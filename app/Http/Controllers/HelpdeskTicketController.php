<?php

namespace App\Http\Controllers;

use App\Support\Helpdesk as H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// helpdesk, helpdesk/{id}, helpdesk/{id}/ambil — 'helpdesk' Gate (TD only) in routes/api.php.
// Exceptions fall through to Helpdesk::dbError via bootstrap/app.php
class HelpdeskTicketController extends Controller
{
    // GET /api/helpdesk?start&end&status&kategori_id&staff_id&sumber&q&limit&offset -> { rows, total }
    public function index(Request $request)
    {
        $sp = $request->query();
        [$where, $p] = H::ticketFilter($sp);
        $n = fn ($k) => ($v = H::num($sp[$k] ?? null)) && ! is_nan($v) ? (int) $v : 0;
        $limit = min(max($n('limit') ?: 25, 1), 200);
        $offset = max($n('offset'), 0);
        // Open queue: oldest first. Everything else: newest first.
        $order = ($sp['status'] ?? null) === 'Open' ? 't.id ASC' : 't.tanggal DESC, t.id DESC';
        $rows = DB::select(H::TICKET_SELECT." $where ORDER BY $order LIMIT ? OFFSET ?", [...$p, $limit, $offset]);
        $total = DB::selectOne("SELECT COUNT(*) AS total FROM hd_tickets t
         LEFT JOIN users ru ON ru.id = t.requested_by
         LEFT JOIN hd_pic p ON p.id = t.pic_id
         LEFT JOIN hd_unit_kerja uk ON uk.id = t.unit_kerja_id $where", $p)->total;
        return response()->json(['rows' => array_map([H::class, 'toTicket'], $rows), 'total' => (int) $total]);
    }

    // TD records a ticket directly (phone / walk-in): PIC required, created_by = session user
    public function store(Request $request)
    {
        $u = $request->user();
        [$d, $err] = H::validateTicket(self::json());
        if (! $d) return self::err($err, 400);
        if (! $d['pic_id']) return self::err('PIC (peminta) wajib dipilih', 400);
        if (! $d['staff_ids']) return self::err('Pilih minimal 1 staf IT', 400);
        if (! H::allTdStaff($d['staff_ids'])) return self::err('Staf IT harus user aktif unit TD', 400);

        $id = DB::transaction(function () use ($d, $u) {
            DB::insert('INSERT INTO hd_tickets (tanggal, jam_mulai, jam_selesai, pic_id, kategori_id, unit_kerja_id,
           isi_helpdesk, tindakan, status, jenis_pelayanan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $d['tanggal'], $d['jam_mulai'], $d['jam_selesai'], $d['pic_id'], $d['kategori_id'], $d['unit_kerja_id'],
                $d['isi_helpdesk'], $d['tindakan'], $d['status'], $d['jenis_pelayanan'], $u->id]);
            $id = (int) DB::getPdo()->lastInsertId();
            H::setStaff($id, $d['staff_ids']);
            H::setBiaya($id, $d['biaya']);
            $t = H::loadTicket($id);
            $changes = [['field' => 'status', 'label' => 'Status', 'new' => $d['status']], ['field' => 'staff_names', 'label' => 'Staf IT', 'new' => $t['staff_names'] ?? null]];
            if ($d['biaya']) $changes[] = ['field' => 'biaya', 'label' => 'Biaya', 'new' => 'Rp '.($t['total_biaya'] ?? 0).' ('.count($d['biaya']).' barang)'];
            H::addHistory($id, 'create', $u->id, $changes);
            return $id;
        });
        H::notify(array_filter($d['staff_ids'], fn ($s) => $s !== $u->id), "Tiket Helpdesk #$id", "{$u->name} menugaskan Anda: ".H::short($d['isi_helpdesk']));
        return response()->json(['ticket' => H::loadTicket($id)], 201);
    }

    public function show(string $id)
    {
        if (! $id = H::parseId($id)) return H::badId();
        if (! $t = H::loadTicket($id)) return self::err('Tiket tidak ditemukan', 404);
        $history = DB::select('SELECT h.id, h.aksi, h.changes, h.created_at, u.name AS user_name
       FROM hd_ticket_history h LEFT JOIN users u ON u.id = h.user_id WHERE h.ticket_id = ? ORDER BY h.id DESC', [$id]);
        // mysql2 returns JSON columns parsed
        foreach ($history as $h) $h->changes = is_string($h->changes) ? json_decode($h->changes) : $h->changes;
        return response()->json(['ticket' => $t, 'history' => $history]);
    }

    public function update(Request $request, string $id)
    {
        $u = $request->user();
        if (! $id = H::parseId($id)) return H::badId();
        [$d, $err] = H::validateTicket(self::json());
        if (! $d) return self::err($err, 400);
        if (! H::allTdStaff($d['staff_ids'])) return self::err('Staf IT harus user aktif unit TD', 400);

        $res = DB::transaction(function () use ($id, $d, $u) {
            // Row lock: two TD staff saving the same ticket apply one after the other, each diffing the latest state
            if (! DB::select('SELECT id FROM hd_tickets WHERE id = ? FOR UPDATE', [$id])) return null;
            $before = H::loadTicket($id);
            DB::update('UPDATE hd_tickets SET tanggal = ?, jam_mulai = ?, jam_selesai = ?, pic_id = ?, kategori_id = ?, unit_kerja_id = ?,
           isi_helpdesk = ?, tindakan = ?, status = ?, jenis_pelayanan = ? WHERE id = ?', [
                $d['tanggal'], $d['jam_mulai'], $d['jam_selesai'], $d['pic_id'], $d['kategori_id'], $d['unit_kerja_id'],
                $d['isi_helpdesk'], $d['tindakan'], $d['status'], $d['jenis_pelayanan'], $id]);
            H::setStaff($id, $d['staff_ids']);
            H::setBiaya($id, $d['biaya']);
            $after = H::loadTicket($id);
            if ($changes = H::diffTicket($before, $after)) H::addHistory($id, 'update', $u->id, $changes);
            return [$before, $after];
        });
        if (! $res) return self::err('Tiket tidak ditemukan', 404);
        [$before, $after] = $res;
        H::notify(array_filter($after['staff_ids'], fn ($s) => ! in_array($s, $before['staff_ids'], true) && $s !== $u->id),
            "Tiket Helpdesk #$id", "{$u->name} menugaskan Anda ke tiket #$id");
        if ($after['requested_by'] && $before['status'] !== $after['status'] && $after['requested_by'] !== $u->id)
            H::notify([$after['requested_by']], "Pengajuan #$id: {$after['status']}", "Status pengajuan Anda sekarang {$after['status']}.");
        return response()->json(['ticket' => $after]);
    }

    // Hard delete (history & staff cascade). Creator or Admin TD only.
    public function destroy(Request $request, string $id)
    {
        $u = $request->user();
        if (! $id = H::parseId($id)) return H::badId();
        $row = DB::selectOne('SELECT created_by FROM hd_tickets WHERE id = ?', [$id]);
        if (! $row) return self::err('Tiket tidak ditemukan', 404);
        if ($row->created_by !== $u->id && $u->cannot('helpdesk-master')) return self::err('Hanya pembuat tiket atau admin TD yang bisa menghapus', 403);
        DB::delete('DELETE FROM hd_tickets WHERE id = ?', [$id]);
        return response()->json(['success' => true]);
    }

    // "Ambil": the status = 'Open' condition makes a second concurrent "Ambil" get 0 rows -> 409.
    public function ambil(Request $request, string $id)
    {
        $u = $request->user();
        if (! $id = H::parseId($id)) return H::badId();
        $taken = DB::transaction(function () use ($id, $u) {
            if (! DB::update("UPDATE hd_tickets SET status = 'Proses', jam_mulai = COALESCE(jam_mulai, CURTIME()) WHERE id = ? AND status = 'Open'", [$id])) return false;
            DB::insert('INSERT IGNORE INTO hd_ticket_staff (ticket_id, user_id) VALUES (?, ?)', [$id, $u->id]);
            H::addHistory($id, 'update', $u->id, [
                ['field' => 'status', 'label' => 'Status', 'old' => 'Open', 'new' => 'Proses'],
                ['field' => 'staff_names', 'label' => 'Staf IT', 'new' => $u->name],
            ]);
            return true;
        });
        if (! $taken) return H::loadTicket($id) ? self::err("Tiket #$id sudah diambil / tidak Open lagi", 409) : self::err('Tiket tidak ditemukan', 404);
        $t = H::loadTicket($id);
        if ($t['requested_by'] && $t['requested_by'] !== $u->id) H::notify([$t['requested_by']], "Pengajuan #$id diproses", "{$u->name} sedang menangani pengajuan Anda.");
        return response()->json(['ticket' => $t]);
    }
}

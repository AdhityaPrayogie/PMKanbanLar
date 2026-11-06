<?php

namespace App\Http\Controllers;

use App\Support\Helpdesk as H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Pengajuan Helpdesk: any logged-in user, scoped to requested_by = session user
class HelpdeskPengajuanController extends Controller
{
    const MAX_OPEN_PER_USER = 10;

    public function index(Request $request)
    {
        return response()->json(['tickets' => array_map([H::class, 'toTicket'],
            DB::select(H::TICKET_SELECT.' WHERE t.requested_by = ? ORDER BY t.id DESC LIMIT 100', [$request->user()->id]))]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        // Only these 3 fields are read; status/staff/jam in the body are ignored on purpose
        $b = self::body();
        $str = fn ($v) => is_scalar($v) ? (is_bool($v) ? ($v ? 'true' : 'false') : (string) $v) : '';
        $kat = H::parseId($str($b['kategori_id'] ?? ''));
        $uk = H::parseId($str($b['unit_kerja_id'] ?? ''));
        $isi = trim($str($b['isi_helpdesk'] ?? ''));
        if (! $kat || ! $uk || $isi === '') return self::err('Kategori, lokasi, dan keluhan wajib diisi', 400);
        if (mb_strlen($isi) > H::MAX_TEXT) return self::err('Keluhan maksimal '.H::MAX_TEXT.' karakter', 400);
        // ponytail: count-then-insert can let 2 parallel requests slip past the cap; fine for spam limiting
        if ((int) DB::selectOne("SELECT COUNT(*) AS n FROM hd_tickets WHERE requested_by = ? AND status = 'Open'", [$u->id])->n >= self::MAX_OPEN_PER_USER)
            return self::err('Maksimal '.self::MAX_OPEN_PER_USER.' pengajuan yang belum diproses', 429);
        if (! DB::selectOne('SELECT 1 FROM hd_kategori WHERE id = ? AND aktif = 1', [$kat]) || ! DB::selectOne('SELECT 1 FROM hd_unit_kerja WHERE id = ? AND aktif = 1', [$uk]))
            return self::err('Kategori / lokasi tidak ditemukan', 400);
        $id = DB::transaction(function () use ($u, $kat, $uk, $isi) {
            DB::insert('INSERT INTO hd_tickets (tanggal, requested_by, created_by, kategori_id, unit_kerja_id, isi_helpdesk) VALUES (CURDATE(), ?, ?, ?, ?, ?)',
                [$u->id, $u->id, $kat, $uk, $isi]);
            $id = (int) DB::getPdo()->lastInsertId();
            H::addHistory($id, 'create', $u->id, [['field' => 'status', 'label' => 'Status', 'new' => 'Open'], ['field' => 'sumber', 'label' => 'Sumber', 'new' => 'Pengajuan']]);
            return $id;
        });
        H::notify(H::tdUserIds(), "Pengajuan Helpdesk #$id", "{$u->name} (".($u->unit_name ?: '-')."): ".H::short($isi));
        return response()->json(['id' => $id], 201);
    }

    public function destroy(Request $request)
    {
        $u = $request->user();
        if (! $id = H::parseId($request->query('id') ?? '')) return H::badId();
        // Status checked inside the DELETE itself: TD may "Ambil" at the same moment
        if (DB::delete("DELETE FROM hd_tickets WHERE id = ? AND requested_by = ? AND status = 'Open'", [$id, $u->id])) return response()->json(['success' => true]);
        return DB::selectOne('SELECT 1 FROM hd_tickets WHERE id = ? AND requested_by = ?', [$id, $u->id])
            ? self::err('Pengajuan sudah diproses TD, tidak bisa dihapus', 409)
            : self::err('Pengajuan tidak ditemukan', 404);
    }
}

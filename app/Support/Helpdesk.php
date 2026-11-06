<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// Port of src/lib/helpdesk.ts + helpdeskDb.ts
class Helpdesk
{
    const STATUS = ['Open', 'Proses', 'Selesai', 'Batal'];
    const JENIS = ['Pelayanan', 'Non Pelayanan'];
    const MAX_TEXT = 5000;
    const MAX_BIAYA = 50;
    const TIME_RE = '/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';
    const HISTORY_FIELDS = [
        'tanggal' => 'Tanggal', 'jam_mulai' => 'Jam Mulai', 'jam_selesai' => 'Jam Selesai', 'pic_nama' => 'PIC',
        'kategori' => 'Kategori', 'unit_kerja' => 'Unit Kerja', 'isi_helpdesk' => 'Isi Helpdesk', 'tindakan' => 'Tindakan',
        'status' => 'Status', 'jenis_pelayanan' => 'Jenis', 'staff_names' => 'Staf IT', 'biaya' => 'Biaya',
    ];

    const TICKET_SELECT = "
  SELECT t.id, DATE_FORMAT(t.tanggal, '%Y-%m-%d') AS tanggal,
    TIME_FORMAT(t.jam_mulai, '%H:%i') AS jam_mulai, TIME_FORMAT(t.jam_selesai, '%H:%i') AS jam_selesai,
    t.total_menit, t.requested_by, ru.name AS requester_name, ru.unit_name AS requester_unit,
    t.pic_id, p.nama AS pic_nama, t.kategori_id, k.nama AS kategori, t.unit_kerja_id, uk.nama AS unit_kerja,
    t.isi_helpdesk, t.tindakan, t.status, t.jenis_pelayanan, t.created_by, cu.name AS creator_name, t.created_at,
    (SELECT GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') FROM hd_ticket_staff s JOIN users u ON u.id = s.user_id WHERE s.ticket_id = t.id) AS staff_names,
    (SELECT GROUP_CONCAT(s.user_id ORDER BY s.user_id) FROM hd_ticket_staff s WHERE s.ticket_id = t.id) AS staff_ids,
    (SELECT JSON_ARRAYAGG(JSON_OBJECT('nama_barang', b.nama_barang, 'qty', b.qty, 'harga', b.harga))
       FROM (SELECT * FROM hd_ticket_biaya WHERE ticket_id = t.id ORDER BY id) b) AS biaya,
    (SELECT COALESCE(SUM(b.qty * b.harga), 0) FROM hd_ticket_biaya b WHERE b.ticket_id = t.id) AS total_biaya
  FROM hd_tickets t
  JOIN hd_kategori k ON k.id = t.kategori_id
  JOIN users cu ON cu.id = t.created_by
  LEFT JOIN users ru ON ru.id = t.requested_by
  LEFT JOIN hd_pic p ON p.id = t.pic_id
  LEFT JOIN hd_unit_kerja uk ON uk.id = t.unit_kerja_id";

    /** JS Number() semantics (NAN when not numeric). */
    public static function num($v): float
    {
        if (is_int($v) || is_float($v)) return (float) $v;
        if (is_bool($v)) return $v ? 1.0 : 0.0;
        if ($v === null) return 0.0;
        if (is_string($v)) {
            $t = trim($v);
            return $t === '' ? 0.0 : (is_numeric($t) ? (float) $t : NAN);
        }
        return NAN;
    }

    public static function isInt(float $n): bool
    {
        return is_finite($n) && floor($n) == $n;
    }

    public static function isDate($v): bool
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)
            && checkdate((int) substr($v, 5, 2), (int) substr($v, 8, 2), (int) substr($v, 0, 4));
    }

    /** null/'' -> null, positive int -> int, else false (invalid). */
    public static function optId($v)
    {
        if ($v === null || $v === '') return null;
        $n = self::num($v);
        return self::isInt($n) && $n > 0 ? (int) $n : false;
    }

    /** empty -> null, valid -> HH:MM, invalid -> false. */
    public static function optTime($v)
    {
        if ($v === null || $v === '') return null;
        return is_string($v) && preg_match(self::TIME_RE, $v) ? substr($v, 0, 5) : false;
    }

    public static function parseId($v): ?int
    {
        $n = self::num((string) $v);
        return self::isInt($n) && $n > 0 ? (int) $n : null;
    }

    /** @return array|string cleaned items or error message */
    public static function parseBiaya($v)
    {
        if ($v === null) return [];
        if (! is_array($v) || ! array_is_list($v) || count($v) > self::MAX_BIAYA) return 'Daftar biaya tidak valid (maks '.self::MAX_BIAYA.' barang)';
        $out = [];
        foreach ($v as $b) {
            $b = is_array($b) ? $b : [];
            $nama = trim(is_scalar($b['nama_barang'] ?? null) ? (string) $b['nama_barang'] : '');
            $qty = self::num($b['qty'] ?? NAN);
            $harga = self::num($b['harga'] ?? NAN);
            if ($nama === '' || mb_strlen($nama) > 150) return 'Nama barang wajib diisi (maks 150 karakter)';
            if (! self::isInt($qty) || $qty < 1 || $qty > 9999) return "Qty \"$nama\" harus 1-9999";
            if (! self::isInt($harga) || $harga < 0 || $harga > 2000000000) return "Harga \"$nama\" tidak valid";
            $out[] = ['nama_barang' => $nama, 'qty' => (int) $qty, 'harga' => (int) $harga];
        }
        return $out;
    }

    /** @return array{0: ?array, 1: ?string} [data, error] */
    public static function validateTicket($b): array
    {
        if (! is_array($b)) return [null, 'Body tidak valid'];
        if (! self::isDate($b['tanggal'] ?? null)) return [null, 'Tanggal tidak valid'];
        $jm = self::optTime($b['jam_mulai'] ?? null);
        $js = self::optTime($b['jam_selesai'] ?? null);
        if ($jm === false || $js === false) return [null, 'Format jam tidak valid (HH:MM)'];
        if ($jm && $js && $js < $jm) return [null, 'Jam selesai harus setelah jam mulai'];
        $kat = self::optId($b['kategori_id'] ?? null);
        if (! $kat) return [null, 'Kategori wajib dipilih'];
        $pic = self::optId($b['pic_id'] ?? null);
        $uk = self::optId($b['unit_kerja_id'] ?? null);
        if ($pic === false || $uk === false) return [null, 'PIC / unit kerja tidak valid'];
        $str = fn ($v) => is_scalar($v) ? (is_bool($v) ? ($v ? 'true' : 'false') : (string) $v) : '';
        $isi = trim($str($b['isi_helpdesk'] ?? ''));
        if ($isi === '') return [null, 'Isi helpdesk wajib diisi'];
        $tindakan = trim($str($b['tindakan'] ?? '')) ?: null;
        if (mb_strlen($isi) > self::MAX_TEXT || mb_strlen($tindakan ?? '') > self::MAX_TEXT) return [null, 'Teks maksimal '.self::MAX_TEXT.' karakter'];
        if (! in_array($b['status'] ?? null, self::STATUS, true)) return [null, 'Status tidak valid'];
        if (! in_array($b['jenis_pelayanan'] ?? null, self::JENIS, true)) return [null, 'Jenis pelayanan tidak valid'];
        $staff = is_array($b['staff_ids'] ?? null)
            ? array_values(array_unique(array_filter($b['staff_ids'], fn ($s) => is_string($s) && $s !== '')))
            : [];
        if ($b['status'] !== 'Open' && ! $staff) return [null, 'Pilih minimal 1 staf IT untuk status selain Open'];
        $biaya = self::parseBiaya($b['biaya'] ?? null);
        if (is_string($biaya)) return [null, $biaya];
        return [[
            'tanggal' => $b['tanggal'], 'jam_mulai' => $jm, 'jam_selesai' => $js, 'pic_id' => $pic, 'kategori_id' => $kat,
            'unit_kerja_id' => $uk, 'isi_helpdesk' => $isi, 'tindakan' => $tindakan, 'status' => $b['status'],
            'jenis_pelayanan' => $b['jenis_pelayanan'], 'staff_ids' => $staff, 'biaya' => $biaya,
        ], null];
    }

    public static function biayaText(array $items): string
    {
        return implode('; ', array_map(fn ($b) => "{$b['nama_barang']} {$b['qty']}x{$b['harga']}", $items));
    }

    public static function diffTicket(array $before, array $after): array
    {
        $val = fn ($o, $f) => $f === 'biaya' ? (self::biayaText($o['biaya'] ?? []) ?: null) : ($o[$f] ?? null);
        $out = [];
        foreach (self::HISTORY_FIELDS as $f => $label) {
            if ((string) $val($before, $f) !== (string) $val($after, $f))
                $out[] = ['field' => $f, 'label' => $label, 'old' => $val($before, $f), 'new' => $val($after, $f)];
        }
        return $out;
    }

    public static function safeCell($v)
    {
        return is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'$v" : $v;
    }

    public static function short(string $s, int $n = 60): string
    {
        return mb_strlen($s) > $n ? mb_substr($s, 0, $n).'…' : $s;
    }

    public static function ticketFilter(array $sp): array
    {
        $w = [];
        $p = [];
        $get = fn ($k) => isset($sp[$k]) && is_string($sp[$k]) ? $sp[$k] : null;
        if (($s = $get('start')) && self::isDate($s)) { $w[] = 't.tanggal >= ?'; $p[] = $s; }
        if (($e = $get('end')) && self::isDate($e)) { $w[] = 't.tanggal <= ?'; $p[] = $e; }
        if (($st = $get('status')) && in_array($st, self::STATUS, true)) { $w[] = 't.status = ?'; $p[] = $st; }
        $kats = array_values(array_filter(array_map(fn ($x) => self::num($x), explode(',', $get('kategori_ids') ?? $get('kategori_id') ?? '')),
            fn ($n) => self::isInt($n) && $n > 0));
        if ($kats) { $w[] = 't.kategori_id IN ('.implode(',', array_fill(0, count($kats), '?')).')'; array_push($p, ...array_map('intval', $kats)); }
        if ($staff = $get('staff_id')) { $w[] = 'EXISTS (SELECT 1 FROM hd_ticket_staff s2 WHERE s2.ticket_id = t.id AND s2.user_id = ?)'; $p[] = $staff; }
        if ($get('sumber') === 'pengajuan') $w[] = 't.requested_by IS NOT NULL';
        if ($get('sumber') === 'langsung') $w[] = 't.requested_by IS NULL';
        if (($q = trim($get('q') ?? '')) !== '') {
            $like = '%'.addcslashes($q, '\\%_').'%';
            $w[] = '(t.isi_helpdesk LIKE ? OR t.tindakan LIKE ? OR p.nama LIKE ? OR ru.name LIKE ? OR uk.nama LIKE ?)';
            array_push($p, $like, $like, $like, $like, $like);
        }
        return [$w ? 'WHERE '.implode(' AND ', $w) : '', $p];
    }

    public static function toTicket($r): array
    {
        $r = (array) $r;
        $r['staff_ids'] = $r['staff_ids'] ? explode(',', (string) $r['staff_ids']) : [];
        $r['biaya'] = (is_string($r['biaya']) ? json_decode($r['biaya'], true) : $r['biaya']) ?? [];
        $r['total_biaya'] = (int) ($r['total_biaya'] ?? 0);
        return $r;
    }

    public static function loadTicket(int $id): ?array
    {
        $r = DB::selectOne(self::TICKET_SELECT.' WHERE t.id = ?', [$id]);
        return $r ? self::toTicket($r) : null;
    }

    public static function setStaff(int $id, array $ids): void
    {
        DB::delete('DELETE FROM hd_ticket_staff WHERE ticket_id = ?', [$id]);
        if ($ids) DB::table('hd_ticket_staff')->insert(array_map(fn ($u) => ['ticket_id' => $id, 'user_id' => $u], $ids));
    }

    public static function setBiaya(int $id, array $items): void
    {
        DB::delete('DELETE FROM hd_ticket_biaya WHERE ticket_id = ?', [$id]);
        if ($items) DB::table('hd_ticket_biaya')->insert(array_map(fn ($b) => ['ticket_id' => $id] + $b, $items));
    }

    public static function addHistory(int $id, string $aksi, string $userId, array $changes): void
    {
        DB::insert('INSERT INTO hd_ticket_history (ticket_id, aksi, user_id, changes) VALUES (?, ?, ?, ?)',
            [$id, $aksi, $userId, json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    public static function allTdStaff(array $ids): bool
    {
        if (! $ids) return true;
        $n = DB::selectOne('SELECT COUNT(*) AS n FROM users WHERE id IN ('.implode(',', array_fill(0, count($ids), '?')).") AND unit_id = 'unit_it' AND status = 'active'", $ids)->n;
        return (int) $n === count($ids);
    }

    public static function tdUserIds(): array
    {
        return array_column(DB::select("SELECT id FROM users WHERE unit_id = 'unit_it' AND status = 'active'"), 'id');
    }

    // Best-effort: a failure here must not fail the ticket write that already committed
    public static function notify(array $ids, string $title, string $msg): void
    {
        foreach (array_unique($ids) as $uid) {
            try { Events::notify($uid, $title, $msg); } catch (\Throwable $e) { report($e); }
        }
    }

    public static function badId()
    {
        return response()->json(['error' => 'id tidak valid'], 400);
    }

    public static function dbError(\Throwable $e)
    {
        $errno = $e instanceof \PDOException || $e instanceof \Illuminate\Database\QueryException ? ($e->errorInfo[1] ?? null) : null;
        [$msg, $code] = match ($errno) {
            1062 => ['Nama sudah ada', 409],
            1451 => ['Data masih dipakai tiket lain', 409],
            1452 => ['PIC / kategori / unit kerja / staf tidak ditemukan', 400],
            3819 => ['Data tidak valid: cek jam atau peminta (PIC)', 400],
            default => ['Database error', 500],
        };
        if ($code === 500) report($e);
        return response()->json(['error' => $msg], $code);
    }
}

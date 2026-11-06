<?php

namespace App\Http\Controllers;

use App\Support\Helpdesk as H;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

// helpdesk/master/{type}: outside the auth middleware so the Next.js order holds: unknown type 404 -> 401 -> 403
class HelpdeskMasterController extends Controller
{
    const TABLES = ['pic' => ['hd_pic', 150], 'kategori' => ['hd_kategori', 100], 'unit-kerja' => ['hd_unit_kerja', 150]];

    /** 404 / 401 response, or null when the request may proceed. */
    private static function guard(Request $request, string $type)
    {
        if (! isset(self::TABLES[$type])) return self::err('Tipe tidak dikenal', 404);
        if (! $request->user()) return self::err('Unauthorized', 401);
        return null;
    }

    // kategori & unit-kerja: every logged-in user (pengajuan form). pic (staff names): TD only.
    public function index(Request $request, string $type)
    {
        if ($e = self::guard($request, $type)) return $e;
        if ($type === 'pic') Gate::authorize('helpdesk');
        $table = self::TABLES[$type][0];
        $all = $request->query('all') === '1' && $request->user()->can('helpdesk');
        return response()->json(['rows' => DB::select("SELECT id, nama, aktif FROM $table ".($all ? '' : 'WHERE aktif = 1').' ORDER BY nama')]);
    }

    private static function nama($v, int $max)
    {
        $n = preg_replace('/\s+/u', ' ', trim(is_scalar($v) ? (string) $v : ''));
        return $n === '' || mb_strlen($n) > $max ? null : $n;
    }

    // Add. Any TD user may add a PIC from the ticket form; other types need Admin TD.
    public function store(Request $request, string $type)
    {
        if ($e = self::guard($request, $type)) return $e;
        Gate::authorize($type === 'pic' ? 'helpdesk' : 'helpdesk-master');
        [$table, $max] = self::TABLES[$type];
        $b = self::json();
        if (! $nama = self::nama(is_array($b) ? ($b['nama'] ?? '') : '', $max)) return self::err("Nama wajib diisi (maks $max karakter)", 400);
        try {
            DB::insert("INSERT INTO $table (nama) VALUES (?)", [$nama]);
            return response()->json(['row' => ['id' => (int) DB::getPdo()->lastInsertId(), 'nama' => $nama, 'aktif' => 1]], 201);
        } catch (\Illuminate\Database\QueryException $ex) {
            // Already exists: hand back the existing row so the form can just select it
            if (($ex->errorInfo[1] ?? null) === 1062 && ($row = DB::selectOne("SELECT id, nama, aktif FROM $table WHERE nama = ?", [$nama])))
                return response()->json(['row' => $row, 'existed' => true]);
            return H::dbError($ex);
        }
    }

    // Rename / toggle aktif. Admin TD only. No delete: rows are referenced by tickets.
    public function update(Request $request, string $type)
    {
        if ($e = self::guard($request, $type)) return $e;
        Gate::authorize('helpdesk-master');
        [$table, $max] = self::TABLES[$type];
        $b = self::body();
        if (! $id = H::parseId(is_scalar($b['id'] ?? '') ? (string) ($b['id'] ?? '') : '')) return H::badId();
        $set = [];
        if (array_key_exists('nama', $b)) {
            if (! $set['nama'] = self::nama($b['nama'], $max)) return self::err("Nama wajib diisi (maks $max karakter)", 400);
        }
        if (array_key_exists('aktif', $b)) $set['aktif'] = self::truthy($b['aktif']) ? 1 : 0;
        if (! $set) return self::err('Tidak ada perubahan', 400);
        // affected rows = matched rows would need CLIENT_FOUND_ROWS; check existence instead
        if (! DB::selectOne("SELECT 1 FROM $table WHERE id = ?", [$id])) return self::err('Data tidak ditemukan', 404);
        DB::table($table)->where('id', $id)->update($set);
        return response()->json(['success' => true]);
    }
}

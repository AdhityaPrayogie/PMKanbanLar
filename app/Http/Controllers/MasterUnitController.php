<?php

namespace App\Http\Controllers;

use App\Support\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// master-units (units table); a code rename cascades to users.unit_name and projects.unit_tag
class MasterUnitController extends Controller
{
    public function index()
    {
        return response()->json(['units' => DB::select('SELECT u.*,
        (SELECT COUNT(*) FROM users usr WHERE usr.unit_id = u.id OR usr.unit_name = u.code) as users_count,
        (SELECT COUNT(*) FROM projects p WHERE p.unit_tag = u.code) as projects_count
       FROM units u ORDER BY u.created_at ASC')]);
    }

    public function store(Request $request)
    {
        $b = self::body();
        $name = trim((string) ($b['name'] ?? ''));
        $code = strtoupper(trim((string) ($b['code'] ?? '')));
        if ($name === '') return self::err('Nama unit wajib diisi', 400);
        if ($code === '') return self::err('Kode unit wajib diisi (Contoh: IT, MARKOM)', 400);
        if (DB::selectOne('SELECT id FROM units WHERE code = ?', [$code])) return self::err("Unit dengan kode \"$code\" sudah ada", 409);
        $id = 'unit_'.strtolower($code).'_'.substr((string) Str::uuid(), 0, 4);
        $desc = trim((string) ($b['description'] ?? '')) ?: null;
        DB::insert('INSERT INTO units (id, name, code, description, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())', [$id, $name, $code, $desc]);
        Events::notify($request->user()->id, 'Master Unit Baru Ditambahkan', "Unit \"$name\" ($code) berhasil ditambahkan ke master table units.");
        return response()->json(['success' => true, 'unit' => ['id' => $id, 'name' => $name, 'code' => $code, 'description' => $desc, 'users_count' => 0, 'projects_count' => 0]]);
    }

    public function update()
    {
        $b = self::body();
        $id = $b['id'] ?? null;
        if (! self::truthy($id)) return self::err('ID unit wajib diisi', 400);
        $cur = DB::selectOne('SELECT * FROM units WHERE id = ?', [$id]);
        if (! $cur) return self::err('Unit tidak ditemukan', 404);
        $set = [];
        if (($n = trim((string) ($b['name'] ?? ''))) !== '') $set['name'] = $n;
        $code = $cur->code;
        if (($c = strtoupper(trim((string) ($b['code'] ?? '')))) !== '' && $c !== $cur->code) {
            if (DB::selectOne('SELECT id FROM units WHERE code = ? AND id != ?', [$c, $id])) return self::err("Unit dengan kode \"$c\" sudah digunakan", 409);
            $set['code'] = $code = $c;
        }
        if (array_key_exists('description', $b)) $set['description'] = trim((string) $b['description']) ?: null;
        if ($set) {
            $cols = implode(', ', array_map(fn ($k) => "$k = ?", array_keys($set)));
            DB::update("UPDATE units SET $cols, updated_at = NOW() WHERE id = ?", [...array_values($set), $id]);
        }
        if ($code !== $cur->code) {
            DB::update('UPDATE users SET unit_name = ? WHERE unit_id = ? OR unit_name = ?', [$code, $id, $cur->code]);
            DB::update('UPDATE projects SET unit_tag = ? WHERE unit_tag = ?', [$code, $cur->code]);
        }
        return response()->json(['success' => true, 'updatedCode' => $code]);
    }
}

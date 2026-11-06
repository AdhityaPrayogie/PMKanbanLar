<?php

namespace App\Http\Controllers;

use App\Support\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// admin/users — guarded by the 'admin' Gate (routes/api.php)
class AdminUserController extends Controller
{
    public function index()
    {
        return response()->json(['users' => DB::select('SELECT id, name, email, avatar_url, role, status, unit_id, unit_name, password_plain, created_at FROM users ORDER BY created_at DESC')]);
    }

    public function update(Request $request)
    {
        $b = self::body();
        $id = $b['userId'] ?? null;
        if (! self::truthy($id)) return self::err('userId wajib diisi', 400);
        $set = [];
        foreach (['status', 'role'] as $f) if (self::truthy($b[$f] ?? null)) $set[$f] = $b[$f];
        foreach (['unit_id', 'unit_name'] as $f) if (array_key_exists($f, $b)) $set[$f] = $b[$f];
        if ($set) DB::table('users')->where('id', $id)->update($set);
        if (($b['status'] ?? null) === 'active') {
            Events::notify($id, 'Akun Anda Telah Disetujui!', 'Selamat, pendaftaran akun Anda telah di-ACC oleh Administrator. Anda sekarang dapat mengakses seluruh fitur.');
        }
        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $id = $request->query('userId');
        if (! self::truthy($id)) return self::err('userId wajib diisi', 400);
        if ($id === $request->user()->id) return self::err('Tidak dapat menghapus akun Anda sendiri', 400);
        DB::delete('DELETE FROM users WHERE id = ?', [$id]);
        return response()->json(['success' => true]);
    }
}

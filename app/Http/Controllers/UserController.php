<?php

namespace App\Http\Controllers;

use App\Support\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// users (team list), settings (own profile)
class UserController extends Controller
{
    public function index()
    {
        // Never SELECT * here: users also holds password_hash / password_plain
        return response()->json(['users' => DB::select('SELECT id, name, email, avatar_url, unit_id, unit_name, role, status, last_active_at FROM users ORDER BY name ASC')]);
    }

    public function updateSettings(Request $request)
    {
        $u = $request->user();
        $b = self::body();
        if (self::truthy($b['name'] ?? null)) DB::update('UPDATE users SET name = ? WHERE id = ?', [trim((string) $b['name']), $u->id]);
        if (array_key_exists('avatar_url', $b)) DB::update('UPDATE users SET avatar_url = ? WHERE id = ?', [$b['avatar_url'], $u->id]);
        if (self::truthy($new = $b['newPassword'] ?? null)) {
            if (! self::truthy($b['oldPassword'] ?? null)) return self::err('Password lama diperlukan untuk mengubah password', 400);
            if (mb_strlen((string) $new) < 6) return self::err('Password baru minimal 6 karakter', 400);
            $hash = DB::selectOne('SELECT password_hash FROM users WHERE id = ?', [$u->id])?->password_hash;
            if (! Auth::verifyPassword((string) $b['oldPassword'], $hash)) return self::err('Password lama salah', 400);
            DB::update('UPDATE users SET password_hash = ? WHERE id = ?', [Auth::hashPassword((string) $new), $u->id]);
        }
        return response()->json(['success' => true, 'user' => DB::selectOne('SELECT id, name, email, avatar_url, role, status FROM users WHERE id = ?', [$u->id])]);
    }
}

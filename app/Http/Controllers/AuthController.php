<?php

namespace App\Http\Controllers;

use App\Support\Auth;
use App\Support\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// auth/login, auth/logout, auth/me, auth/register (public routes)
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $b = self::body();
        $email = $b['email'] ?? null;
        $pw = $b['password'] ?? null;
        if (! self::truthy($email) || ! self::truthy($pw)) return self::err('Email dan password wajib diisi', 400);
        $u = DB::selectOne('SELECT id, name, email, avatar_url, role, status, password_hash FROM users WHERE email = ?', [strtolower(trim((string) $email))]);
        if (! $u || ! Auth::verifyPassword((string) $pw, $u->password_hash)) return self::err('Email atau password salah', 401);
        if ($u->status === 'pending') return self::err('Akun Anda sedang menunggu persetujuan (approval) dari Administrator sebelum dapat masuk.', 403);
        if ($u->status === 'rejected') return self::err('Akses akun Anda telah ditolak atau dinonaktifkan oleh Administrator.', 403);
        $token = Auth::token(['id' => $u->id, 'email' => $u->email, 'role' => $u->role]);
        unset($u->password_hash);
        return response()->json(['success' => true, 'user' => $u])
            ->cookie('auth_token', $token, 7 * 24 * 60, '/', null, app()->isProduction(), true, false, 'lax');
    }

    public function logout()
    {
        return response()->json(['success' => true])->cookie('auth_token', '', -1, '/', null, false, true);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        return $u ? response()->json(['authenticated' => true, 'user' => $u])
            : response()->json(['authenticated' => false, 'user' => null], 401);
    }

    public function register(Request $request)
    {
        $b = self::body();
        [$name, $email, $pw] = [$b['name'] ?? null, $b['email'] ?? null, $b['password'] ?? null];
        if (! self::truthy($name) || ! self::truthy($email) || ! self::truthy($pw)) return self::err('Nama, email, dan password wajib diisi', 400);
        if (mb_strlen((string) $pw) < 6) return self::err('Password minimal 6 karakter', 400);
        $email = strtolower(trim((string) $email));
        if (DB::selectOne('SELECT id FROM users WHERE email = ?', [$email])) return self::err('Email sudah terdaftar', 409);
        $name = trim((string) $name);
        DB::insert("INSERT INTO users (id, name, email, avatar_url, password_hash, password_plain, role, status, unit_id, unit_name)
            VALUES (?, ?, ?, ?, ?, ?, 'user', 'pending', ?, ?)", [
            (string) Str::uuid(), $name, $email,
            'https://api.dicebear.com/7.x/initials/svg?seed='.rawurlencode((string) $b['name']).'&backgroundColor=034694',
            Auth::hashPassword((string) $pw), $pw,
            self::truthy($b['unit_id'] ?? null) ? $b['unit_id'] : null, self::truthy($b['unit_name'] ?? null) ? $b['unit_name'] : 'IT',
        ]);
        foreach (DB::select("SELECT id FROM users WHERE role = 'admin'") as $a) {
            Events::notify($a->id, 'Pendaftaran Pengguna Baru Menunggu ACC',
                "Pengguna $name ($email) baru saja mendaftar dan menunggu persetujuan Anda di menu Kelola Pengguna.");
        }
        return response()->json(['success' => true,
            'message' => 'Pendaftaran berhasil! Akun Anda sedang menunggu persetujuan (approval) dari Administrator sebelum dapat masuk.']);
    }
}

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Port of src/lib/auth.ts — token/password formats must stay byte-compatible with the Next.js app.
class Auth
{
    public static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private static function secret(): string
    {
        return config('app.auth_secret');
    }

    public static function hashPassword(string $pw): string
    {
        $salt = bin2hex(random_bytes(16));
        return $salt.':'.hash_pbkdf2('sha512', $pw, $salt, 1000, 128);
    }

    public static function verifyPassword(string $pw, ?string $stored): bool
    {
        if (! $stored || ! str_contains($stored, ':')) return false;
        [$salt, $hash] = explode(':', $stored, 2);
        return hash_equals($hash, hash_pbkdf2('sha512', $pw, $salt, 1000, 128));
    }

    public static function token(array $payload): string
    {
        $head = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])).'.'
            .self::b64(json_encode($payload + ['exp' => time() + 7 * 86400], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $head.'.'.self::b64(hash_hmac('sha256', $head, self::secret(), true));
    }

    public static function verify(?string $token): ?array
    {
        $p = explode('.', (string) $token);
        if (count($p) !== 3) return null;
        if (! hash_equals(self::b64(hash_hmac('sha256', "$p[0].$p[1]", self::secret(), true)), $p[2])) return null;
        $data = json_decode(base64_decode(strtr($p[1], '-_', '+/')), true);
        if (! is_array($data) || (isset($data['exp']) && $data['exp'] < time())) return null;
        return $data;
    }

    /** Resolver for the 'hmac' guard (AppServiceProvider); use $request->user() / auth()->user() everywhere else. */
    public static function user(Request $request): ?User
    {
        $p = self::verify($request->cookie('auth_token'));
        if (empty($p['id'])) return null;
        $id = (string) $p['id'];
        if (Cache::add("active:$id", 1, 60)) DB::update('UPDATE users SET last_active_at = NOW() WHERE id = ?', [$id]);
        return User::query()->select(User::SESSION_COLUMNS)->find($id);
    }

    public static function canHelpdesk($u): bool
    {
        return ($u->unit_id ?? null) === 'unit_it';
    }

    public static function canHelpdeskMaster($u): bool
    {
        return self::canHelpdesk($u) && ($u->role ?? null) === 'admin';
    }
}

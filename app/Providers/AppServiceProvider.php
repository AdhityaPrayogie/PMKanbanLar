<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Auth as Token;
use Illuminate\Auth\Access\Response;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // auth_token cookie (HMAC JWT shared with the Next.js app) -> guard driver 'hmac' (config/auth.php)
        Auth::viaRequest('hmac', fn (Request $request) => Token::user($request));

        // Deny messages are rendered as {error} 403 in bootstrap/app.php, same text as the Next.js routes.
        // 'admin' also runs for guests: Next.js answers 403 (not 401) on admin/users.
        Gate::define('admin', fn (?User $u = null) => $u?->role === 'admin' ?: Response::deny('Akses ditolak: Hanya untuk Administrator'));
        Gate::define('helpdesk', fn (User $u) => Token::canHelpdesk($u) ?: Response::deny('Khusus unit TD'));
        Gate::define('helpdesk-master', fn (User $u) => Token::canHelpdeskMaster($u) ?: Response::deny('Khusus admin unit TD'));

        // Brute-force guard; {error} JSON is what the login page already displays
        $tooMany = fn (string $msg) => fn (Request $r, array $h) => response()->json(['error' => $msg], 429, $h);
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)
            ->by(strtolower((string) $r->input('email')).'|'.$r->ip())
            ->response($tooMany('Terlalu banyak percobaan login. Coba lagi dalam 1 menit.')));
        RateLimiter::for('register', fn (Request $r) => Limit::perMinute(3)->by($r->ip())
            ->response($tooMany('Terlalu banyak pendaftaran. Coba lagi dalam 1 menit.')));
    }
}

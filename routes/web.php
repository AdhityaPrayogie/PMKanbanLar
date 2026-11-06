<?php

use App\Support\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Session user is rendered into the page (window.__USER__), replacing the client-side /api/auth/me check.
// View name comes from ->defaults(): route:cache can't serialize a closure that captures variables.
// 'auth' redirects guests to /login (bootstrap/app.php redirectGuestsTo).
Route::middleware('auth')->group(function () {
    $page = fn (Request $request, string $view) => view("pages.$view", ['user' => $request->user()]);
    Route::get('/', $page)->defaults('view', 'home');
    Route::get('/messages', $page)->defaults('view', 'messages');
    Route::get('/pengajuan', $page)->defaults('view', 'pengajuan');

    // Server-side gate (TD only). Next streamed this redirect (200 + meta refresh); keep that shape.
    Route::get('/helpdesk', fn (Request $request) => Auth::canHelpdesk($request->user())
        ? view('pages.helpdesk', ['user' => $request->user()])
        : response('<!DOCTYPE html><meta http-equiv="refresh" content="0;url=/pengajuan">'));
});
Route::view('/login', 'pages.login');

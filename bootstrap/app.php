<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // POST /api/broadcasting/auth: api group = no CSRF, auth = JSON 401 like the rest of the API
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['prefix' => 'api', 'middleware' => ['api', 'auth']])
    ->withMiddleware(function (Middleware $middleware): void {
        // auth_token is our own HMAC token shared with the Next.js app, so keep it unencrypted
        $middleware->encryptCookies(except: ['auth_token']);
        // Next.js never trimmed / nulled input (passwords must stay verbatim)
        $middleware->remove([
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 'auth' middleware / Gate denials -> the {error} shape the Next.js routes return
        $exceptions->render(fn (AuthenticationException $e, Request $r) => $r->is('api/*') ? response()->json(['error' => 'Unauthorized'], 401) : null);
        $exceptions->render(fn (AccessDeniedHttpException $e, Request $r) => $r->is('api/*') ? response()->json(['error' => $e->getMessage()], 403) : null);
        // Uncaught API errors -> {error} 500 like the Next.js catch blocks (helpdesk maps MySQL errnos)
        $exceptions->render(function (Throwable $e, Request $request) {
            // HttpResponseException carries its own response (e.g. throttle 429), let Laravel send it
            if (! $request->is('api/*') || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException
                || $e instanceof AuthenticationException || $e instanceof \Illuminate\Auth\Access\AuthorizationException) return null;
            if ($request->is('api/helpdesk*')) return \App\Support\Helpdesk::dbError($e);
            report($e);
            $msg = $e instanceof \Illuminate\Database\QueryException ? ($e->errorInfo[2] ?? null) : $e->getMessage();
            return response()->json(['error' => $msg ?: 'Database error'], 500);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

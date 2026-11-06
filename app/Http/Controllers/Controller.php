<?php

namespace App\Http\Controllers;

// Auth is route middleware ('auth', 'can:*' in routes/api.php): controllers get the user from $request->user().
abstract class Controller
{
    /** JS truthiness ('0' and [] are truthy, unlike PHP). */
    protected static function truthy($v): bool
    {
        return ! ($v === null || $v === false || $v === '' || $v === 0 || $v === 0.0);
    }

    protected static function err(string $msg, int $status)
    {
        return response()->json(['error' => $msg], $status);
    }

    /** JSON body as array ({} when missing / invalid). */
    protected static function body(): array
    {
        $b = json_decode(request()->getContent(), true);
        return is_array($b) ? $b : [];
    }

    /** Raw JSON body, null on invalid JSON (helpdesk validators report "Body ..."). */
    protected static function json()
    {
        return json_decode(request()->getContent(), true);
    }

    protected static function placeholders(array $a): string
    {
        return implode(',', array_fill(0, count($a), '?'));
    }
}

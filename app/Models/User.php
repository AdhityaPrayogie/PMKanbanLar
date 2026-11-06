<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

// The users table is owned by the Next.js app (shared kanbandb, no migrations here).
// This model is the auth user (guard 'hmac') and the Gate subject; queries elsewhere stay on the query builder.
class User extends Authenticatable
{
    /** What the session exposes (window.__USER__, /api/auth/me). Never password_hash / password_plain. */
    const SESSION_COLUMNS = ['id', 'name', 'email', 'avatar_url', 'role', 'status', 'unit_id', 'unit_name', 'last_active_at'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = ['password_hash', 'password_plain'];
}

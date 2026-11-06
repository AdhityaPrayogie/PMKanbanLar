<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', fn ($user, string $id) => $user->id === $id);

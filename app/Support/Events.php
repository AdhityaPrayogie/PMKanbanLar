<?php

namespace App\Support;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Realtime push via Reverb: each user listens on private channel "user.{id}".
class Events
{
    /** Best-effort push: a Reverb outage must never fail the DB write that triggered it. */
    public static function broadcast(string $type, $data, array $userIds): void
    {
        try {
            Broadcast::on(array_map(fn ($id) => new PrivateChannel("user.$id"), array_values(array_unique(array_filter($userIds)))))
                ->as($type)->with($data)->sendNow();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Insert a notification row and push it to the user. */
    public static function notify(string $userId, string $title, string $message, string $type = 'system', ?string $related = null): void
    {
        $id = (string) Str::uuid();
        DB::insert('INSERT INTO notifications (id, user_id, title, message, type, related_task_id) VALUES (?, ?, ?, ?, ?, ?)',
            [$id, $userId, $title, $message, $type, $related]);
        self::broadcast('notification', ['id' => $id, 'user_id' => $userId, 'title' => $title, 'message' => $message,
            'type' => $type, 'related_task_id' => $related, 'is_read' => false, 'created_at' => now()->utc()->format('Y-m-d\TH:i:s.v\Z')], [$userId]);
    }
}

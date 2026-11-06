<?php

namespace App\Http\Controllers;

use App\Support\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// tasks/{id}/comments
class TaskCommentController extends Controller
{
    public function store(Request $request, string $id)
    {
        $u = $request->user();
        $b = self::body();
        $img = ($b['imageUrl'] ?? null) ?: (($b['image_url'] ?? null) ?: null);
        $text = trim((string) ($b['content'] ?? ''));
        if ($text === '' && ! $img) return self::err('Pesan teks atau gambar diperlukan', 400);
        $content = $text !== '' ? $text : '[Gambar]';
        $cid = (string) Str::uuid();
        DB::insert('INSERT INTO comments (id, task_id, user_id, content, image_url) VALUES (?, ?, ?, ?, ?)', [$cid, $id, $u->id, $content, $img]);
        $t = DB::selectOne('SELECT title, creator_id FROM tasks WHERE id = ?', [$id]);
        $to = array_unique(array_filter([$t?->creator_id, ...array_column(DB::select('SELECT user_id FROM task_assignees WHERE task_id = ?', [$id]), 'user_id')],
            fn ($x) => $x && $x !== $u->id));
        $title = $t?->title ?: 'Task';
        foreach ($to as $rid) Events::notify($rid, 'Komentar Baru pada Tugas', "{$u->name} mengomentari \"$title\": \"".mb_substr($content, 0, 60).'"', 'mention', $id);
        $c = DB::selectOne('SELECT c.id, c.task_id, c.user_id, c.content, c.image_url, c.created_at, u.name as user_name, u.email as user_email, u.avatar_url as user_avatar
       FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?', [$cid]);
        return response()->json(['success' => true, 'comment' => ['id' => $c->id, 'task_id' => $c->task_id, 'user_id' => $c->user_id, 'content' => $c->content,
            'image_url' => $c->image_url, 'created_at' => $c->created_at,
            'user' => ['id' => $c->user_id, 'name' => $c->user_name, 'email' => $c->user_email, 'avatar_url' => $c->user_avatar]]]);
    }
}

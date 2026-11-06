<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        // ponytail: once/day/user via cache; move to a daily scheduler if it gets heavy
        if (Cache::add("deadline:{$u->id}:".date('Y-m-d'), 1, 86400)) {
            DB::insert("INSERT INTO notifications (id, user_id, title, message, type, related_task_id)
     SELECT UUID(), ta.user_id, 'Mendekati Batas Waktu',
            CONCAT('Tugas \"', t.title, '\" mendekati batas tenggat waktu: ', DATE_FORMAT(t.due_date, '%d/%m/%Y'), '.'),
            'deadline', t.id
     FROM tasks t
     JOIN task_assignees ta ON t.id = ta.task_id
     JOIN board_columns c ON t.column_id = c.id
     WHERE ta.user_id = ?
       AND t.due_date IS NOT NULL
       AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
       AND LOWER(c.title) NOT LIKE '%done%'
       AND LOWER(c.title) NOT LIKE '%selesai%'
       AND NOT EXISTS (
         SELECT 1 FROM notifications n
         WHERE n.user_id = ta.user_id AND n.related_task_id = t.id
           AND n.type = 'deadline' AND DATE(n.created_at) = CURDATE()
       )", [$u->id]);
        }
        $n = DB::select('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30', [$u->id]);
        return response()->json(['notifications' => $n, 'unreadCount' => count(array_filter($n, fn ($x) => ! $x->is_read))]);
    }

    public function update(Request $request)
    {
        $u = $request->user();
        $b = self::body();
        if (self::truthy($b['markAll'] ?? null)) DB::update('UPDATE notifications SET is_read = TRUE WHERE user_id = ?', [$u->id]);
        elseif (self::truthy($b['id'] ?? null)) DB::update('UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?', [$b['id'], $u->id]);
        else return self::err('id or markAll required', 400);
        return response()->json(['success' => true]);
    }
}

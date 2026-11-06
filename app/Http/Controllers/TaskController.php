<?php

namespace App\Http\Controllers;

use App\Support\Events;
use App\Support\Kanban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// tasks (POST/PATCH/DELETE with body/query id), tasks/{id} (GET/PUT)
class TaskController extends Controller
{
    public function store(Request $request)
    {
        $u = $request->user();
        $b = self::body() + ['parentId' => null, 'description' => '', 'customer' => null, 'unit_tags' => null, 'priority' => 'Medium',
            'startDate' => null, 'dueDate' => null, 'assigneeIds' => [], 'labelIds' => []];
        if (! self::truthy($b['projectId'] ?? null) || ! self::truthy($b['columnId'] ?? null) || ! self::truthy($b['title'] ?? null))
            return self::err('projectId, columnId, and title are required', 400);
        $id = (string) Str::uuid();
        $title = $b['title'];
        $pos = DB::selectOne('SELECT COALESCE(MAX(position), 0) + 1000 AS nextPos FROM tasks WHERE column_id = ? AND (parent_id IS NULL OR parent_id = ?)',
            [$b['columnId'], self::truthy($b['parentId']) ? $b['parentId'] : ''])->nextPos ?: 1000;
        DB::insert('INSERT INTO tasks (id, project_id, column_id, parent_id, title, description, customer, unit_tags, priority, start_date, due_date, position, creator_id)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $id, $b['projectId'], $b['columnId'], $b['parentId'], $title, $b['description'],
            self::truthy($b['customer']) ? $b['customer'] : null, Kanban::tagsStr($b['unit_tags']), $b['priority'],
            self::truthy($b['startDate']) ? $b['startDate'] : null, self::truthy($b['dueDate']) ? $b['dueDate'] : null, $pos, $u->id,
        ]);
        foreach (is_array($b['assigneeIds']) ? $b['assigneeIds'] : [] as $uid) {
            DB::insert('INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE assigned_at=CURRENT_TIMESTAMP', [$id, $uid]);
            if ($uid !== $u->id) Events::notify($uid, 'New Task Assigned', "You were assigned to \"$title\".", 'assignment', $id);
        }
        foreach (is_array($b['labelIds']) ? $b['labelIds'] : [] as $lid) DB::insert('INSERT INTO task_labels (task_id, label_id) VALUES (?, ?)', [$id, $lid]);
        Events::notify($u->id, self::truthy($b['parentId']) ? 'Sub-task Dibuat' : 'Tugas Baru Dibuat',
            "Tugas \"$title\" berhasil ditambahkan ke papan kerja.", 'assignment', $id);

        $col = Kanban::colTitle($b['columnId']) ?: 'To Do';
        $changes = [['field' => 'Judul', 'new' => $title], ['field' => 'Prioritas', 'new' => $b['priority']], ['field' => 'Kolom', 'new' => $col]];
        if (self::truthy($b['customer'])) $changes[] = ['field' => 'Customer / Klien', 'new' => $b['customer']];
        $tags = $b['unit_tags'];
        if (is_array($tags) ? $tags : self::truthy($tags))
            $changes[] = ['field' => 'Unit Tags', 'new' => is_array($tags) ? implode(', ', array_map(fn ($t) => "#$t", $tags)) : $tags];
        Kanban::activity($id, $b['projectId'], $u->id, 'create_task', ['summary' => "Tugas dibuat di kolom \"$col\"", 'changes' => $changes]);

        return response()->json(['success' => true, 'taskId' => $id, 'id' => $id], 201);
    }

    public function patch(Request $request)
    {
        $u = $request->user();
        $b = self::body();
        $id = $b['id'] ?? null;
        if (! self::truthy($id)) return self::err('Task id is required', 400);
        $cur = DB::selectOne('SELECT * FROM tasks WHERE id = ?', [$id]);
        if (! $cur) return self::err('Task not found', 404);
        $has = fn ($k) => array_key_exists($k, $b);
        $orNull = fn ($v) => self::truthy($v) ? $v : null;

        $set = [];
        foreach (['column_id', 'position', 'title', 'description', 'priority'] as $f) if ($has($f)) $set[$f] = $b[$f];
        foreach (['customer', 'start_date', 'due_date', 'parent_id'] as $f) if ($has($f)) $set[$f] = $orNull($b[$f]);
        if ($has('unit_tags')) $set['unit_tags'] = Kanban::tagsStr($b['unit_tags']);
        if ($set) DB::table('tasks')->where('id', $id)->update($set);

        $changes = [];
        $t = $has('title') ? trim((string) $b['title']) : '';
        if ($t !== '' && $t !== $cur->title) $changes[] = ['field' => 'Judul', 'old' => $cur->title, 'new' => $t];
        if ($has('priority') && $b['priority'] !== $cur->priority) $changes[] = ['field' => 'Prioritas', 'old' => $cur->priority, 'new' => $b['priority']];
        if ($has('column_id') && $b['column_id'] !== $cur->column_id)
            $changes[] = ['field' => 'Status / Kolom', 'old' => Kanban::colTitle($cur->column_id) ?: 'Kolom Lama', 'new' => Kanban::colTitle($b['column_id']) ?: 'Kolom Baru'];
        if ($has('customer')) {
            [$o, $n] = [trim((string) $cur->customer), trim((string) ($b['customer'] ?: ''))];
            if ($o !== $n) $changes[] = ['field' => 'Customer / Klien', 'old' => $o ?: '-', 'new' => $n ?: '-'];
        }
        if ($has('description')) {
            [$o, $n] = [trim((string) $cur->description), trim((string) ($b['description'] ?: ''))];
            if ($o !== $n) $changes[] = ['field' => 'Deskripsi', 'old' => $o !== '' ? 'Tercatat' : 'Kosong', 'new' => $n !== '' ? 'Diperbarui' : 'Dikosongkan'];
        }
        foreach (['start_date' => 'Tanggal Mulai', 'due_date' => 'Tenggat Waktu'] as $f => $label) {
            if (! $has($f)) continue;
            [$o, $n] = [substr((string) $cur->$f, 0, 10), substr((string) ($b[$f] ?: ''), 0, 10)];
            if ($o !== $n) $changes[] = ['field' => $label, 'old' => $o ?: '-', 'new' => $n ?: '-'];
        }
        if ($has('unit_tags')) {
            $raw = (string) $cur->unit_tags;
            $old = str_starts_with($raw, '[') ? (json_decode($raw, true) ?? []) : array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
            $new = is_array($b['unit_tags']) ? $b['unit_tags'] : [];
            sort($old);
            sort($new);
            [$os, $ns] = [implode(', ', $old), implode(', ', $new)];
            $hash = fn ($l) => implode(', ', array_map(fn ($x) => "#$x", $l));
            if ($os !== $ns) $changes[] = ['field' => 'Unit Tags', 'old' => $os ? $hash($old) : '-', 'new' => $ns ? $hash($new) : '-'];
        }

        if (is_array($ids = $b['assignee_ids'] ?? null)) {
            $existing = DB::select('SELECT ta.user_id, u.name FROM task_assignees ta LEFT JOIN users u ON ta.user_id = u.id WHERE ta.task_id = ?', [$id]);
            $exIds = array_column($existing, 'user_id');
            $added = array_values(array_filter($ids, fn ($x) => ! in_array($x, $exIds, true)));
            $removed = array_filter($existing, fn ($r) => ! in_array($r->user_id, $ids, true));
            if ($added || $removed) {
                $addedNames = $added ? implode(', ', array_column(DB::select('SELECT name FROM users WHERE id IN ('.self::placeholders($added).')', $added), 'name')) : '';
                $removedNames = implode(', ', array_column($removed, 'name'));
                $changes[] = array_filter(['field' => 'Penugasan Tim',
                    'old' => $removedNames ? "Dihapus: $removedNames" : null, 'new' => $addedNames ? "Ditugaskan: $addedNames" : null]);
            }
            DB::delete('DELETE FROM task_assignees WHERE task_id = ?', [$id]);
            foreach ($ids as $uid) {
                DB::insert('INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)', [$id, $uid]);
                if (! in_array($uid, $exIds, true)) Events::notify($uid, 'Penugasan Tugas', "Anda telah ditugaskan ke tugas \"{$cur->title}\".", 'assignment', $id);
            }
        }

        if (self::truthy($b['column_id'] ?? null) && $b['column_id'] !== $cur->column_id) {
            $col = Kanban::colTitle($b['column_id']) ?: 'Kolom Baru';
            $to = array_filter(array_unique([$cur->creator_id, ...array_column(DB::select('SELECT user_id FROM task_assignees WHERE task_id = ?', [$id]), 'user_id')]));
            foreach ($to as $rid) Events::notify($rid, 'Perubahan Kolom Tugas', "Tugas \"{$cur->title}\" telah dipindahkan ke kolom \"$col\".", 'system', $id);
        }

        if ($changes) {
            $move = count($changes) === 1 && $changes[0]['field'] === 'Status / Kolom';
            Kanban::activity($id, $cur->project_id, $u->id, $move ? 'move_task' : 'update_task', ['summary' => $move
                ? "Status tugas dipindahkan dari \"{$changes[0]['old']}\" ke \"{$changes[0]['new']}\""
                : 'Perubahan: '.implode(', ', array_column($changes, 'field')), 'changes' => $changes]);
        }
        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $id = $request->query('id');
        if (! self::truthy($id)) return self::err('Task id is required', 400);
        if ($t = DB::selectOne('SELECT title, creator_id FROM tasks WHERE id = ?', [$id]))
            Events::notify($t->creator_id, 'Tugas Telah Dihapus', "Tugas \"{$t->title}\" telah berhasil dihapus dari papan.");
        DB::delete('DELETE FROM tasks WHERE id = ?', [$id]);
        return response()->json(['success' => true]);
    }

    public function show(string $id)
    {
        $task = DB::selectOne('SELECT * FROM tasks WHERE id = ?', [$id]);
        if (! $task) return self::err('Task not found', 404);
        $task->unit_tags = Kanban::parseTags($task->unit_tags);
        $task->assignees = DB::select('SELECT u.id, u.name, u.email, u.avatar_url FROM task_assignees ta JOIN users u ON ta.user_id = u.id WHERE ta.task_id = ?', [$id]);
        $task->labels = DB::select('SELECT l.id, l.project_id, l.name, l.color FROM task_labels tl JOIN labels l ON tl.label_id = l.id WHERE tl.task_id = ?', [$id]);
        $task->sub_tasks = DB::select('SELECT * FROM tasks WHERE parent_id = ? ORDER BY position ASC', [$id]);
        $task->attachments = array_map(fn ($a) => array_filter([
            'id' => $a->id, 'task_id' => $a->task_id, 'file_name' => $a->file_name, 'file_url' => $a->file_url, 'file_size' => $a->file_size,
            'file_type' => $a->file_type, 'uploaded_by' => $a->uploaded_by, 'created_at' => $a->created_at,
            'uploader' => $a->uploaded_by ? ['id' => $a->uploaded_by, 'name' => $a->uploader_name, 'email' => $a->uploader_email, 'avatar_url' => $a->uploader_avatar] : null,
        ], fn ($v, $k) => $k !== 'uploader' || $v, ARRAY_FILTER_USE_BOTH), DB::select('SELECT a.id, a.task_id, a.file_name, a.file_url, a.file_size, a.file_type, a.uploaded_by, a.created_at,
              u.name as uploader_name, u.email as uploader_email, u.avatar_url as uploader_avatar
       FROM task_attachments a LEFT JOIN users u ON a.uploaded_by = u.id WHERE a.task_id = ? ORDER BY a.created_at DESC', [$id]));

        $comments = array_map(fn ($c) => ['id' => $c->id, 'task_id' => $c->task_id, 'user_id' => $c->user_id, 'content' => $c->content,
            'image_url' => $c->image_url, 'created_at' => $c->created_at, 'updated_at' => $c->updated_at,
            'user' => ['id' => $c->user_id, 'name' => $c->user_name, 'email' => $c->user_email, 'avatar_url' => $c->user_avatar]],
            DB::select('SELECT c.id, c.task_id, c.user_id, c.content, c.image_url, c.created_at, c.updated_at,
              u.name as user_name, u.email as user_email, u.avatar_url as user_avatar
       FROM comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC', [$id]));

        $activities = array_map(function ($a) {
            $d = is_string($a->details) ? json_decode($a->details, true) : $a->details;
            if (! is_array($d)) $d = ['summary' => $a->details ?: '', 'changes' => []];
            return ['id' => $a->id, 'task_id' => $a->task_id, 'project_id' => $a->project_id, 'user_id' => $a->user_id, 'action' => $a->action,
                'details' => $d, 'created_at' => $a->created_at,
                'user' => ['id' => $a->user_id, 'name' => $a->user_name ?: 'Pengguna', 'avatar_url' => $a->user_avatar, 'role' => $a->user_role, 'unit_name' => $a->user_unit]];
        }, DB::select('SELECT a.*, u.name as user_name, u.avatar_url as user_avatar, u.role as user_role, u.unit_name as user_unit
       FROM task_activities a LEFT JOIN users u ON a.user_id = u.id
       WHERE a.task_id = ? OR (a.task_id IS NULL AND a.project_id = ?) ORDER BY a.created_at DESC', [$id, $task->project_id]));

        // Backfill a missing create_task entry for tasks created before activity logging existed
        if (! in_array('create_task', array_column($activities, 'action'), true) && $task->creator_id) {
            try {
                $c = DB::selectOne('SELECT name, avatar_url, role, unit_name FROM users WHERE id = ?', [$task->creator_id]);
                $d = ['summary' => "Tugas \"{$task->title}\" dibuat", 'changes' => [['field' => 'Judul', 'new' => $task->title], ['field' => 'Prioritas', 'new' => $task->priority]]];
                $actId = 'act_'.substr($task->id, 0, 8).'_'.base_convert((string) (int) (microtime(true) * 1000), 10, 36);
                $at = $task->created_at ?: now()->format('Y-m-d H:i:s');
                DB::insert("INSERT INTO task_activities (id, task_id, project_id, user_id, action, details, created_at) VALUES (?, ?, ?, ?, 'create_task', ?, ?)",
                    [$actId, $id, $task->project_id, $task->creator_id, json_encode($d, Kanban::J), $at]);
                $activities[] = ['id' => $actId, 'task_id' => $id, 'project_id' => $task->project_id, 'user_id' => $task->creator_id, 'action' => 'create_task',
                    'details' => $d, 'created_at' => $at,
                    'user' => ['id' => $task->creator_id, 'name' => $c?->name ?: 'Admin', 'avatar_url' => $c?->avatar_url, 'role' => $c?->role, 'unit_name' => $c?->unit_name]];
            } catch (\Throwable $e) { report($e); }
        }
        return response()->json(['task' => $task, 'comments' => $comments, 'activities' => $activities]);
    }

    public function update(string $id)
    {
        $b = self::body();
        $or = fn ($k) => self::truthy($b[$k] ?? null) ? $b[$k] : null;
        DB::update('UPDATE tasks SET title = COALESCE(?, title), description = ?, customer = ?, unit_tags = ?, priority = COALESCE(?, priority),
           column_id = COALESCE(?, column_id), start_date = ?, due_date = ? WHERE id = ?', [
            $or('title'), $b['description'] ?? null, $b['customer'] ?? null, Kanban::tagsStr($b['unit_tags'] ?? null),
            $or('priority'), $or('column_id'), $or('start_date'), $or('due_date'), $id,
        ]);
        if (is_array($b['assignee_ids'] ?? null)) {
            DB::delete('DELETE FROM task_assignees WHERE task_id = ?', [$id]);
            foreach ($b['assignee_ids'] as $uid) DB::insert('INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)', [$id, $uid]);
        }
        if (is_array($b['label_ids'] ?? null)) {
            DB::delete('DELETE FROM task_labels WHERE task_id = ?', [$id]);
            foreach ($b['label_ids'] as $lid) DB::insert('INSERT INTO task_labels (task_id, label_id) VALUES (?, ?)', [$id, $lid]);
        }
        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Shared board / task helpers (port of the Next.js route helpers).
class Kanban
{
    const J = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    const PROJECTS_SQL = "SELECT p.*,
        COALESCE(p.unit_tag, 'IT') as unit_tag,
        COALESCE(p.stage, 'Active') as stage,
        COALESCE(p.position, 1000) as position,
        (SELECT COUNT(*) FROM board_columns bc WHERE bc.project_id = p.id) as columns_count,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_count
       FROM projects p
       ORDER BY p.position ASC, p.created_at ASC";

    /** tasks.unit_tags is JSON or a legacy comma list. */
    public static function parseTags($v): array
    {
        if (! $v) return [];
        if (is_array($v)) return $v;
        $j = json_decode((string) $v, true);
        if (json_last_error() === JSON_ERROR_NONE) return is_array($j) ? $j : [];
        return array_values(array_filter(array_map('trim', explode(',', (string) $v)), 'strlen'));
    }

    public static function tagsStr($v): ?string
    {
        return is_array($v) ? json_encode($v, self::J) : (is_string($v) ? $v : null);
    }

    /** Activity log is best-effort: never fail the request over it. */
    public static function activity(?string $taskId, $projectId, string $userId, string $action, array $details): void
    {
        try {
            DB::insert('INSERT INTO task_activities (id, task_id, project_id, user_id, action, details, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [(string) Str::uuid(), $taskId, $projectId, $userId, $action, json_encode($details, self::J)]);
        } catch (\Throwable $e) { report($e); }
    }

    public static function colTitle($id): ?string
    {
        return DB::selectOne('SELECT title FROM board_columns WHERE id = ?', [$id])?->title;
    }

    public static function taskProject(string $taskId): ?string
    {
        return DB::selectOne('SELECT project_id FROM tasks WHERE id = ?', [$taskId])?->project_id;
    }
}

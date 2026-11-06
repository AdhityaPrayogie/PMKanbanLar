<?php

namespace App\Http\Controllers;

use App\Support\Kanban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// GET board: one project with its columns, task tree, assignees, labels
class BoardController extends Controller
{
    public function __invoke(Request $request)
    {
        $projects = DB::select(Kanban::PROJECTS_SQL);
        if (! $projects) return self::err('No projects found', 404);
        $pid = $request->query('projectId');
        $current = $projects[0];
        foreach ($projects as $p) if ($pid && $p->id === $pid) { $current = $p; break; }
        $pid = $current->id;

        $columns = DB::select('SELECT * FROM board_columns WHERE project_id = ? ORDER BY position ASC', [$pid]);
        $tasks = DB::select('SELECT t.*, (SELECT COUNT(*) FROM comments c WHERE c.task_id = t.id) as comments_count FROM tasks t WHERE t.project_id = ? ORDER BY t.position ASC', [$pid]);
        $assignees = DB::select('SELECT ta.task_id, u.id, u.name, u.email, u.avatar_url FROM task_assignees ta JOIN users u ON ta.user_id = u.id JOIN tasks t ON ta.task_id = t.id WHERE t.project_id = ?', [$pid]);
        $labels = DB::select('SELECT tl.task_id, l.id, l.name, l.color, l.project_id FROM task_labels tl JOIN labels l ON tl.label_id = l.id WHERE l.project_id = ?', [$pid]);
        $master = DB::select('SELECT * FROM labels WHERE project_id = ? ORDER BY name ASC', [$pid]);
        try {
            $att = DB::select('SELECT a.task_id, COUNT(*) as count FROM task_attachments a JOIN tasks t ON a.task_id = t.id WHERE t.project_id = ? GROUP BY a.task_id', [$pid]);
        } catch (\Throwable) { $att = []; }

        // objects are handles: sub-tasks appear both nested and in allTasks, like the JS version
        $map = [];
        foreach ($tasks as $t) {
            $t->unit_tags = Kanban::parseTags($t->unit_tags);
            $t->assignees = $t->labels = $t->sub_tasks = [];
            $map[$t->id] = $t;
        }
        foreach ($assignees as $a) if (isset($map[$a->task_id])) $map[$a->task_id]->assignees[] = ['id' => $a->id, 'name' => $a->name, 'email' => $a->email, 'avatar_url' => $a->avatar_url];
        foreach ($labels as $l) if (isset($map[$l->task_id])) $map[$l->task_id]->labels[] = ['id' => $l->id, 'project_id' => $l->project_id, 'name' => $l->name, 'color' => $l->color];
        foreach ($att as $a) if (isset($map[$a->task_id])) $map[$a->task_id]->attachments_count = (int) $a->count;
        $parents = [];
        foreach ($map as $t) {
            if (! $t->parent_id) $parents[] = $t;
            elseif (isset($map[$t->parent_id])) $map[$t->parent_id]->sub_tasks[] = $t;
        }
        foreach ($columns as $c) $c->tasks = array_values(array_filter($parents, fn ($t) => $t->column_id === $c->id));

        return response()->json(['project' => $current, 'projects' => $projects, 'columns' => $columns,
            'allTasks' => array_values($map), 'masterLabels' => $master]);
    }
}

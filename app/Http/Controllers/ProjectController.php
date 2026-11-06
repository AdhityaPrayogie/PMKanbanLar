<?php

namespace App\Http\Controllers;

use App\Support\Events;
use App\Support\Kanban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// units = boards (projects table)
class ProjectController extends Controller
{
    public function index()
    {
        return response()->json(['units' => DB::select(Kanban::PROJECTS_SQL)]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        $b = self::body();
        $name = trim((string) ($b['name'] ?? ''));
        if ($name === '') return self::err('Nama board/unit wajib diisi', 400);
        $tag = $b['unit_tag'] ?? 'IT';
        $stage = $b['stage'] ?? 'Active';
        $desc = trim((string) ($b['description'] ?? '')) ?: null;
        $id = 'board_'.substr((string) Str::uuid(), 0, 8);
        DB::insert('INSERT INTO projects (id, name, description, owner_id, unit_tag, stage, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1000, NOW(), NOW())',
            [$id, $name, $desc, $u->id, $tag, $stage]);
        foreach (['To Do', 'In Progress', 'In Review', 'Done'] as $i => $t) {
            DB::insert('INSERT INTO board_columns (id, project_id, title, position, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
                [$id.'_col'.($i + 1), $id, $t, ($i + 1) * 1000]);
        }
        Events::notify($u->id, 'Papan Kerja Baru Dibuat', "Board \"$name\" (Tag: #$tag) berhasil dibuat pada tahap $stage.");
        Kanban::activity(null, $id, $u->id, 'create_board', ['summary' => "Papan kerja \"$name\" dibuat", 'changes' => [
            ['field' => 'Nama Board', 'new' => $name], ['field' => 'Tag Unit', 'new' => "#$tag"], ['field' => 'Tahap', 'new' => $stage],
        ]]);
        return response()->json(['success' => true, 'unit' => ['id' => $id, 'name' => $name, 'description' => $desc, 'owner_id' => $u->id, 'unit_tag' => $tag, 'stage' => $stage]]);
    }

    public function update()
    {
        $b = self::body();
        if (! self::truthy($b['id'] ?? null)) return self::err('Board ID wajib disertakan', 400);
        $set = array_intersect_key($b, array_flip(['stage', 'position', 'name', 'description', 'unit_tag']));
        foreach (['name', 'description'] as $f) if (isset($set[$f])) $set[$f] = trim((string) $set[$f]);
        if ($set) {
            $cols = implode(', ', array_map(fn ($k) => "$k = ?", array_keys($set)));
            DB::update("UPDATE projects SET $cols, updated_at = NOW() WHERE id = ?", [...array_values($set), $b['id']]);
        }
        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $u = $request->user();
        $id = $request->query('id');
        if (! self::truthy($id)) return self::err('Unit ID wajib diisi', 400);
        $owner = DB::selectOne('SELECT owner_id FROM projects WHERE id = ?', [$id]);
        if ($owner && $owner->owner_id !== $u->id && $u->role !== 'admin') return self::err('Hanya pemilik board atau admin yang bisa menghapus', 403);
        if ((int) DB::selectOne('SELECT COUNT(*) as count FROM projects')->count <= 1) return self::err('Minimal harus ada 1 unit/divisi di sistem', 400);
        DB::delete('DELETE FROM projects WHERE id = ?', [$id]);
        return response()->json(['success' => true]);
    }
}

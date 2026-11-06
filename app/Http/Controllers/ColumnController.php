<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// columns (board_columns)
class ColumnController extends Controller
{
    public function store()
    {
        $b = self::body();
        if (! self::truthy($b['projectId'] ?? null) || ! self::truthy($b['title'] ?? null)) return self::err('projectId and title are required', 400);
        $id = (string) Str::uuid();
        $pos = DB::selectOne('SELECT COALESCE(MAX(position), 0) + 1000 AS nextPos FROM board_columns WHERE project_id = ?', [$b['projectId']])->nextPos ?: 1000;
        $title = trim((string) $b['title']);
        DB::insert('INSERT INTO board_columns (id, project_id, title, position) VALUES (?, ?, ?, ?)', [$id, $b['projectId'], $title, $pos]);
        return response()->json(['success' => true, 'column' => ['id' => $id, 'project_id' => $b['projectId'], 'title' => $title, 'position' => $pos, 'tasks' => []]]);
    }

    public function update()
    {
        $b = self::body();
        if (! self::truthy($b['id'] ?? null)) return self::err('Column id is required', 400);
        $set = array_intersect_key($b, array_flip(['title', 'position']));
        if (! $set) return response()->json(['message' => 'No updates provided']);
        DB::table('board_columns')->where('id', $b['id'])->update($set);
        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $id = $request->query('id');
        if (! self::truthy($id)) return self::err('Column id is required', 400);
        DB::delete('DELETE FROM board_columns WHERE id = ?', [$id]);
        return response()->json(['success' => true]);
    }
}

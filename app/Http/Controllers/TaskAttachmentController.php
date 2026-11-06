<?php

namespace App\Http\Controllers;

use App\Support\Kanban;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// tasks/{id}/attachments
class TaskAttachmentController extends Controller
{
    const CATEGORIES = [
        'image' => ['png', 'jpg', 'jpeg', 'webp', 'gif'], 'doc' => ['doc', 'docx'], 'pdf' => ['pdf'], 'md' => ['md', 'markdown'],
        'excel' => ['xls', 'xlsx', 'csv'], 'zip' => ['zip', 'rar', '7z', 'tar', 'gz'],
    ];

    public function store(Request $request, string $id)
    {
        $u = $request->user();
        $f = $request->file('file');
        if (! $f) return self::err('Berkas tidak ditemukan', 400);
        $name = $f->getClientOriginalName();
        $ext = str_contains($name, '.') ? strtolower(substr($name, strrpos($name, '.') + 1)) : '';
        $cat = null;
        foreach (self::CATEGORIES as $k => $exts) if (in_array($ext, $exts, true)) { $cat = $k; break; }
        if (! $cat) return self::err('Format berkas tidak didukung! Hanya diperbolehkan upload Gambar (IMG), Dokumen (DOC/DOCX), PDF (.pdf), Markdown (.md), Excel (XLS/XLSX/CSV), dan Arsip ZIP (ZIP/RAR/7Z).', 400);
        if ($f->getSize() > 50 * 1024 * 1024) return self::err('Ukuran berkas melebihi batas maksimal 50 MB', 400);
        try {
            [$url, $orig, $size, $compressed] = Uploads::store($f, 'attachments', 2048, 82);
            $aid = (string) Str::uuid();
            DB::insert('INSERT INTO task_attachments (id, task_id, file_name, file_url, file_size, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$aid, $id, $name, $url, $size, $cat, $u->id]);
            Kanban::activity($id, Kanban::taskProject($id), $u->id, 'upload_attachment',
                ['summary' => "Mengunggah berkas lampiran \"$name\"", 'changes' => [['field' => 'Lampiran Berkas', 'new' => $name]]]);
        } catch (\Throwable $e) {
            report($e);
            return self::err($e->getMessage() ?: 'Gagal mengunggah berkas', 500);
        }
        return response()->json(['success' => true,
            'attachment' => ['id' => $aid, 'task_id' => $id, 'file_name' => $name, 'file_url' => $url, 'file_size' => $size, 'file_type' => $cat,
                'uploaded_by' => $u->id, 'uploader' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'avatar_url' => $u->avatar_url],
                'created_at' => now()->utc()->format('Y-m-d\TH:i:s.v\Z')],
            'compression' => ['wasCompressed' => $compressed, 'originalSize' => $orig, 'finalSize' => $size,
                'savedPercent' => $compressed ? (int) round(($orig - $size) / $orig * 100) : 0]]);
    }

    public function destroy(Request $request, string $id)
    {
        $aid = $request->query('attachmentId');
        if (! self::truthy($aid)) return self::err('attachmentId diperlukan', 400);
        try {
            if ($r = DB::selectOne('SELECT file_name, file_url FROM task_attachments WHERE id = ? AND task_id = ?', [$aid, $id])) {
                @unlink(public_path(ltrim($r->file_url, '/')));
                DB::delete('DELETE FROM task_attachments WHERE id = ?', [$aid]);
                Kanban::activity($id, Kanban::taskProject($id), $request->user()->id, 'delete_attachment',
                    ['summary' => "Menghapus berkas lampiran \"{$r->file_name}\"", 'changes' => [['field' => 'Lampiran Berkas', 'old' => $r->file_name, 'new' => 'Dihapus']]]);
            }
        } catch (\Throwable $e) {
            report($e);
            return self::err($e->getMessage() ?: 'Gagal menghapus berkas', 500);
        }
        return response()->json(['success' => true]);
    }
}

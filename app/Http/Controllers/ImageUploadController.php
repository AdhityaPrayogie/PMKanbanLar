<?php

namespace App\Http\Controllers;

use App\Support\Uploads;
use Illuminate\Http\Request;

// upload/image (chat & comment images)
class ImageUploadController extends Controller
{
    public function __invoke(Request $request)
    {
        $f = $request->file('file');
        if (! $f) return self::err('Berkas gambar tidak ditemukan', 400);
        if (! in_array(strtolower($f->getClientOriginalExtension()), ['png', 'jpg', 'jpeg', 'webp', 'gif'], true))
            return self::err('Hanya format gambar (PNG, JPG, WEBP, GIF) yang diperbolehkan di diskusi dan chat!', 400);
        if ($f->getSize() > 20 * 1024 * 1024) return self::err('Ukuran gambar maksimal 20 MB', 400);
        try {
            [$url, $orig, $size, $compressed] = Uploads::store($f, 'chat', 1600, 80);
        } catch (\Throwable $e) {
            report($e);
            return self::err($e->getMessage() ?: 'Gagal mengunggah gambar', 500);
        }
        return response()->json(['success' => true, 'url' => $url, 'fileName' => $f->getClientOriginalName(),
            'originalSize' => $orig, 'finalSize' => $size, 'wasCompressed' => $compressed]);
    }
}

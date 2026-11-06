<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class Uploads
{
    /** Save under public/uploads/$dir, recompressing jpg/png/webp with GD. Returns [url, originalSize, finalSize, wasCompressed]. */
    public static function store(UploadedFile $file, string $dir, int $maxDim, int $quality): array
    {
        $data = file_get_contents($file->getRealPath());
        $orig = strlen($data);
        $ext = strtolower($file->getClientOriginalExtension());
        $final = $data;
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagecreatefromstring')) {
            try {
                if ($img = @imagecreatefromstring($data)) {
                    [$w, $h] = [imagesx($img), imagesy($img)];
                    if ($w > $maxDim || $h > $maxDim) {
                        $img = imagescale($img, $w >= $h ? $maxDim : (int) round($w * $maxDim / $h), $w >= $h ? (int) round($h * $maxDim / $w) : $maxDim);
                    }
                    ob_start();
                    match ($ext) {
                        'png' => (imagesavealpha($img, true) && imagepng($img, null, 8)),
                        'webp' => imagewebp($img, null, $quality),
                        default => imagejpeg($img, null, $quality),
                    };
                    $out = ob_get_clean();
                    if ($out && strlen($out) < $orig) $final = $out;
                }
            } catch (\Throwable) { $final = $data; }
        }
        $name = (int) (microtime(true) * 1000).'-'.substr((string) Str::uuid(), 0, 8).'-'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        @mkdir(public_path("uploads/$dir"), 0777, true);
        file_put_contents(public_path("uploads/$dir/$name"), $final);
        return ["/uploads/$dir/$name", $orig, strlen($final), $final !== $data];
    }
}

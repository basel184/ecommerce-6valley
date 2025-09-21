<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    // Serve files from storage/app/public safely without requiring a symlink
    public function publicFile(string $path)
    {
        // Normalize and validate path
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $fullPath = storage_path('app/public/' . $path);

        // Must be an actual file
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            abort(404);
        }

        $mime = @mime_content_type($fullPath) ?: 'application/octet-stream';

        return new StreamedResponse(function () use ($fullPath) {
            $fp = @fopen($fullPath, 'rb');
            if ($fp !== false) {
                fpassthru($fp);
                fclose($fp);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}

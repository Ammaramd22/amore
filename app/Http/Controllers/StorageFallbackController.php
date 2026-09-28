<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves files from storage/app/public when public/storage symlink is missing
 * (common on cPanel / shared hosting after upload).
 */
class StorageFallbackController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        $path = str_replace(['..', "\0"], '', $path);
        $path = ltrim($path, '/');
        $full = storage_path('app/public/'.$path);

        if ($path === '' || ! is_file($full)) {
            abort(404);
        }

        $realBase = realpath(storage_path('app/public'));
        $realFile = realpath($full);
        if (! $realBase || ! $realFile || ! str_starts_with($realFile, $realBase.DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        return response()->file($realFile);
    }
}

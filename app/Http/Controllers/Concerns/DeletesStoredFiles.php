<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Storage;

trait DeletesStoredFiles
{
    protected function deleteStoredFile(?string $path, string $resource): void
    {
        $disk = Storage::disk('local');

        if ($path && $disk->exists($path) && ! $disk->delete($path)) {
            throw new \RuntimeException("Lampiran {$resource} gagal dihapus.");
        }
    }
}

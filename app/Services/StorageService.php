<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    /**
     * Get the configured disk for file storage.
     */
    private function getDisk(): string
    {
        $defaultDisk = config('filesystems.default', 'public');

        return $defaultDisk === 'local' ? 'public' : $defaultDisk;
    }

    /**
     * Upload a file to the configured disk.
     */
    public function upload(UploadedFile $file, string $directory = 'products'): string
    {
        return $file->store($directory, $this->getDisk());
    }

    /**
     * Delete a file from the configured disk.
     */
    public function delete(string $path): void
    {
        $disk = $this->getDisk();
        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    /**
     * Get the public URL for a file.
     */
    public function url(string $path): string
    {
        return Storage::disk($this->getDisk())->url($path);
    }
}

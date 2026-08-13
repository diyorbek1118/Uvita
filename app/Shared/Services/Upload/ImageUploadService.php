<?php

declare(strict_types=1);

namespace App\Shared\Services\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ImageUploadService
{
    /**
     * Rasmni `public` diskka saqlaydi va to'liq URL qaytaradi.
     */
    public function store(UploadedFile $file, string $directory = 'products'): string
    {
        $name = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $disk = (string) config('uploads.disk', 'public');

        $path = $file->storeAs($directory, $name, $disk);

        return Storage::disk($disk)->url($path);
    }
}

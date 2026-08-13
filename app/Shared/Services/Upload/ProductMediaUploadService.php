<?php

declare(strict_types=1);

namespace App\Shared\Services\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ProductMediaUploadService
{
    /** @param array<int, UploadedFile> $images */
    public function storeImages(array $images): array
    {
        return array_map(fn (UploadedFile $image): string => $this->store($image, 'products/images'), $images);
    }

    public function storeVideo(UploadedFile $video): string
    {
        return $this->store($video, 'products/videos');
    }

    private function store(UploadedFile $file, string $directory): string
    {
        $disk = (string) config('uploads.disk', 'public');
        $name = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs($directory, $name, $disk);

        return Storage::disk($disk)->url($path);
    }
}

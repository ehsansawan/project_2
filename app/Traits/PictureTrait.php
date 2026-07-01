<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait PictureTrait
{
    public static function storePicture(UploadedFile $file, string $directory = 'uploads'): string
    {
        $filename = time() . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // يرجع المسار النسبي فقط (مثال: complains/images/123_abc.jpg)
        return $file->storeAs($directory, $filename, 'public');
    }

    public static function destroyPicture(?string $path): void
    {
        if (!$path) {
            return;
        }
        // الحذف باستخدام المسار النسبي مباشرة
        Storage::disk('public')->delete($path);
    }

    public static function updatePicture(?UploadedFile $file, ?string $oldPath, string $directory = 'uploads'): ?string
    {
        if (!$file) {
            return $oldPath;
        }

        self::destroyPicture($oldPath);
        return self::storePicture($file, $directory);
    }
}
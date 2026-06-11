<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    /**
     * Resolve the URL/path of a specific thumbnail size based on the optimized image path.
     *
     * @param string|null $path The optimized image path.
     * @param string $size 'small', 'medium', or 'large'.
     * @return string|null Resolved path or null.
     */
    public static function thumb(?string $path, string $size = 'medium'): ?string
    {
        if (blank($path)) {
            return null;
        }

        // Check if it's already an absolute URL
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_contains($path, '/optimized/')) {
            $thumbnail = str_replace('/optimized/', "/thumbnails/{$size}/", $path);

            if (Storage::disk('public')->exists($thumbnail)) {
                return $thumbnail;
            }

            if (Storage::disk('public')->exists($path)) {
                return $path;
            }

            $original = self::matchingOriginalPath($path);

            if ($original) {
                return $original;
            }
        }

        return $path;
    }

    private static function matchingOriginalPath(string $optimizedPath): ?string
    {
        $originalDirectory = dirname(str_replace('/optimized/', '/original/', $optimizedPath));
        $filename = pathinfo($optimizedPath, PATHINFO_FILENAME);

        foreach (Storage::disk('public')->files($originalDirectory) as $file) {
            if (pathinfo($file, PATHINFO_FILENAME) === $filename) {
                return $file;
            }
        }

        return null;
    }
}

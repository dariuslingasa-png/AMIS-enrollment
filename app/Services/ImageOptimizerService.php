<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageOptimizerService
{
    /**
     * Optimize an uploaded image: convert to WebP, compress, and generate thumbnails.
     *
     * @param UploadedFile $file The uploaded file.
     * @param string $disk The storage disk to use.
     * @param string $directory The relative target directory.
     * @param string $filename Without extension.
     * @return array Paths to all generated versions.
     */
    public function optimize(UploadedFile $file, string $disk, string $directory, string $filename): array
    {
        $mimeType = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        
        $originalName = "{$filename}.{$extension}";
        $webpName = "{$filename}.webp";

        $originalPath = "{$directory}/original/{$originalName}";
        $optimizedPath = "{$directory}/optimized/{$webpName}";
        $thumbSmallPath = "{$directory}/thumbnails/small/{$webpName}";
        $thumbMediumPath = "{$directory}/thumbnails/medium/{$webpName}";
        $thumbLargePath = "{$directory}/thumbnails/large/{$webpName}";

        // Save original file
        Storage::disk($disk)->putFileAs("{$directory}/original", $file, $originalName);

        // Get absolute path to original file for local ImageMagick execution
        $absoluteOriginal = Storage::disk($disk)->path($originalPath);
        
        // Ensure absolute paths exist for output directories
        $optimizedDir = Storage::disk($disk)->path("{$directory}/optimized");
        $thumbnailsDir = Storage::disk($disk)->path("{$directory}/thumbnails");
        
        if (!is_dir($optimizedDir)) {
            mkdir($optimizedDir, 0755, true);
        }
        if (!is_dir("{$thumbnailsDir}/small")) {
            mkdir("{$thumbnailsDir}/small", 0755, true);
        }
        if (!is_dir("{$thumbnailsDir}/medium")) {
            mkdir("{$thumbnailsDir}/medium", 0755, true);
        }
        if (!is_dir("{$thumbnailsDir}/large")) {
            mkdir("{$thumbnailsDir}/large", 0755, true);
        }

        $absoluteOptimized = Storage::disk($disk)->path($optimizedPath);
        $absoluteSmall = Storage::disk($disk)->path($thumbSmallPath);
        $absoluteMedium = Storage::disk($disk)->path($thumbMediumPath);
        $absoluteLarge = Storage::disk($disk)->path($thumbLargePath);

        // 1. Convert and compress optimized main image
        $optimizedCreated = $this->runCommand([
            'magick',
            $absoluteOriginal,
            '-quality', '85',
            $absoluteOptimized
        ]) && Storage::disk($disk)->exists($optimizedPath);

        // 2. Generate Thumbnails (crop center to ensure square profiles)
        $smallCreated = $this->runCommand([
            'magick',
            $absoluteOriginal,
            '-thumbnail', '150x150^',
            '-gravity', 'center',
            '-extent', '150x150',
            '-quality', '80',
            $absoluteSmall
        ]) && Storage::disk($disk)->exists($thumbSmallPath);

        $mediumCreated = $this->runCommand([
            'magick',
            $absoluteOriginal,
            '-thumbnail', '300x300^',
            '-gravity', 'center',
            '-extent', '300x300',
            '-quality', '80',
            $absoluteMedium
        ]) && Storage::disk($disk)->exists($thumbMediumPath);

        $largeCreated = $this->runCommand([
            'magick',
            $absoluteOriginal,
            '-thumbnail', '600x600^',
            '-gravity', 'center',
            '-extent', '600x600',
            '-quality', '80',
            $absoluteLarge
        ]) && Storage::disk($disk)->exists($thumbLargePath);

        $displayPath = $optimizedCreated ? $optimizedPath : $originalPath;

        return [
            'original' => $originalPath,
            'optimized' => $displayPath,
            'small' => $smallCreated ? $thumbSmallPath : $displayPath,
            'medium' => $mediumCreated ? $thumbMediumPath : $displayPath,
            'large' => $largeCreated ? $thumbLargePath : $displayPath,
        ];
    }

    /**
     * Check if a file type is optimizable (images only).
     *
     * @param string|null $mimeType
     * @return bool
     */
    public function isOptimizable(?string $mimeType): bool
    {
        if (blank($mimeType)) {
            return false;
        }
        return str_starts_with(strtolower($mimeType), 'image/') && !str_contains(strtolower($mimeType), 'svg');
    }

    /**
     * Run a system command securely.
     *
     * @param array $args
     * @return bool
     */
    private function runCommand(array $args): bool
    {
        if (!function_exists('exec')) {
            Log::warning("exec() function is disabled. ImageMagick command skipped.");
            return false;
        }

        try {
            $escapedArgs = array_map('escapeshellarg', $args);
            $command = implode(' ', $escapedArgs);
            
            $resultCode = -1;
            $output = [];
            @exec($command . ' 2>&1', $output, $resultCode);

            if ($resultCode !== 0) {
                Log::error("ImageMagick command failed: {$command}. Code: {$resultCode}. Output: " . implode("\n", $output));
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to run ImageMagick command: " . $e->getMessage());
            return false;
        }
    }
}

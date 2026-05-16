<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class IdentityImageInspectionService
{
    public function quality(UploadedFile $file): array
    {
        $dimensions = @getimagesize($file->getRealPath());
        $warnings = [];

        if (!$dimensions) {
            return [
                'passed' => false,
                'width' => null,
                'height' => null,
                'file_size' => $file->getSize(),
                'warnings' => ['The uploaded file is not a readable image.'],
            ];
        }

        [$width, $height] = $dimensions;

        if ($width < 800 || $height < 500) {
            $warnings[] = 'Image resolution is low for reliable OCR.';
        }

        if ($file->getSize() < 50 * 1024) {
            $warnings[] = 'Image file size is unusually small.';
        }

        return [
            'passed' => empty($warnings),
            'width' => $width,
            'height' => $height,
            'file_size' => $file->getSize(),
            'warnings' => $warnings,
        ];
    }

    public function exif(UploadedFile $file): array
    {
        $warnings = [];
        $exif = false;

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getRealPath());
        }

        if (!$exif) {
            $warnings[] = 'No EXIF metadata found.';
        }

        return [
            'has_exif' => (bool) $exif,
            'captured_at' => $exif['DateTimeOriginal'] ?? $exif['DateTime'] ?? null,
            'camera_make' => $exif['Make'] ?? null,
            'camera_model' => $exif['Model'] ?? null,
            'warnings' => $warnings,
        ];
    }

    public function validateLebaneseIdFields(array $fields): array
    {
        $errors = [];

        if (blank($fields['first_name'] ?? null)) {
            $errors[] = 'Name was not detected.';
        }

        if (blank($fields['family_name'] ?? null)) {
            $errors[] = 'Family name was not detected.';
        }

        return [
            'passed' => empty($errors),
            'errors' => $errors,
            'warnings' => [],
        ];
    }
}

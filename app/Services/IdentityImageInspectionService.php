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
        $warnings = [];
        $errors = [];

        if (blank($fields['full_name'] ?? null)) {
            $errors[] = 'Full name was not detected.';
        }

        if (blank($fields['id_number'] ?? null)) {
            $errors[] = 'ID number was not detected.';
        } elseif (!preg_match('/^[A-Z0-9\-]{5,20}$/', $fields['id_number'])) {
            $warnings[] = 'Detected ID number format needs manual review.';
        }

        if (blank($fields['date_of_birth'] ?? null)) {
            $warnings[] = 'Date of birth was not detected.';
        }

        return [
            'passed' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}

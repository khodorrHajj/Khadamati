<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class IdentityImageInspectionService
{
    public function quality(UploadedFile $file): array
    {
        return $this->qualityFromPath($file->getRealPath(), $file->getSize());
    }

    public function exif(UploadedFile $file): array
    {
        return $this->exifFromPath($file->getRealPath());
    }

    public function qualityForStoredImage(string $diskPath): array
    {
        $absolutePath = Storage::disk('public')->path($diskPath);

        return $this->qualityFromPath($absolutePath, is_file($absolutePath) ? filesize($absolutePath) ?: null : null);
    }

    public function exifForStoredImage(string $diskPath): array
    {
        $absolutePath = Storage::disk('public')->path($diskPath);

        return $this->exifFromPath($absolutePath);
    }

    public function validateLebaneseIdFields(array $fields): array
    {
        $errors = [];
        $warnings = [];

        if (blank($fields['first_name_ar'] ?? $fields['first_name'] ?? null)) {
            $errors[] = 'First name was not detected.';
        }

        if (blank($fields['family_name_ar'] ?? $fields['family_name'] ?? null)) {
            $errors[] = 'Family name was not detected.';
        }

        if (blank($fields['father_name_ar'] ?? null)) {
            $warnings[] = 'Father name was not detected.';
        }

        if (blank($fields['mother_name_ar'] ?? null)) {
            $warnings[] = 'Mother name was not detected.';
        }

        if (blank($fields['mother_family_name_ar'] ?? null)) {
            $warnings[] = 'Mother family name was not detected.';
        }

        if (blank($fields['place_of_birth_ar'] ?? null)) {
            $warnings[] = 'Place of birth was not detected.';
        }

        if (blank($fields['date_of_birth_text'] ?? null)) {
            $warnings[] = 'Date of birth was not detected.';
        }

        if (blank($fields['national_id_number_normalized'] ?? null)) {
            $warnings[] = 'National ID number was not detected.';
        }

        foreach ([
            'gender_ar' => 'Gender',
            'marital_status_ar' => 'Family status',
            'record_number' => 'Record number',
            'locality_ar' => 'Locality',
            'governorate_ar' => 'Governorate',
            'district_ar' => 'District',
            'blood_type' => 'Blood type',
            'issue_date_text' => 'Issue date',
        ] as $key => $label) {
            if (blank($fields[$key] ?? null)) {
                $warnings[] = $label . ' was not detected on the back side.';
            }
        }

        return [
            'passed' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function qualityFromPath(string $path, ?int $size): array
    {
        $dimensions = @getimagesize($path);
        $warnings = [];

        if (!$dimensions) {
            return [
                'passed' => false,
                'width' => null,
                'height' => null,
                'file_size' => $size,
                'warnings' => ['The uploaded file is not a readable image.'],
            ];
        }

        [$width, $height] = $dimensions;

        if ($width < 800 || $height < 500) {
            $warnings[] = 'Image resolution is low for reliable OCR.';
        }

        if (($size ?? 0) > 0 && $size < 50 * 1024) {
            $warnings[] = 'Image file size is unusually small.';
        }

        return [
            'passed' => empty($warnings),
            'width' => $width,
            'height' => $height,
            'file_size' => $size,
            'warnings' => $warnings,
        ];
    }

    private function exifFromPath(string $path): array
    {
        $warnings = [];
        $exif = false;

        if (function_exists('exif_read_data') && is_file($path)) {
            $exif = @exif_read_data($path);
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
}

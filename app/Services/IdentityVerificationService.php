<?php

namespace App\Services;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class IdentityVerificationService
{
    public function __construct(
        private IdentityOcrService $ocrService,
        private IdentityImageInspectionService $inspectionService
    ) {
    }

    public function submit(User $user, UploadedFile $file): IdentityVerification
    {
        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'status' => IdentityVerification::STATUS_PROCESSING,
            'id_image_path' => $file->store('identity-verifications', 'public'),
        ]);

        try {
            $quality = $this->inspectionService->quality($file);
            $exif = $this->inspectionService->exif($file);
            $ocr = $this->ocrService->analyze($verification->id_image_path);
            $rawText = $ocr['text'] ?? '';
            $fields = $this->extractNameFields($user, $rawText, $ocr['fields'] ?? []);
            $validation = $this->inspectionService->validateLebaneseIdFields($fields);

            if ($rawText === '') {
                $validation['warnings'][] = 'No text was detected. Try a clearer image or approve manually.';
            }

            if ($this->containsArabicText($rawText)) {
                $validation['warnings'][] = 'Arabic OCR text detected. Manual admin review required.';
            }

            Log::debug('Identity OCR raw text', [
                'verification_id' => $verification->id,
                'raw_text' => $rawText,
            ]);

            $verification->update([
                'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
                'extracted_first_name' => $fields['first_name'] ?? null,
                'extracted_family_name' => $fields['family_name'] ?? null,
                'extracted_full_name' => trim(implode(' ', array_filter([
                    $fields['first_name'] ?? null,
                    $fields['family_name'] ?? null,
                ]))) ?: null,
                'extracted_id_number' => null,
                'extracted_date_of_birth' => null,
                'ocr_confidence' => $this->confidenceFor($ocr, $rawText, $fields),
                'ocr_raw_text' => $rawText,
                'ocr_raw_json' => $ocr['raw'] ?? $ocr,
                'quality_result_json' => $quality,
                'exif_result_json' => $exif,
                'validation_result_json' => $validation,
            ]);
        } catch (\Throwable $exception) {
            $verification->update([
                'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
                'validation_result_json' => [
                    'passed' => false,
                    'errors' => ['Identity OCR processing failed. Manual review required.'],
                    'exception' => $exception->getMessage(),
                ],
            ]);
        }

        return $verification->fresh();
    }

    private function extractNameFields(User $user, string $rawText, array $ocrFields = []): array
    {
        if (filled($ocrFields['first_name'] ?? null) || filled($ocrFields['family_name'] ?? null)) {
            return [
                'first_name' => $ocrFields['first_name'] ?? null,
                'family_name' => $ocrFields['family_name'] ?? null,
            ];
        }

        if ($this->containsArabicText($rawText)) {
            return [
                'first_name' => null,
                'family_name' => null,
            ];
        }

        $nameParts = preg_split('/\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = $nameParts[0] ?? null;
        $familyName = count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : null;

        return [
            'first_name' => $this->textContainsNamePart($rawText, $firstName) ? $firstName : null,
            'family_name' => $this->textContainsNamePart($rawText, $familyName) ? $familyName : null,
        ];
    }

    private function textContainsNamePart(string $rawText, ?string $namePart): bool
    {
        if (!$namePart) {
            return false;
        }

        $text = $this->normalizeComparableText($rawText);
        $part = $this->normalizeComparableText($namePart);

        if (strlen($part) < 2) {
            return false;
        }

        if (str_contains($text, $part)) {
            return true;
        }

        return strlen($part) >= 4 && str_contains($text, substr($part, 0, -1));
    }

    private function normalizeComparableText(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function containsArabicText(string $value): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $value);
    }

    private function confidenceFor(array $ocr, string $rawText, array $fields): float
    {
        $confidence = (float) ($ocr['confidence'] ?? 0);

        if ($confidence > 0) {
            return $confidence;
        }

        if (filled($fields['first_name'] ?? null) && filled($fields['family_name'] ?? null)) {
            return 0.75;
        }

        if (filled($rawText)) {
            return 0.5;
        }

        return 0.0;
    }
}

<?php

namespace App\Services;

use App\Jobs\ProcessIdentityVerificationJob;
use App\Models\IdentityVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class IdentityVerificationService
{
    public function __construct(
        private IdentityOcrService $ocrService,
        private IdentityImageInspectionService $inspectionService,
        private LebaneseNationalIdParser $parser
    ) {
    }

    public function submit(User $user, UploadedFile $frontFile, UploadedFile $backFile): IdentityVerification
    {
        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'status' => IdentityVerification::STATUS_PROCESSING,
            'id_image_path' => $frontFile->store('identity-verifications', 'public'),
            'id_image_back_path' => $backFile->store('identity-verifications', 'public'),
        ]);

        ProcessIdentityVerificationJob::dispatch($verification->id);

        return $verification->fresh();
    }

    public function process(IdentityVerification $verification): IdentityVerification
    {
        $verification->loadMissing('user');
        $user = $verification->user;

        if (!$user || $verification->status === IdentityVerification::STATUS_APPROVED) {
            return $verification;
        }

        try {
            $quality = [
                'front' => $this->inspectionService->qualityForStoredImage($verification->id_image_path),
                'back' => $this->inspectionService->qualityForStoredImage($verification->id_image_back_path),
            ];
            $exif = [
                'front' => $this->inspectionService->exifForStoredImage($verification->id_image_path),
                'back' => $this->inspectionService->exifForStoredImage($verification->id_image_back_path),
            ];
            $frontOcr = $this->ocrService->analyze($verification->id_image_path);
            $backOcr = $this->ocrService->analyze($verification->id_image_back_path);
            $frontRawText = $frontOcr['text'] ?? '';
            $backRawText = $backOcr['text'] ?? '';
            $fields = $this->extractIdentityFields(
                $user,
                $frontRawText,
                $backRawText,
                $frontOcr['fields'] ?? []
            );
            $validation = $this->inspectionService->validateLebaneseIdFields($fields);

            if ($frontRawText === '') {
                $validation['warnings'][] = 'No text was detected on the front-side image. Try a clearer image or approve manually.';
            }

            if ($backRawText === '') {
                $validation['warnings'][] = 'No text was detected on the back-side image. Try a clearer image or approve manually.';
            }

            if ($this->containsArabicText($frontRawText . "\n" . $backRawText)) {
                $validation['warnings'][] = 'Arabic OCR text detected. Manual admin review required.';
            }

            Log::debug('Identity OCR raw text', [
                'verification_id' => $verification->id,
                'front_raw_text' => $frontRawText,
                'back_raw_text' => $backRawText,
            ]);

            $verification->update([
                'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
                'extracted_first_name' => $fields['first_name_ar'] ?? null,
                'extracted_family_name' => $fields['family_name_ar'] ?? null,
                'extracted_father_name' => $fields['father_name_ar'] ?? null,
                'extracted_mother_name' => $fields['mother_name_ar'] ?? null,
                'extracted_mother_family_name' => $fields['mother_family_name_ar'] ?? null,
                'extracted_full_name' => trim(implode(' ', array_filter([
                    $fields['first_name_ar'] ?? null,
                    $fields['family_name_ar'] ?? null,
                ]))) ?: null,
                'extracted_place_of_birth' => $fields['place_of_birth_ar'] ?? null,
                'extracted_date_of_birth_text' => $fields['date_of_birth_text'] ?? null,
                'extracted_id_number' => $fields['national_id_number_normalized'] ?? $fields['national_id_number'] ?? null,
                'extracted_date_of_birth' => $this->dateForDatabase($fields['date_of_birth_text'] ?? null),
                'extracted_gender' => $fields['gender_ar'] ?? null,
                'extracted_marital_status' => $fields['marital_status_ar'] ?? null,
                'extracted_record_number' => $fields['record_number'] ?? null,
                'extracted_locality' => $fields['locality_ar'] ?? null,
                'extracted_governorate' => $fields['governorate_ar'] ?? null,
                'extracted_district' => $fields['district_ar'] ?? null,
                'extracted_blood_type' => $fields['blood_type'] ?? null,
                'extracted_issue_date_text' => $fields['issue_date_text'] ?? null,
                'ocr_confidence' => $this->combinedConfidence([$frontOcr, $backOcr], $fields),
                'ocr_raw_text' => $fields['raw_ocr_text'] ?? '',
                'ocr_raw_json' => [
                    'front' => $frontOcr,
                    'back' => $backOcr,
                    'parsed_fields' => $fields,
                ],
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

    private function extractIdentityFields(User $user, string $frontRawText, string $backRawText, array $frontOcrFields = []): array
    {
        $fields = $this->parser->parse($frontRawText, $backRawText);

        if (blank($fields['first_name_ar'] ?? null) && filled($frontOcrFields['first_name'] ?? null)) {
            $fields['first_name_ar'] = $frontOcrFields['first_name'];
        }

        if (blank($fields['family_name_ar'] ?? null) && filled($frontOcrFields['family_name'] ?? null)) {
            $fields['family_name_ar'] = $frontOcrFields['family_name'];
        }

        if ($this->containsArabicText($frontRawText . "\n" . $backRawText)) {
            return $fields;
        }

        $nameParts = preg_split('/\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = $nameParts[0] ?? null;
        $familyName = count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : null;

        if (blank($fields['first_name_ar'] ?? null) && $this->textContainsNamePart($frontRawText, $firstName)) {
            $fields['first_name_ar'] = $firstName;
        }

        if (blank($fields['family_name_ar'] ?? null) && $this->textContainsNamePart($frontRawText, $familyName)) {
            $fields['family_name_ar'] = $familyName;
        }

        return $fields;
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

    private function combinedConfidence(array $ocrResults, array $fields): float
    {
        $confidences = collect($ocrResults)
            ->map(fn (array $result) => (float) ($result['confidence'] ?? 0))
            ->filter(fn (float $confidence) => $confidence > 0)
            ->values();

        if ($confidences->isNotEmpty()) {
            return round((float) $confidences->avg(), 4);
        }

        if (
            filled($fields['first_name_ar'] ?? null)
            && filled($fields['family_name_ar'] ?? null)
            && filled($fields['national_id_number_normalized'] ?? null)
        ) {
            return 0.75;
        }

        if (filled($fields['raw_ocr_text'] ?? null)) {
            return 0.5;
        }

        return 0.0;
    }

    private function dateForDatabase(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (preg_match('/^(?<year>\d{4})[\/\-](?<month>\d{1,2})[\/\-](?<day>\d{1,2})$/', $value, $matches)) {
            $year = (int) $matches['year'];
            $month = (int) $matches['month'];
            $day = (int) $matches['day'];
        } elseif (preg_match('/^(?<day>\d{1,2})[\/\-](?<month>\d{1,2})[\/\-](?<year>\d{2,4})$/', $value, $matches)) {
            $year = (int) $matches['year'];
            if (strlen($matches['year']) === 2) {
                $year += $year >= 50 ? 1900 : 2000;
            }

            $month = (int) $matches['month'];
            $day = (int) $matches['day'];
        } else {
            return null;
        }

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
    }
}

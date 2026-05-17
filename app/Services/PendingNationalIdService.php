<?php

namespace App\Services;

use App\Models\NationalId;
use App\Models\PendingRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PendingNationalIdService
{
    public function __construct(
        private IdentityOcrService $ocrService,
        private LebaneseNationalIdParser $parser
    ) {
    }

    public function createFromSignup(array $validated, string $frontImagePath, string $backImagePath): NationalId
    {
        $nationalId = null;

        DB::transaction(function () use ($validated, $frontImagePath, $backImagePath, &$nationalId) {
            $nationalId = NationalId::create([
                'id_image_path' => $frontImagePath,
                'id_image_back_path' => $backImagePath,
                'status' => NationalId::STATUS_PENDING_REVIEW,
            ]);

            $pendingRegistration = PendingRegistration::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'national_id_id' => $nationalId->id,
                'status' => \App\Models\PendingRegistration::STATUS_PENDING_REVIEW,
            ]);

            $nationalId->update([
                'pending_registration_id' => $pendingRegistration->id,
            ]);
        });

        return $nationalId->fresh();
    }

    public function findDuplicateFromFrontImage(string $frontImagePath): ?string
    {
        $ocr = $this->ocrService->analyzeForNationalIdLookup($frontImagePath);
        $fields = $this->parser->parse($ocr['text'] ?? '', '');
        $normalizedNationalId = $fields['national_id_number_normalized'] ?? null;

        if (!$normalizedNationalId) {
            return null;
        }

        $exists = NationalId::query()
            ->where('national_id_number_normalized', $normalizedNationalId)
            ->whereIn('status', NationalId::statuses())
            ->exists();

        return $exists ? $normalizedNationalId : null;
    }

    public function process(NationalId $nationalId): NationalId
    {
        try {
            $frontOcr = $this->ocrService->analyze($nationalId->id_image_path);
            $backOcr = $this->ocrService->analyze($nationalId->id_image_back_path);
            $frontRawText = $frontOcr['text'] ?? '';
            $backRawText = $backOcr['text'] ?? '';
            $fields = $this->parser->parse($frontRawText, $backRawText);
            $notes = [];

            $normalizedNationalId = $fields['national_id_number_normalized'] ?? null;
            $nationalIdNumber = $fields['national_id_number'] ?? null;
            $hasDuplicate = $normalizedNationalId && $this->hasDuplicateNationalId($nationalId, $normalizedNationalId);

            if ($hasDuplicate) {
                $notes[] = 'Potential duplicate national ID detected. Manual admin review required.';
                $nationalIdNumber = null;
                $normalizedNationalId = null;
            }

            if ($frontRawText === '') {
                $notes[] = 'Front-side OCR returned no text.';
            }

            if ($backRawText === '') {
                $notes[] = 'Back-side OCR returned no text.';
            }

            $nationalId->update([
                'national_id_number' => $nationalIdNumber,
                'national_id_number_normalized' => $normalizedNationalId,
                'first_name_ar' => $fields['first_name_ar'] ?? null,
                'family_name_ar' => $fields['family_name_ar'] ?? null,
                'father_name_ar' => $fields['father_name_ar'] ?? null,
                'mother_name_ar' => $fields['mother_name_ar'] ?? null,
                'mother_family_name_ar' => $fields['mother_family_name_ar'] ?? null,
                'place_of_birth_ar' => $fields['place_of_birth_ar'] ?? null,
                'date_of_birth_text' => $fields['date_of_birth_text'] ?? null,
                'gender_ar' => $fields['gender_ar'] ?? null,
                'marital_status_ar' => $fields['marital_status_ar'] ?? null,
                'record_number' => $fields['record_number'] ?? null,
                'locality_ar' => $fields['locality_ar'] ?? null,
                'governorate_ar' => $fields['governorate_ar'] ?? null,
                'district_ar' => $fields['district_ar'] ?? null,
                'blood_type' => $fields['blood_type'] ?? null,
                'issue_date_text' => $fields['issue_date_text'] ?? null,
                'raw_ocr_text' => $fields['raw_ocr_text'] ?? null,
                'ocr_confidence' => $this->combinedOcrConfidence($frontOcr, $backOcr),
                'admin_notes' => empty($notes) ? null : implode(' ', $notes),
            ]);
        } catch (\Throwable $exception) {
            $nationalId->update([
                'admin_notes' => 'OCR processing failed. Manual admin review required. ' . $exception->getMessage(),
            ]);
        }

        return $nationalId->fresh();
    }

    private function combinedOcrConfidence(array $frontOcr, array $backOcr): ?float
    {
        $confidences = collect([
            (float) ($frontOcr['confidence'] ?? 0),
            (float) ($backOcr['confidence'] ?? 0),
        ])->filter(fn (float $confidence) => $confidence > 0)->values();

        if ($confidences->isEmpty()) {
            return null;
        }

        return round((float) $confidences->avg(), 4);
    }

    private function hasDuplicateNationalId(NationalId $nationalId, string $normalizedNationalId): bool
    {
        return NationalId::query()
            ->where('id', '!=', $nationalId->id)
            ->where('national_id_number_normalized', $normalizedNationalId)
            ->whereIn('status', NationalId::statuses())
            ->exists();
    }
}

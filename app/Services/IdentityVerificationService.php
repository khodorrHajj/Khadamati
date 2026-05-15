<?php

namespace App\Services;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Http\UploadedFile;

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
            $fields = $ocr['fields'] ?? [];
            $validation = $this->inspectionService->validateLebaneseIdFields($fields);

            $verification->update([
                'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
                'extracted_full_name' => $fields['full_name'] ?? null,
                'extracted_id_number' => $fields['id_number'] ?? null,
                'extracted_date_of_birth' => $fields['date_of_birth'] ?? null,
                'ocr_confidence' => $ocr['confidence'] ?? null,
                'ocr_raw_json' => $ocr['raw'] ?? $ocr,
                'quality_result_json' => $quality,
                'exif_result_json' => $exif,
                'validation_result_json' => $validation,
            ]);
        } catch (\Throwable $exception) {
            $verification->update([
                'status' => IdentityVerification::STATUS_FAILED,
                'validation_result_json' => [
                    'passed' => false,
                    'errors' => ['Identity verification processing failed.'],
                    'exception' => $exception->getMessage(),
                ],
            ]);
        }

        return $verification->fresh();
    }
}

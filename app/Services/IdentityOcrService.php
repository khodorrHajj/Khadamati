<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type as FeatureType;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\ImageContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IdentityOcrService
{
    public function analyze(string $diskPath): array
    {
        $absolutePath = Storage::disk('public')->path($diskPath);
        $fileDiagnostics = $this->fileDiagnostics($diskPath, $absolutePath);

        Log::debug('Identity OCR image diagnostics', $fileDiagnostics);

        if (!$fileDiagnostics['exists'] || !$fileDiagnostics['readable']) {
            return $this->failure('Uploaded identity image file is missing or unreadable.', [
                'file' => $fileDiagnostics,
            ]);
        }

        return $this->analyzePhysicalFile($absolutePath, [
            'stored_path' => $diskPath,
            'file' => $fileDiagnostics,
        ]);
    }

    public function analyzePhysicalFile(string $absolutePath, array $context = []): array
    {
        $credentialsPath = $this->resolveCredentialsPath(config('identity.google_application_credentials'));

        if (!$credentialsPath) {
            return $this->failure('GOOGLE_APPLICATION_CREDENTIALS is not configured.', $context);
        }

        if (!is_file($credentialsPath)) {
            return $this->failure('Google Vision credentials file does not exist.', array_merge($context, [
                'credentials_path' => $credentialsPath,
            ]));
        }

        try {
            $documentResult = $this->runGoogleVision($absolutePath, $credentialsPath, FeatureType::DOCUMENT_TEXT_DETECTION, 'document');

            if (filled($documentResult['text'])) {
                return $this->withContext($documentResult, $context);
            }

            $textResult = $this->runGoogleVision($absolutePath, $credentialsPath, FeatureType::TEXT_DETECTION, 'text');

            if (filled($textResult['text'])) {
                return $this->withContext($textResult, $context);
            }

            $preprocessedPath = null;
            $preprocessDiagnostics = $this->preprocessImage($absolutePath);

            if ($preprocessDiagnostics['used']) {
                $preprocessedPath = $preprocessDiagnostics['path'];
                $preprocessedResult = $this->runGoogleVision($preprocessedPath, $credentialsPath, FeatureType::DOCUMENT_TEXT_DETECTION, 'preprocessed_document');

                if (filled($preprocessedResult['text'])) {
                    $preprocessedResult['raw']['diagnostics']['preprocessing'] = $preprocessDiagnostics;
                    return $this->withContext($preprocessedResult, $context);
                }

                $preprocessedTextResult = $this->runGoogleVision($preprocessedPath, $credentialsPath, FeatureType::TEXT_DETECTION, 'preprocessed_text');
                $preprocessedTextResult['raw']['diagnostics']['preprocessing'] = $preprocessDiagnostics;

                if (filled($preprocessedTextResult['text'])) {
                    return $this->withContext($preprocessedTextResult, $context);
                }
            } else {
                Log::debug('Identity OCR preprocessing skipped', $preprocessDiagnostics);
            }

            Log::warning('Google Vision returned no text annotations.', [
                'document' => $documentResult['raw']['diagnostics'] ?? [],
                'text' => $textResult['raw']['diagnostics'] ?? [],
                'preprocessing' => $preprocessDiagnostics,
            ]);

            $emptyResult = $textResult;
            $emptyResult['raw']['diagnostics']['document_attempt'] = $documentResult['raw']['diagnostics'] ?? [];
            $emptyResult['raw']['diagnostics']['preprocessing'] = $preprocessDiagnostics;

            return $this->withContext($emptyResult, $context);
        } catch (\Throwable $exception) {
            return $this->failure('Google Vision OCR call failed.', array_merge($context, [
                'exception' => $exception->getMessage(),
            ]));
        } finally {
            if (isset($preprocessedPath) && $preprocessedPath && is_file($preprocessedPath)) {
                @unlink($preprocessedPath);
            }
        }
    }

    public function analyzeForNationalIdLookup(string $diskPath): array
    {
        $absolutePath = Storage::disk('public')->path($diskPath);
        $fileDiagnostics = $this->fileDiagnostics($diskPath, $absolutePath);

        if (!$fileDiagnostics['exists'] || !$fileDiagnostics['readable']) {
            return $this->failure('Uploaded identity image file is missing or unreadable.', [
                'file' => $fileDiagnostics,
            ]);
        }

        $credentialsPath = $this->resolveCredentialsPath(config('identity.google_application_credentials'));

        if (!$credentialsPath || !is_file($credentialsPath)) {
            return $this->failure('Google Vision credentials are unavailable.', [
                'stored_path' => $diskPath,
                'file' => $fileDiagnostics,
            ]);
        }

        try {
            $documentResult = $this->runGoogleVision(
                $absolutePath,
                $credentialsPath,
                FeatureType::DOCUMENT_TEXT_DETECTION,
                'lookup_document',
                [
                    'timeoutMillis' => 10000,
                    'retrySettings' => ['retriesEnabled' => false],
                ]
            );

            if (filled($documentResult['text'])) {
                return $this->withContext($documentResult, [
                    'stored_path' => $diskPath,
                    'file' => $fileDiagnostics,
                ]);
            }

            return $this->withContext(
                $this->runGoogleVision(
                    $absolutePath,
                    $credentialsPath,
                    FeatureType::TEXT_DETECTION,
                    'lookup_text',
                    [
                        'timeoutMillis' => 10000,
                        'retrySettings' => ['retriesEnabled' => false],
                    ]
                ),
                [
                    'stored_path' => $diskPath,
                    'file' => $fileDiagnostics,
                ]
            );
        } catch (\Throwable $exception) {
            return $this->failure('Google Vision OCR lookup failed.', [
                'stored_path' => $diskPath,
                'file' => $fileDiagnostics,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    public function extractFieldsFromText(string $text): array
    {
        return $this->extractFields($text);
    }

    private function runGoogleVision(
        string $absolutePath,
        string $credentialsPath,
        int $featureType,
        string $attempt,
        array $callOptions = []
    ): array
    {
        $credentials = new ServiceAccountCredentials(ImageAnnotatorClient::$serviceScopes, $credentialsPath);
        $client = new ImageAnnotatorClient([
            'credentials' => $credentials,
            'transport' => 'rest',
        ]);

        try {
            $request = (new AnnotateImageRequest())
                ->setImage((new Image())->setContent(file_get_contents($absolutePath)))
                ->setFeatures([(new Feature())->setType($featureType)])
                ->setImageContext((new ImageContext())->setLanguageHints(['ar', 'en']));

            $response = $client->batchAnnotateImages(
                (new BatchAnnotateImagesRequest())->setRequests([$request]),
                $callOptions
            );
        } finally {
            $client->close();
        }

        $annotationResponse = null;

        foreach ($response->getResponses() as $googleResponse) {
            $annotationResponse = $googleResponse;
            break;
        }

        if (!$annotationResponse) {
            return $this->failure('Google Vision returned no OCR response.', [
                'attempt' => $attempt,
            ]);
        }

        if ($annotationResponse->hasError()) {
            $error = $annotationResponse->getError();

            return $this->failure('Google Vision OCR response contained an error.', [
                'attempt' => $attempt,
                'code' => $error?->getCode(),
                'message' => $error?->getMessage(),
            ]);
        }

        $annotation = $annotationResponse->getFullTextAnnotation();
        $fullText = $annotation?->getText() ?? '';
        $textAnnotations = $annotationResponse->getTextAnnotations();
        $annotationDescriptions = [];

        foreach ($textAnnotations as $textAnnotation) {
            $description = trim((string) $textAnnotation->getDescription());

            if ($description !== '') {
                $annotationDescriptions[] = $description;
            }
        }

        $firstAnnotation = $annotationDescriptions[0] ?? '';
        $joinedAnnotations = implode("\n", $annotationDescriptions);
        $text = $fullText ?: $firstAnnotation ?: $joinedAnnotations;
        $confidence = $this->averageGoogleConfidence($annotation);

        if ($confidence <= 0 && filled($text)) {
            $confidence = 0.5;
        }

        $diagnostics = [
            'attempt' => $attempt,
            'feature_type' => $featureType === FeatureType::DOCUMENT_TEXT_DETECTION ? 'DOCUMENT_TEXT_DETECTION' : 'TEXT_DETECTION',
            'has_response' => true,
            'has_full_text_annotation' => (bool) $annotation,
            'full_text_length' => strlen($fullText),
            'text_annotations_count' => count($annotationDescriptions),
            'first_annotation_description' => $firstAnnotation,
            'final_raw_text_length' => strlen($text),
        ];

        Log::debug('Identity OCR Google Vision diagnostics', $diagnostics);

        if ($text === '') {
            Log::warning('Google Vision returned no text annotations.', $diagnostics);
        }

        $raw = json_decode($response->serializeToJsonString(), true) ?: [];

        return [
            'success' => filled($text),
            'confidence' => $confidence,
            'text' => $text,
            'fields' => $this->extractFields($text),
            'raw' => [
                'driver' => 'google-cloud-vision',
                'response' => $raw,
                'diagnostics' => $diagnostics,
            ],
        ];
    }

    private function fileDiagnostics(?string $storedPath, string $absolutePath): array
    {
        $exists = is_file($absolutePath);

        return [
            'stored_path' => $storedPath,
            'absolute_path' => $absolutePath,
            'exists' => $exists,
            'readable' => $exists && is_readable($absolutePath),
            'size' => $exists ? filesize($absolutePath) : null,
            'mime_type' => $exists ? (@mime_content_type($absolutePath) ?: null) : null,
        ];
    }

    private function preprocessImage(string $absolutePath): array
    {
        $target = tempnam(sys_get_temp_dir(), 'identity-ocr-');

        if ($target === false) {
            return ['used' => false, 'reason' => 'Could not create temporary file.'];
        }

        @unlink($target);
        $target .= '.jpg';

        try {
            if (class_exists(\Imagick::class)) {
                $image = new \Imagick($absolutePath);
                $image->setImageColorspace(\Imagick::COLORSPACE_GRAY);
                $image->contrastImage(true);
                $image->sharpenImage(0, 1);

                if ($image->getImageWidth() < 1200) {
                    $image->resizeImage(1200, 0, \Imagick::FILTER_LANCZOS, 1);
                }

                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(92);
                $image->writeImage($target);
                $image->clear();

                return ['used' => true, 'driver' => 'imagick', 'path' => $target];
            }

            if (function_exists('imagecreatefromstring')) {
                $source = @imagecreatefromstring(file_get_contents($absolutePath));

                if (!$source) {
                    @unlink($target);
                    return ['used' => false, 'reason' => 'GD could not read the uploaded image.'];
                }

                imagefilter($source, IMG_FILTER_GRAYSCALE);
                imagefilter($source, IMG_FILTER_CONTRAST, -25);

                $width = imagesx($source);
                $height = imagesy($source);
                $scale = $width < 1200 ? 2 : 1;
                $processed = imagescale($source, $width * $scale, $height * $scale);

                if (!$processed) {
                    $processed = $source;
                }

                imagejpeg($processed, $target, 92);
                imagedestroy($source);

                if ($processed !== $source) {
                    imagedestroy($processed);
                }

                return ['used' => true, 'driver' => 'gd', 'path' => $target];
            }
        } catch (\Throwable $exception) {
            @unlink($target);

            return [
                'used' => false,
                'reason' => 'Image preprocessing failed.',
                'exception' => $exception->getMessage(),
            ];
        }

        @unlink($target);

        return ['used' => false, 'reason' => 'GD and Imagick are unavailable.'];
    }

    private function averageGoogleConfidence($annotation): float
    {
        if (!$annotation) {
            return 0.0;
        }

        $confidences = [];

        foreach ($annotation->getPages() as $page) {
            foreach ($page->getBlocks() as $block) {
                $confidences[] = (float) $block->getConfidence();
            }
        }

        if (!$confidences) {
            return 0.0;
        }

        return round(array_sum($confidences) / count($confidences), 4);
    }

    private function extractFields(string $text): array
    {
        return $this->extractArabicFields($text);
    }

    private function extractArabicFields(string $text): array
    {
        $normalized = $this->normalizeArabicText($text);
        $lines = preg_split('/\R/u', $normalized) ?: [];

        return [
            'first_name' => $this->valueForArabicLabel($lines, 'الاسم'),
            'family_name' => $this->valueForArabicLabel($lines, 'الشهره'),
        ];
    }

    private function valueForArabicLabel(array $lines, string $label): ?string
    {
        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^' . preg_quote($label, '/') . '\s*[:\-]?\s*(.+)$/u', $line, $matches)) {
                return $this->cleanArabicFieldValue($matches[1]);
            }

            if ($line === $label || str_contains($line, $label)) {
                $afterLabel = trim(str_replace($label, '', $line));
                $afterLabel = trim($afterLabel, ":- \t\n\r\0\x0B");

                if ($afterLabel !== '') {
                    return $this->cleanArabicFieldValue($afterLabel);
                }

                $next = $lines[$index + 1] ?? null;

                if ($next) {
                    return $this->cleanArabicFieldValue($next);
                }
            }
        }

        return null;
    }

    private function cleanArabicFieldValue(string $value): ?string
    {
        $value = trim(preg_replace('/[^\p{Arabic}\s]+/u', ' ', $value) ?? '');
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    }

    private function normalizeArabicText(string $value): string
    {
        $value = str_replace(['ـ', 'أ', 'إ', 'آ', 'ى'], ['', 'ا', 'ا', 'ا', 'ي'], $value);
        $value = str_replace('ة', 'ه', $value);
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function failure(string $message, array $context = []): array
    {
        Log::warning('Identity OCR failed', array_merge([
            'message' => $message,
        ], $context));

        return [
            'success' => false,
            'confidence' => 0,
            'text' => '',
            'fields' => [],
            'raw' => [
                'driver' => 'google-cloud-vision',
                'error' => $message,
                'context' => $context,
                'diagnostics' => $context,
            ],
        ];
    }

    private function withContext(array $result, array $context): array
    {
        $result['raw']['context'] = $context;

        return $result;
    }

    private function resolveCredentialsPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (is_file($path)) {
            return $path;
        }

        $projectPath = base_path($path);

        return is_file($projectPath) ? $projectPath : $path;
    }
}

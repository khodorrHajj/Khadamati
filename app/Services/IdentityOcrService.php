<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class IdentityOcrService
{
    public function analyze(string $diskPath): array
    {
        if (!config('identity.google_vision_api_key')) {
            return [
                'success' => false,
                'confidence' => 0,
                'text' => '',
                'fields' => [],
                'raw' => [
                    'driver' => 'google',
                    'error' => 'GOOGLE_VISION_API_KEY is not configured.',
                ],
            ];
        }

        return $this->analyzeWithGoogleVision($diskPath);
    }

    private function analyzeWithGoogleVision(string $diskPath): array
    {
        $absolutePath = Storage::disk('public')->path($diskPath);
        $content = base64_encode(file_get_contents($absolutePath));

        $response = Http::post('https://vision.googleapis.com/v1/images:annotate?key=' . config('identity.google_vision_api_key'), [
            'requests' => [[
                'image' => ['content' => $content],
                'features' => [
                    ['type' => 'DOCUMENT_TEXT_DETECTION'],
                    ['type' => 'FACE_DETECTION'],
                ],
            ]],
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'confidence' => 0,
                'text' => '',
                'fields' => [],
                'raw' => [
                    'driver' => 'google',
                    'error' => $response->body(),
                    'status' => $response->status(),
                ],
            ];
        }

        $raw = $response->json();
        $annotation = $raw['responses'][0]['fullTextAnnotation'] ?? null;
        $text = $annotation['text'] ?? '';
        $confidence = $this->averageGoogleConfidence($annotation);

        return [
            'success' => true,
            'confidence' => $confidence,
            'text' => $text,
            'fields' => $this->extractFields($text),
            'raw' => [
                'driver' => 'google',
                'response' => $raw,
            ],
        ];
    }

    private function averageGoogleConfidence(?array $annotation): float
    {
        if (!$annotation || empty($annotation['pages'])) {
            return 0.0;
        }

        $confidences = [];

        foreach ($annotation['pages'] as $page) {
            foreach ($page['blocks'] ?? [] as $block) {
                if (isset($block['confidence'])) {
                    $confidences[] = (float) $block['confidence'];
                }
            }
        }

        if (!$confidences) {
            return 0.0;
        }

        return round(array_sum($confidences) / count($confidences), 4);
    }

    private function extractFields(string $text): array
    {
        $fullName = null;
        $idNumber = null;
        $dateOfBirth = null;

        if (preg_match('/(?:name|full name)\s*[:\-]?\s*([A-Za-z][A-Za-z ]{2,80})/i', $text, $matches)) {
            $fullName = trim($matches[1]);
        }

        if (preg_match('/(?:id\s*(?:no\.?|number)|identity\s*(?:no\.?|number)|no\.?)\s*[:\-]?\s*([A-Z0-9\-]{5,20})/i', $text, $matches)) {
            $idNumber = strtoupper(trim($matches[1]));
        } elseif (preg_match('/\b([0-9]{6,12})\b/', $text, $matches)) {
            $idNumber = trim($matches[1]);
        }

        if (preg_match('/(?:dob|birth|date of birth)\s*[:\-]?\s*([0-9]{4}[-\/][0-9]{1,2}[-\/][0-9]{1,2}|[0-9]{1,2}[-\/][0-9]{1,2}[-\/][0-9]{4})/i', $text, $matches)) {
            $dateOfBirth = $this->normalizeDate($matches[1]);
        }

        return [
            'full_name' => $fullName,
            'id_number' => $idNumber,
            'date_of_birth' => $dateOfBirth,
        ];
    }

    private function normalizeDate(string $value): ?string
    {
        $value = str_replace('/', '-', trim($value));
        $timestamp = strtotime($value);

        if (!$timestamp) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }
}

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\IdentityOcrService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('identity:test-ocr {path}', function (string $path, IdentityOcrService $ocrService) {
    $absolutePath = realpath($path) ?: $path;
    $exists = is_file($absolutePath);
    $readable = $exists && is_readable($absolutePath);

    $this->line('Path: ' . $absolutePath);
    $this->line('Exists: ' . ($exists ? 'yes' : 'no'));
    $this->line('Readable: ' . ($readable ? 'yes' : 'no'));
    $this->line('Size: ' . ($exists ? filesize($absolutePath) . ' bytes' : '-'));

    if (!$exists || !$readable) {
        $this->error('File is missing or unreadable.');

        return self::FAILURE;
    }

    $result = $ocrService->analyzePhysicalFile($absolutePath, [
        'command' => 'identity:test-ocr',
        'file' => [
            'absolute_path' => $absolutePath,
            'exists' => $exists,
            'readable' => $readable,
            'size' => filesize($absolutePath),
            'mime_type' => @mime_content_type($absolutePath) ?: null,
        ],
    ]);

    $rawText = $result['text'] ?? '';
    $fields = $result['fields'] ?? [];
    $raw = $result['raw'] ?? [];

    $this->line('Success: ' . (($result['success'] ?? false) ? 'yes' : 'no'));
    $this->line('Confidence: ' . ($result['confidence'] ?? 0));
    $this->line('Raw text length: ' . strlen($rawText));
    $this->line('Extracted first name: ' . ($fields['first_name'] ?? '-'));
    $this->line('Extracted family name: ' . ($fields['family_name'] ?? '-'));

    if ($error = ($raw['error'] ?? null)) {
        $this->warn('Error: ' . $error);
    }

    $this->newLine();
    $this->line('Raw OCR text:');
    $this->line($rawText !== '' ? $rawText : '[empty]');

    return self::SUCCESS;
})->purpose('Run Google Vision OCR diagnostics for a local identity image');

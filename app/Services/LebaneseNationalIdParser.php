<?php

namespace App\Services;

class LebaneseNationalIdParser
{
    public function parse(string $rawText): array
    {
        $normalizedText = $this->normalizeArabicText($rawText);
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $normalizedText) ?: [])));

        $nationalIdNumber = $this->extractNationalIdNumber($lines);

        return [
            'first_name_ar' => $this->valueForLabel($lines, ['الاسم']),
            'family_name_ar' => $this->valueForLabel($lines, ['الشهره']),
            'father_name_ar' => $this->valueForLabel($lines, ['اسم الاب']),
            'mother_name_ar' => $this->valueForLabel($lines, ['اسم الام']),
            'place_of_birth_ar' => $this->valueForLabel($lines, ['محل الولاده']),
            'date_of_birth_text' => $this->valueForLabel($lines, ['تاريخ الولاده']),
            'national_id_number' => $nationalIdNumber,
            'national_id_number_normalized' => $this->normalizeNationalIdNumber($nationalIdNumber),
            'raw_ocr_text' => $rawText,
        ];
    }

    public function normalizeNationalIdNumber(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = $this->convertArabicDigits($value);
        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    public function normalizeArabicText(string $value): string
    {
        $value = $this->convertArabicDigits($value);
        $value = str_replace(['ـ', 'أ', 'إ', 'آ', 'ى'], ['', 'ا', 'ا', 'ا', 'ي'], $value);
        $value = str_replace('ة', 'ه', $value);
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function valueForLabel(array $lines, array $labels): ?string
    {
        foreach ($lines as $index => $line) {
            foreach ($labels as $label) {
                if (preg_match('/^' . preg_quote($label, '/') . '\s*[:\-]?\s*(.+)$/u', $line, $matches)) {
                    return $this->cleanArabicValue($matches[1]);
                }

                if ($line === $label || str_contains($line, $label)) {
                    $afterLabel = trim(str_replace($label, '', $line));
                    $afterLabel = trim($afterLabel, ":- \t\n\r\0\x0B");

                    if ($afterLabel !== '') {
                        return $this->cleanArabicValue($afterLabel);
                    }

                    if (isset($lines[$index + 1])) {
                        return $this->cleanArabicValue($lines[$index + 1]);
                    }
                }
            }
        }

        return null;
    }

    private function extractNationalIdNumber(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (str_contains($line, 'تاريخ الولاده')) {
                continue;
            }

            $digits = $this->normalizeNationalIdNumber($line);

            if ($digits && strlen($digits) >= 6) {
                return trim($line);
            }
        }

        return null;
    }

    private function cleanArabicValue(string $value): ?string
    {
        $value = trim(preg_replace('/[^\p{Arabic}0-9\/\-\s]+/u', ' ', $value) ?? '');
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    }

    private function convertArabicDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}

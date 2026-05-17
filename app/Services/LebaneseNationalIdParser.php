<?php

namespace App\Services;

class LebaneseNationalIdParser
{
    public function parse(string $frontRawText, ?string $backRawText = null): array
    {
        $normalizedFrontText = $this->normalizeArabicText($frontRawText);
        $normalizedBackText = $this->normalizeArabicText($backRawText ?? '');
        $frontLines = $this->linesFromText($normalizedFrontText);
        $backLines = $this->linesFromText($normalizedBackText);
        $motherFields = $this->extractMotherFields($frontLines);
        $nationalIdNumber = $this->extractNationalIdNumber($frontLines);

        return [
            'first_name_ar' => $this->valueForLabel($frontLines, ['الاسم']),
            'family_name_ar' => $this->valueForLabel($frontLines, ['الشهرة', 'الشهره']),
            'father_name_ar' => $this->valueForLabel($frontLines, ['اسم الاب', 'اسم الأب']),
            'mother_name_ar' => $motherFields['mother_name_ar'],
            'mother_family_name_ar' => $motherFields['mother_family_name_ar'],
            'place_of_birth_ar' => $this->valueForLabel($frontLines, ['محل الولادة', 'محل الولاده']),
            'date_of_birth_text' => $this->normalizeDateValue($this->valueForLabel($frontLines, ['تاريخ الولادة', 'تاريخ الولاده'])),
            'national_id_number' => $nationalIdNumber,
            'national_id_number_normalized' => $this->normalizeNationalIdNumber($nationalIdNumber),
            'gender_ar' => $this->valueForLabel($backLines, ['الجنس']),
            'marital_status_ar' => $this->valueForLabel($backLines, ['الوضع العائلي', 'الوضع العايلي', 'الحالة العائلية', 'الحاله العائليه']),
            'record_number' => $this->cleanNumericValue($this->valueForLabel($backLines, ['رقم السجل'])),
            'locality_ar' => $this->valueForLabel($backLines, ['المحلة او القرية', 'المحلة أو القرية', 'المحله او القريه', 'المحله أو القريه', 'القرية او المحلة', 'القرية أو المحلة', 'القريه او المحله', 'القريه أو المحله']),
            'governorate_ar' => $this->valueForLabel($backLines, ['المحافظة', 'المحافظه']),
            'district_ar' => $this->valueForLabel($backLines, ['القضاء']),
            'blood_type' => $this->cleanBloodType($this->valueForLabel($backLines, ['فئة الدم', 'فئه الدم'])),
            'issue_date_text' => $this->normalizeDateValue($this->valueForLabel($backLines, ['تاريخ الإصدار', 'تاريخ الاصدار'])),
            'raw_ocr_text' => $this->combineRawTexts($frontRawText, $backRawText),
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
        $value = str_replace('ـ', '', $value);
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function linesFromText(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $value) ?: [])));
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

    private function extractMotherFields(array $frontLines): array
    {
        $combined = $this->valueForLabel($frontLines, [
            'اسم الام وشهرتها',
            'اسم الام و شهرتها',
            'اسم الأم وشهرتها',
            'اسم الأم و شهرتها',
            'اسم الام وشهرها',
            'اسم الام و شهرها',
            'اسم الأم وشهرها',
            'اسم الأم و شهرها',
        ]);

        if ($combined) {
            $parts = preg_split('/\s+/u', $combined, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            return [
                'mother_name_ar' => $parts[0] ?? null,
                'mother_family_name_ar' => count($parts) > 1
                    ? $this->cleanArabicValue(implode(' ', array_slice($parts, 1)))
                    : null,
            ];
        }

        return [
            'mother_name_ar' => $this->valueForLabel($frontLines, ['اسم الام', 'اسم الأم']),
            'mother_family_name_ar' => $this->valueForLabel($frontLines, ['شهرتها', 'شهرة الام', 'شهرة الأم', 'شهرتها']),
        ];
    }

    private function extractNationalIdNumber(array $lines): ?string
    {
        foreach ($lines as $index => $line) {
            if (!str_contains($line, 'تاريخ الولاده') && !str_contains($line, 'تاريخ الولادة')) {
                continue;
            }

            $candidates = [];
            $sameLine = trim((string) preg_replace('/^.*?(?:تاريخ الولاده|تاريخ الولادة)\s*[:\-]?\s*/u', '', $line));

            if ($sameLine !== '') {
                $candidates[] = $sameLine;
            }

            foreach ([$index + 1, $index + 2] as $candidateIndex) {
                if (isset($lines[$candidateIndex])) {
                    $candidates[] = $lines[$candidateIndex];
                }
            }

            foreach ($candidates as $candidate) {
                if ($this->isLikelyDateValue($candidate)) {
                    continue;
                }

                $digits = $this->normalizeNationalIdNumber($candidate);

                if ($digits && strlen($digits) >= 7) {
                    return trim($candidate);
                }
            }
        }

        foreach ($lines as $line) {
            $digits = $this->normalizeNationalIdNumber($line);

            if ($digits && strlen($digits) >= 7) {
                return trim($line);
            }
        }

        return null;
    }

    private function isLikelyDateValue(string $candidate): bool
    {
        $candidate = $this->normalizeDateValue($candidate) ?? '';

        if ($candidate === '') {
            return false;
        }

        return (bool) preg_match('/^(?:\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})$/', $candidate);
    }

    private function cleanArabicValue(string $value): ?string
    {
        $value = trim(preg_replace('/[^\p{Arabic}0-9A-Za-z\/\-\+\s]+/u', ' ', $value) ?? '');
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    }

    private function normalizeDateValue(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = $this->convertArabicDigits($value);
        $value = trim(preg_replace('/[^0-9\/\-]+/u', '', $value) ?? '');

        return $value !== '' ? $value : null;
    }

    private function cleanNumericValue(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = $this->convertArabicDigits($value);
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits !== '' ? $digits : null;
    }

    private function cleanBloodType(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = strtoupper($this->convertArabicDigits($value));
        $value = trim(preg_replace('/[^ABO\+\-\s]+/u', '', $value) ?? '');
        $value = str_replace(' ', '', $value);

        if (preg_match('/^([\+\-])(A|B|AB|O)$/', $value, $matches)) {
            $value = $matches[2] . $matches[1];
        }

        return $value !== '' ? $value : null;
    }

    private function combineRawTexts(string $frontRawText, ?string $backRawText): string
    {
        $parts = [];

        if (trim($frontRawText) !== '') {
            $parts[] = "=== FRONT ===\n" . trim($frontRawText);
        }

        if (trim((string) $backRawText) !== '') {
            $parts[] = "=== BACK ===\n" . trim((string) $backRawText);
        }

        return implode("\n\n", $parts);
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

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CsvImportService
{
    /**
     * @return array{0: list<string>, 1: list<array<string, string>>}
     */
    public function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new \RuntimeException('CSV import failed: Could not read CSV file.');
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            throw new \RuntimeException('CSV import failed: CSV file is empty.');
        }

        // Strip UTF-8 BOM (Excel) and normalize header names to snake_case
        $headers = array_map(function ($h) {
            $h = (string) $h;
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h) ?? $h;
            $h = trim($h);

            return Str::snake($h);
        }, $headers);

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if ($this->rowIsEmpty($data)) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = isset($data[$i]) ? trim((string) $data[$i]) : '';
            }
            $row['_line'] = (string) $line;
            $rows[] = $row;
        }
        fclose($handle);

        if ($rows === []) {
            throw new \RuntimeException('CSV import failed: No data rows found in CSV.');
        }

        return [$headers, $rows];
    }

    public function bool(mixed $value, bool $default = true): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'on'], true);
    }

    public function float(mixed $value, float $default = 0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    public function isNumericValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $normalized = str_replace([',', ' '], '', (string) $value);

        return is_numeric($normalized);
    }

    public function isBoolToken(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true; // empty uses default
        }

        return in_array(strtolower(trim((string) $value)), ['0', '1', 'true', 'false', 'yes', 'no', 'y', 'n', 'on', 'off'], true);
    }

    private function rowIsEmpty(array $data): bool
    {
        foreach ($data as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class SellerAiDocumentParser
{
    private const MAX_TEXT_LENGTH = 60000;
    private const MAX_ROWS = 300;

    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->getRealPath();

        $result = match ($extension) {
            'csv' => $this->parseCsv($path),
            'xls', 'xlsx' => $this->parseSpreadsheet($path),
            'doc', 'docx' => $this->parseWord($path),
            'pdf' => $this->parsePdf($path),
            default => throw new \InvalidArgumentException('Faqat Excel, CSV, Word va PDF fayllar qabul qilinadi.'),
        };

        $text = trim((string) ($result['text'] ?? ''));

        return [
            'file_name' => $file->getClientOriginalName(),
            'extension' => $extension,
            'size' => $file->getSize(),
            'text' => mb_substr($text, 0, self::MAX_TEXT_LENGTH),
            'truncated' => mb_strlen($text) > self::MAX_TEXT_LENGTH,
            'rows' => array_slice($result['rows'] ?? [], 0, self::MAX_ROWS),
            'row_count' => (int) ($result['row_count'] ?? count($result['rows'] ?? [])),
        ];
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['text' => '', 'rows' => [], 'row_count' => 0];
        }

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
        }
        fclose($handle);

        return $this->rowsToPayload($rows);
    }

    private function parseSpreadsheet(string $path): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $row) {
            $clean = array_values(array_filter(array_map(
                fn ($value) => trim((string) $value),
                $row
            ), fn ($value) => $value !== ''));

            if ($clean === []) {
                continue;
            }

            $rows[] = $clean;
            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
        }

        return $this->rowsToPayload($rows);
    }

    private function parseWord(string $path): array
    {
        $word = WordIOFactory::load($path);
        $parts = [];

        foreach ($word->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $parts[] = $this->wordElementText($element);
            }
        }

        $text = trim(implode("\n", array_filter($parts)));

        return ['text' => $text, 'rows' => [], 'row_count' => 0];
    }

    private function wordElementText(object $element): string
    {
        if (method_exists($element, 'getText')) {
            $value = $element->getText();
            return is_string($value) ? $value : '';
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $parts[] = $this->wordElementText($child);
            }
            return implode(' ', array_filter($parts));
        }

        if (method_exists($element, 'getRows')) {
            $rows = [];
            foreach ($element->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $child) {
                        $cells[] = $this->wordElementText($child);
                    }
                }
                $rows[] = implode(' | ', array_filter($cells));
            }
            return implode("\n", array_filter($rows));
        }

        return '';
    }

    private function parsePdf(string $path): array
    {
        $pdf = (new PdfParser())->parseFile($path);

        return ['text' => trim($pdf->getText()), 'rows' => [], 'row_count' => 0];
    }

    private function rowsToPayload(array $rows): array
    {
        return [
            'text' => implode("\n", array_map(fn ($row) => implode(' | ', $row), $rows)),
            'rows' => $rows,
            'row_count' => count($rows),
        ];
    }
}

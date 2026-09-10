<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Rendering;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;

/**
 * Menulis data tabular ke bytes XLSX (OpenSpout) atau CSV (native). Pure PHP —
 * cocok untuk lingkungan infrastruktur terbatas (tanpa headless browser).
 */
final class TabularWriter
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<int|float|string>>  $rows
     */
    public function csv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new RuntimeException('Tidak dapat membuat buffer CSV.');
        }

        fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8 agar Excel membaca dengan benar
        fputcsv($handle, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    /**
     * @param  array<string, array{headers: list<string>, rows: list<list<int|float|string>>}>  $sheets  judul sheet => tabel
     */
    public function xlsx(array $sheets): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'rpt_').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($tmp);

        $first = true;
        foreach ($sheets as $title => $table) {
            if ($first) {
                $writer->getCurrentSheet()->setName($this->safeSheetName($title));
                $first = false;
            } else {
                $writer->addNewSheetAndMakeItCurrent()->setName($this->safeSheetName($title));
            }

            $writer->addRow(Row::fromValues($table['headers']));
            foreach ($table['rows'] as $row) {
                $writer->addRow(Row::fromValues($row));
            }
        }

        $writer->close();

        $content = file_get_contents($tmp) ?: '';
        @unlink($tmp);

        return $content;
    }

    private function safeSheetName(string $name): string
    {
        $name = preg_replace('/[\\\\\\/\\*\\?\\[\\]:]/', ' ', $name) ?? $name;

        return mb_substr(trim($name), 0, 31) ?: 'Sheet';
    }
}

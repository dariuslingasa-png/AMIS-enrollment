<?php

namespace App\Services\Exporters;

use Mpdf\Mpdf;
use Illuminate\Support\Facades\File;

class PdfExporterService
{
    /**
     * Render HTML string to PDF binary or save to file.
     *
     * @param string $html
     * @param string|null $outputPath Target file path if saving to disk
     * @return string Raw PDF content if $outputPath is null, or target file path
     */
    public function exportHtmlToPdf(string $html, ?string $outputPath = null): string
    {
        $tempDir = storage_path('app/temp/mpdf');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_header' => 5,
            'margin_footer' => 5,
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->WriteHTML($html);

        if ($outputPath) {
            $directory = dirname($outputPath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);
            return $outputPath;
        }

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}

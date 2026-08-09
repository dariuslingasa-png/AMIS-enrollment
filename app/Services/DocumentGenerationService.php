<?php

namespace App\Services;

use App\Models\EnrollmentApplicant;
use App\Services\Exporters\PdfExporterService;
use App\Services\Exporters\DocxExporterService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentGenerationService
{
    public function __construct(
        protected PdfExporterService $pdfExporter,
        protected DocxExporterService $docxExporter
    ) {}

    /**
     * Render HTML Preview string from single-source Blade template.
     */
    public function renderHtmlPreview(EnrollmentApplicant $applicant, string $template = 'enrollment_form'): string
    {
        $qrCodeBase64 = null;
        try {
            $refData = $applicant->amis_student_id ?? ('APP-' . $applicant->id);
            $qrRaw = QrCode::format('png')->size(120)->margin(1)->generate($refData);
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrRaw);
        } catch (\Throwable $e) {
            $qrCodeBase64 = null;
        }

        return view('documents.' . $template, [
            'applicant' => $applicant,
            'qrCodeBase64' => $qrCodeBase64,
        ])->render();
    }

    /**
     * Generate PDF binary or file.
     */
    public function generatePdf(EnrollmentApplicant $applicant, string $template = 'enrollment_form', ?string $outputPath = null): string
    {
        $html = $this->renderHtmlPreview($applicant, $template);
        return $this->pdfExporter->exportHtmlToPdf($html, $outputPath);
    }

    /**
     * Generate native DOCX file.
     */
    public function generateDocx(EnrollmentApplicant $applicant, ?string $outputPath = null): string
    {
        return $this->docxExporter->exportApplicantToDocx($applicant, $outputPath);
    }
}

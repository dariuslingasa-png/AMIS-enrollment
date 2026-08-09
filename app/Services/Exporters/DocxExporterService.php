<?php

namespace App\Services\Exporters;

use App\Models\EnrollmentApplicant;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use Illuminate\Support\Facades\File;

class DocxExporterService
{
    /**
     * Generate native DOCX file from student applicant data.
     *
     * @param EnrollmentApplicant $applicant
     * @param string|null $outputPath Target file path if saving to disk
     * @return string Target file path of generated .docx file
     */
    public function exportApplicantToDocx(EnrollmentApplicant $applicant, ?string $outputPath = null): string
    {
        $phpWord = new PhpWord();
        
        // Page setup
        $section = $phpWord->addSection([
            'pageSizeW' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(21.0),
            'pageSizeH' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(29.7),
            'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.5),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.5),
            'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
            'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2.0),
        ]);

        // Header Title
        $section->addText('AL MUNAWWARA ISLAMIC SCHOOL', ['bold' => true, 'size' => 16, 'color' => '047857'], ['alignment' => Jc::CENTER]);
        $section->addText('OFFICIAL STUDENT ENROLLMENT FORM', ['bold' => true, 'size' => 12, 'color' => '0F172A'], ['alignment' => Jc::CENTER]);
        $section->addText('School Year ' . ($applicant->school_year ?? '2026-2027'), ['size' => 10, 'italic' => true, 'color' => '475569'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        // 1. STUDENT PERSONAL INFORMATION
        $section->addText('1. STUDENT PERSONAL INFORMATION', ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'], ['bgColor' => '059669', 'alignment' => Jc::LEFT]);
        
        $tableStyle = ['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 80];
        $phpWord->addTableStyle('StudentTable', $tableStyle);
        $table = $section->addTable('StudentTable');

        $table->addRow();
        $table->addCell(3000)->addText('Full Name', ['bold' => true]);
        $table->addCell(6000)->addText($applicant->full_name, ['bold' => true, 'color' => '059669']);

        $table->addRow();
        $table->addCell(3000)->addText('AMIS Student ID', ['bold' => true]);
        $table->addCell(6000)->addText($applicant->amis_student_id ?? 'PENDING');

        $table->addRow();
        $table->addCell(3000)->addText('LRN', ['bold' => true]);
        $table->addCell(6000)->addText($applicant->lrn ?? 'N/A');

        $table->addRow();
        $table->addCell(3000)->addText('Grade Level', ['bold' => true]);
        $table->addCell(6000)->addText($applicant->grade_level);

        $table->addRow();
        $table->addCell(3000)->addText('Gender / DOB', ['bold' => true]);
        $table->addCell(6000)->addText(ucfirst($applicant->gender ?? 'N/A') . ' | ' . (optional($applicant->date_of_birth)->format('F d, Y') ?? 'N/A'));

        $table->addRow();
        $table->addCell(3000)->addText('Residence Address', ['bold' => true]);
        $table->addCell(6000)->addText($applicant->address ?? ($applicant->street_address . ', ' . $applicant->city . ', ' . $applicant->state_province));

        $section->addTextBreak(1);

        // 2. PARENT INFORMATION
        $section->addText('2. PARENT & GUARDIAN DETAILS', ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'], ['bgColor' => '059669', 'alignment' => Jc::LEFT]);
        $table2 = $section->addTable('StudentTable');

        $table2->addRow();
        $table2->addCell(3000)->addText("Father's Name", ['bold' => true]);
        $table2->addCell(6000)->addText(trim(($applicant->father_first_name ?? '') . ' ' . ($applicant->father_last_name ?? '')) ?: 'N/A');

        $table2->addRow();
        $table2->addCell(3000)->addText("Mother's Maiden Name", ['bold' => true]);
        $table2->addCell(6000)->addText(trim(($applicant->mother_first_name ?? '') . ' ' . ($applicant->mother_last_name ?? '')) ?: 'N/A');

        $table2->addRow();
        $table2->addCell(3000)->addText('Parent Contact', ['bold' => true]);
        $table2->addCell(6000)->addText($applicant->parent_mobile ?? 'N/A');

        $section->addTextBreak(2);

        // Signatures
        $sigTable = $section->addTable();
        $sigTable->addRow();
        $sigTable->addCell(4500)->addText("_________________________\nStudent Signature", ['bold' => true], ['alignment' => Jc::CENTER]);
        $sigTable->addCell(4500)->addText("_________________________\nParent / Guardian Signature", ['bold' => true], ['alignment' => Jc::CENTER]);

        // Output setup
        if (!$outputPath) {
            $tempDir = storage_path('app/temp/docx');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }
            $outputPath = $tempDir . '/applicant_' . $applicant->id . '_' . time() . '.docx';
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

        return $outputPath;
    }
}

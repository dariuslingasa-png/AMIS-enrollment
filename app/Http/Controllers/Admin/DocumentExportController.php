<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplicant;
use App\Services\DocumentGenerationService;
use App\Jobs\GenerateBatchDocumentsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentExportController extends Controller
{
    public function __construct(
        protected DocumentGenerationService $docService
    ) {}

    /**
     * Preview Document as HTML in Browser.
     */
    public function previewHtml($id)
    {
        $applicant = EnrollmentApplicant::findOrFail($id);
        $html = $this->docService->renderHtmlPreview($applicant);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Download Single Student PDF.
     */
    public function exportPdf($id)
    {
        $applicant = EnrollmentApplicant::findOrFail($id);
        $pdfContent = $this->docService->generatePdf($applicant);

        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $applicant->full_name);
        $fileName = "Enrollment_Form_{$applicant->id}_{$cleanName}.pdf";

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$fileName}\"");
    }

    /**
     * Download Single Student DOCX.
     */
    public function exportDocx($id)
    {
        $applicant = EnrollmentApplicant::findOrFail($id);
        $filePath = $this->docService->generateDocx($applicant);

        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $applicant->full_name);
        $fileName = "Enrollment_Form_{$applicant->id}_{$cleanName}.docx";

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Dispatch Batch Export Job (ZIP Archive).
     */
    public function batchExport(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,docx',
            'grade_level' => 'nullable|string',
            'school_year' => 'nullable|string',
        ]);

        $batchId = (string) Str::uuid();
        
        GenerateBatchDocumentsJob::dispatch($batchId, [
            'format' => $request->format,
            'grade_level' => $request->grade_level,
            'school_year' => $request->school_year,
        ]);

        return response()->json([
            'success' => true,
            'batch_id' => $batchId,
            'message' => 'Batch document generation queued successfully.',
        ]);
    }

    /**
     * Poll Batch Progress Status.
     */
    public function batchStatus($batchId)
    {
        $status = Cache::get("export_batch_{$batchId}", [
            'status' => 'pending',
            'progress' => 0,
            'message' => 'Waiting in queue...',
        ]);

        return response()->json($status);
    }

    /**
     * Download generated ZIP archive.
     */
    public function downloadZip($filename)
    {
        $safeName = basename($filename);
        $filePath = storage_path("app/public/exports/{$safeName}");

        if (!file_exists($filePath)) {
            abort(404, 'Export file not found or expired.');
        }

        return response()->download($filePath);
    }
}

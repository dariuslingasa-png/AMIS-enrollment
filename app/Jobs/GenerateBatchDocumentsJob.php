<?php

namespace App\Jobs;

use App\Models\EnrollmentApplicant;
use App\Services\DocumentGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ZipArchive;

class GenerateBatchDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public string $batchId,
        public array $filters = []
    ) {}

    public function handle(DocumentGenerationService $docService): void
    {
        $cacheKey = "export_batch_{$this->batchId}";
        Cache::put($cacheKey, ['status' => 'processing', 'progress' => 5, 'message' => 'Preparing batch...']);

        $format = strtolower($this->filters['format'] ?? 'pdf'); // pdf or docx
        $gradeLevel = $this->filters['grade_level'] ?? null;
        $schoolYear = $this->filters['school_year'] ?? null;

        $query = EnrollmentApplicant::query();
        if ($gradeLevel) {
            $query->where('grade_level', $gradeLevel);
        }
        if ($schoolYear) {
            $query->where('school_year', $schoolYear);
        }

        $totalCount = $query->count();
        if ($totalCount === 0) {
            Cache::put($cacheKey, ['status' => 'failed', 'progress' => 0, 'message' => 'No student records found matching filters.']);
            return;
        }

        $tempBatchDir = storage_path("app/temp/batch_{$this->batchId}");
        if (!File::exists($tempBatchDir)) {
            File::makeDirectory($tempBatchDir, 0755, true);
        }

        $exportDir = storage_path('app/public/exports');
        if (!File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $zipFileName = "AMIS_Documents_{$this->batchId}.zip";
        $zipFilePath = "{$exportDir}/{$zipFileName}";

        $processed = 0;
        $filesToZip = [];

        $query->chunk(50, function ($applicants) use ($docService, $format, $tempBatchDir, $totalCount, $cacheKey, &$processed, &$filesToZip) {
            foreach ($applicants as $applicant) {
                $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $applicant->full_name);
                $idTag = $applicant->amis_student_id ?? ("APP_" . $applicant->id);

                if ($format === 'docx') {
                    $fileName = "{$idTag}_{$cleanName}.docx";
                    $filePath = "{$tempBatchDir}/{$fileName}";
                    $docService->generateDocx($applicant, $filePath);
                } else {
                    $fileName = "{$idTag}_{$cleanName}.pdf";
                    $filePath = "{$tempBatchDir}/{$fileName}";
                    $docService->generatePdf($applicant, 'enrollment_form', $filePath);
                }

                $filesToZip[] = ['path' => $filePath, 'name' => $fileName];
                $processed++;

                $pct = (int) round(($processed / $totalCount) * 85) + 5;
                Cache::put($cacheKey, ['status' => 'processing', 'progress' => $pct, 'message' => "Generated {$processed} of {$totalCount} documents..."]);
            }
        });

        // Create ZIP archive
        Cache::put($cacheKey, ['status' => 'processing', 'progress' => 92, 'message' => 'Compressing files into downloadable ZIP archive...']);

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($filesToZip as $file) {
                if (File::exists($file['path'])) {
                    $zip->addFile($file['path'], $file['name']);
                }
            }
            $zip->close();
        }

        // Cleanup temp folder
        File::deleteDirectory($tempBatchDir);

        Cache::put($cacheKey, [
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Export complete!',
            'zip_filename' => $zipFileName,
            'download_url' => route('admin.documents.download-zip', $zipFileName),
        ]);
    }
}

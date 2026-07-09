<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdVerificationController extends Controller
{
    /**
     * Display the ID verification page.
     */
    public function show()
    {
        $gradeLevels = [
            'Kinder 1', 'Kinder 2',
            'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
            'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
        ];

        return view('enrollment.id-verification', compact('gradeLevels'));
    }

    /**
     * Process verification request.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|string',
            'full_name'   => 'required|string',
            'grade_level' => 'required|string',
            'school_year' => 'required|string',
        ]);

        $enteredId = trim($request->input('student_id'));
        $enteredName = trim($request->input('full_name'));
        $enteredGrade = trim($request->input('grade_level'));
        $enteredYear = trim($request->input('school_year'));

        $record = null;

        // 1. Identify ID type and search
        if (str_starts_with(strtoupper($enteredId), 'TEMP-')) {
            // Temporary Application ID
            // Format: TEMP-YYYY-XXXXXX
            $parts = explode('-', $enteredId);
            $applicantId = (int) end($parts);

            if ($applicantId > 0) {
                $applicant = EnrollmentApplicant::find($applicantId);
                if ($applicant) {
                    $record = $this->buildFromApplicant($applicant);
                }
            }
        } else {
            // AMIS Student ID
            $normalizedId = $this->normalizeAmisId($enteredId);

            // A. Search in CSV first (historical enrolled students)
            $csvRecord = $this->searchCsv($normalizedId);
            if ($csvRecord) {
                $record = $csvRecord;
            } else {
                // B. Search in DB Students table
                $student = Student::where('student_number', $enteredId)
                    ->orWhere('student_number', $normalizedId)
                    ->with('applicant')
                    ->first();

                if ($student && $student->applicant) {
                    $record = $this->buildFromStudent($student);
                } else {
                    // C. Search in DB EnrollmentApplicants table
                    $applicant = EnrollmentApplicant::where('amis_student_id', $enteredId)
                        ->orWhere('amis_student_id', $normalizedId)
                        ->first();
                    
                    if ($applicant) {
                        $record = $this->buildFromApplicant($applicant);
                    }
                }
            }
        }

        // 2. Perform strict 4-field validation
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No matching record found. Please verify your ID, Name, Grade Level, and School Year.'
            ], 422);
        }

        // Validate School Year (CSV defaults to 2026-2027)
        $recordYear = $record['school_year'] ?? '2026-2027';
        if ($recordYear !== $enteredYear) {
            return response()->json([
                'success' => false,
                'message' => 'No matching record found. Please verify your ID, Name, Grade Level, and School Year.'
            ], 422);
        }

        // Validate Grade Level
        if (strtolower(trim($record['grade_level'])) !== strtolower(trim($enteredGrade))) {
            return response()->json([
                'success' => false,
                'message' => 'No matching record found. Please verify your ID, Name, Grade Level, and School Year.'
            ], 422);
        }

        // Validate Full Name
        if (!$this->nameMatches($enteredName, $record['full_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'No matching record found. Please verify your ID, Name, Grade Level, and School Year.'
            ], 422);
        }

        // 3. Success: return details
        return response()->json([
            'success'     => true,
            'student_id'  => $record['display_id'],
            'full_name'   => $record['full_name'],
            'grade_level' => $record['grade_level'],
            'school_year' => $recordYear,
            'status'      => $record['status'],
            'photo_url'   => $record['photo_url'],
            'qr_code'     => $record['qr_code_url'] ?: 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/id?id=' . $record['display_id']) . '&dark=000000&light=ffffff&margin=1&format=png&size=300',
            'parent_name' => $record['parent_name'] ?? 'Registrar Office',
            'address'     => $record['address'] ?? 'Davao City, Philippines',
            'is_temp'     => str_starts_with($record['display_id'], 'TEMP-'),
        ]);
    }

    /**
     * Normalize AMIS ID format to CSV notation (e.g. AMIS-2026-000123 -> 260123).
     */
    private function normalizeAmisId(string $id): string
    {
        $id = strtoupper(trim($id));

        // Format: AMIS-YYYY-XXXXXX
        if (preg_match('/^AMIS-(\d{4})-(\d+)$/', $id, $matches)) {
            $year = substr($matches[1], 2, 2); // get last 2 digits of year (e.g. "26" from "2026")
            $seq = (int) $matches[2];          // get integer sequence (e.g. 123 from "000123")
            return $year . str_pad($seq, 4, '0', STR_PAD_LEFT); // e.g. "260123"
        }

        return $id;
    }

    /**
     * Build normalized record array from EnrollmentApplicant model.
     */
    private function buildFromApplicant(EnrollmentApplicant $applicant): array
    {
        $displayId = $applicant->amis_student_id;
        $isApproved = $applicant->status === 'approved';

        if (!$displayId) {
            // Fallback to TEMP- format
            $year = substr($applicant->school_year ?? '2026-2027', 0, 4);
            $displayId = 'TEMP-' . $year . '-' . str_pad($applicant->id, 6, '0', STR_PAD_LEFT);
        } else {
            // Format numeric ID as AMIS-YYYY-XXXXXX for display
            if (is_numeric($displayId) && strlen($displayId) >= 6) {
                $year = '20' . substr($displayId, 0, 2);
                $seq = (int) substr($displayId, 2);
                $displayId = 'AMIS-' . $year . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
            }
        }

        // Status mapping
        $status = 'Temporary';
        if ($isApproved) {
            $status = 'Officially Enrolled';
        } elseif (in_array($applicant->status, ['pending', 'submitted', 'under_review'], true)) {
            $status = 'Pending';
        }

        // Parent Name fallback
        $father = trim(($applicant->father_first_name ?? '') . ' ' . ($applicant->father_last_name ?? ''));
        $mother = trim(($applicant->mother_first_name ?? '') . ' ' . ($applicant->mother_last_name ?? ''));
        $parent = $father ?: ($mother ?: null);

        return [
            'display_id'  => $displayId,
            'full_name'   => mb_strtoupper($applicant->full_name),
            'grade_level' => $applicant->grade_level,
            'school_year' => $applicant->school_year,
            'status'      => $status,
            'photo_url'   => $applicant->photo_2x2_url ? asset('storage/' . $applicant->photo_2x2_url) : null,
            'qr_code_url' => null,
            'parent_name' => $parent,
            'address'     => $applicant->address ?: $applicant->home_address,
        ];
    }

    /**
     * Build normalized record array from Student model.
     */
    private function buildFromStudent(Student $student): array
    {
        $applicant = $student->applicant;
        $displayId = $student->student_number;

        if (is_numeric($displayId) && strlen($displayId) >= 6) {
            $year = '20' . substr($displayId, 0, 2);
            $seq = (int) substr($displayId, 2);
            $displayId = 'AMIS-' . $year . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
        }

        // Parent Name fallback
        $father = trim(($applicant->father_first_name ?? '') . ' ' . ($applicant->father_last_name ?? ''));
        $mother = trim(($applicant->mother_first_name ?? '') . ' ' . ($applicant->mother_last_name ?? ''));
        $parent = $father ?: ($mother ?: null);

        return [
            'display_id'  => $displayId,
            'full_name'   => mb_strtoupper($applicant->full_name),
            'grade_level' => $student->grade_level,
            'school_year' => $student->school_year,
            'status'      => 'Officially Enrolled',
            'photo_url'   => $applicant->photo_2x2_url ? asset('storage/' . $applicant->photo_2x2_url) : null,
            'qr_code_url' => null,
            'parent_name' => $parent,
            'address'     => $applicant->address ?: $applicant->home_address,
        ];
    }

    /**
     * Search student by normalized ID in the parent-directory CSVs.
     */
    private function searchCsv(string $normalizedId): ?array
    {
        $csvPaths = [
            base_path('../AMIS_Verification_Database_Latest.csv'),
            base_path('../AMIS_F2F_Verification_Database_Latest.csv'),
        ];

        foreach ($csvPaths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            if (($handle = fopen($path, 'r')) !== false) {
                $headers = fgetcsv($handle);
                if (!$headers) {
                    fclose($handle);
                    continue;
                }

                $studentIdIdx  = array_search('Student_ID', $headers);
                $fullNameIdx   = array_search('Full_Name', $headers);
                $gradeLevelIdx = array_search('Grade_Level', $headers);
                $photoUrlIdx   = array_search('Photo_URL', $headers);
                $qrUrlIdx      = array_search('QR_Code_URL', $headers);
                $parentNameIdx = array_search('Parent_Full_Name', $headers);
                $addressIdx    = array_search('Address', $headers);

                if ($studentIdIdx === false || $fullNameIdx === false || $gradeLevelIdx === false) {
                    fclose($handle);
                    continue;
                }

                while (($row = fgetcsv($handle)) !== false) {
                    if (trim($row[$studentIdIdx]) === $normalizedId) {
                        $displayId = trim($row[$studentIdIdx]);
                        if (is_numeric($displayId) && strlen($displayId) >= 6) {
                            $year = '20' . substr($displayId, 0, 2);
                            $seq = (int) substr($displayId, 2);
                            $displayId = 'AMIS-' . $year . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
                        }

                        $res = [
                            'display_id'  => $displayId,
                            'full_name'   => mb_strtoupper(trim($row[$fullNameIdx])),
                            'grade_level' => trim($row[$gradeLevelIdx]),
                            'school_year' => '2026-2027', // CSV defaults to current school year
                            'status'      => 'Officially Enrolled',
                            'photo_url'   => $photoUrlIdx !== false ? trim($row[$photoUrlIdx]) : null,
                            'qr_code_url' => $qrUrlIdx !== false ? trim($row[$qrUrlIdx]) : null,
                            'parent_name' => $parentNameIdx !== false ? trim($row[$parentNameIdx]) : null,
                            'address'     => $addressIdx !== false ? trim($row[$addressIdx]) : null,
                        ];
                        fclose($handle);
                        return $res;
                    }
                }
                fclose($handle);
            }
        }

        return null;
    }

    /**
     * Compare input name and record name for security and loose verification.
     */
    private function nameMatches(string $entered, string $record): bool
    {
        $enteredClean = preg_replace('/[^a-z0-9 ]/', '', strtolower($entered));
        $recordClean  = preg_replace('/[^a-z0-9 ]/', '', strtolower(str_replace(['ñ', 'Ñ'], 'n', $record)));

        if ($enteredClean === $recordClean) {
            return true;
        }

        $enteredTokens = array_filter(explode(' ', $enteredClean));
        $recordTokens  = array_filter(explode(' ', $recordClean));

        if (count($enteredTokens) < 2) {
            return false;
        }

        $matchCount = 0;
        foreach ($enteredTokens as $token) {
            if (in_array($token, $recordTokens, true)) {
                $matchCount++;
            }
        }

        return $matchCount >= 2 && $matchCount >= (count($enteredTokens) - 1);
    }
}

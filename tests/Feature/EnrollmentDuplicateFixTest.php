<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnrollmentDuplicateFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_draft_autosave_updates_existing_record_and_keeps_count_at_1(): void
    {
        $user = User::factory()->create();

        // 1. Initial draft autosave
        $res1 = $this->actingAs($user)->postJson('/enroll/draft', [
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'school_year' => '2026-2027',
            'last_step' => 1,
        ]);

        $res1->assertOk();
        $applicantId = $res1->json('applicant_id');
        $this->assertNotEmpty($applicantId);
        $this->assertDatabaseCount('enrollment_applicants', 1);

        // 2. Subsequent autosaves with applicant_id provided
        for ($i = 2; $i <= 5; $i++) {
            $res = $this->actingAs($user)->postJson('/enroll/draft', [
                'applicant_id' => $applicantId,
                'student_type' => 'New',
                'grade_level' => 'Grade 1',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'street_address' => "Updated Address Step {$i}",
                'school_year' => '2026-2027',
                'last_step' => $i,
            ]);
            $res->assertOk();
            $this->assertSame($applicantId, $res->json('applicant_id'));
        }

        // Database count MUST remain exactly 1
        $this->assertDatabaseCount('enrollment_applicants', 1);
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $applicantId,
            'user_id' => $user->id,
            'status' => 'draft',
            'street_address' => 'Updated Address Step 5',
        ]);
    }

    public function test_final_submit_updates_same_record_id_and_changes_status_to_submitted(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        // Create initial draft #1565
        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'grade_level' => 'Grade 1',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'gender' => 'Female',
            'date_of_birth' => '2018-01-01',
            'place_of_birth' => 'Davao City',
            'religion' => 'Islam',
            'ethnicity' => 'Filipino',
            'country' => 'Philippines',
            'street_address' => 'Sample St',
            'mobile_country_code' => '+63',
            'mobile_number' => '9123456789',
            'parent_country_code' => '+63',
            'parent_mobile' => '9123456789',
            'medical_has_concern' => 'No',
            'emergency_name' => 'Mother Smith',
            'emergency_relationship' => 'Mother',
            'emergency_phone' => '9123456789',
            'school_year' => '2026-2027',
            'last_step' => 6,
        ]);

        $draftId = $draft->id;

        // Perform final submit
        $payload = array_merge($draft->toArray(), [
            'applicant_id' => $draftId,
            'agreed_to_terms' => '1',
            'agreed_to_fee_policy' => '1',
            'agreed_to_data_privacy' => '1',
            'photo_2x2' => UploadedFile::fake()->create('photo.jpg', 20, 'image/jpeg'),
            'birth_cert' => UploadedFile::fake()->create('birth.jpg', 20, 'image/jpeg'),
            'report_card' => UploadedFile::fake()->create('report.jpg', 20, 'image/jpeg'),
        ]);

        $response = $this->actingAs($user)->post('/enroll', $payload);

        $response->assertRedirect();

        // 1. Same primary key #1565 MUST be updated
        $this->assertDatabaseCount('enrollment_applicants', 1);
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draftId,
            'user_id' => $user->id,
            'status' => 'submitted',
        ]);
    }

    public function test_dashboard_places_submitted_application_under_submitted_only(): void
    {
        $user = User::factory()->create();

        $applicant = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'submitted',
            'student_type' => 'New',
            'first_name' => 'Submitted',
            'last_name' => 'Student',
            'school_year' => '2026-2027',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/enrollment/dashboard');

        $response->assertOk();
        $response->assertViewHas('readyApplications', fn ($col) => $col->isEmpty());
        $response->assertViewHas('draftApplications', fn ($col) => $col->isEmpty());
        
        $applicants = $response->viewData('applicants');
        $this->assertCount(1, $applicants);
        $this->assertSame('submitted', $applicants->first()->status);
    }

    public function test_incomplete_submission_preserves_draft_status_and_does_not_create_duplicate(): void
    {
        $user = User::factory()->create();

        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Incomplete',
            'last_name' => 'Student',
            'school_year' => '2026-2027',
            'last_step' => 2,
        ]);

        $draftId = $draft->id;

        // Submit with missing required fields (e.g. missing grade_level, date_of_birth, etc.)
        $response = $this->actingAs($user)->post('/enroll', [
            'applicant_id' => $draftId,
            'student_type' => 'New',
            'first_name' => 'Incomplete',
            'last_name' => 'Student',
        ]);

        $response->assertSessionHasErrors();

        // Must remain draft #draftId and MUST NOT create another row
        $this->assertDatabaseCount('enrollment_applicants', 1);
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draftId,
            'status' => 'draft',
        ]);
    }

    public function test_double_submit_request_is_idempotent_and_creates_no_second_record(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'submitted',
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'grade_level' => 'Grade 1',
            'first_name' => 'Already',
            'last_name' => 'Submitted',
            'school_year' => '2026-2027',
            'submitted_at' => now(),
        ]);

        $draftId = $draft->id;

        // Repeated submit request for already submitted application
        $response = $this->actingAs($user)->post('/enroll', [
            'applicant_id' => $draftId,
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'grade_level' => 'Grade 1',
            'last_name' => 'Submitted',
            'first_name' => 'Already',
            'school_year' => '2026-2027',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('enrollment_applicants', 1);
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draftId,
            'status' => 'submitted',
        ]);
    }

    public function test_autosave_after_final_submit_does_not_revert_status_to_draft_or_create_new_draft(): void
    {
        $user = User::factory()->create();

        $submitted = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'submitted',
            'student_type' => 'New',
            'first_name' => 'Finalized',
            'last_name' => 'Applicant',
            'school_year' => '2026-2027',
            'submitted_at' => now(),
        ]);

        $submittedId = $submitted->id;

        // Late autosave arrives after final submission
        $response = $this->actingAs($user)->postJson('/enroll/draft', [
            'applicant_id' => $submittedId,
            'student_type' => 'New',
            'first_name' => 'LateAutosave',
            'last_name' => 'Applicant',
            'school_year' => '2026-2027',
            'last_step' => 6,
        ]);

        $response->assertOk();

        // Database MUST still contain only 1 record, and status MUST remain submitted!
        $this->assertDatabaseCount('enrollment_applicants', 1);
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $submittedId,
            'status' => 'submitted',
            'first_name' => 'Finalized',
        ]);
    }

    public function test_dashboard_refresh_after_submission_does_not_auto_create_draft(): void
    {
        $user = User::factory()->create();

        EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'submitted',
            'student_type' => 'New',
            'first_name' => 'Done',
            'last_name' => 'User',
            'school_year' => '2026-2027',
            'submitted_at' => now(),
        ]);

        // Visit dashboard
        $this->actingAs($user)->get('/enrollment/dashboard')->assertOk();

        // Visit form without parameters (redirects to dashboard because application is finalized & locked)
        $this->actingAs($user)->get('/enroll')->assertRedirect(route('enrollment.dashboard', absolute: false));

        // No new draft should be auto-created in database
        $this->assertDatabaseCount('enrollment_applicants', 1);
    }
}

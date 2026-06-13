<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnrollmentDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_discard_own_draft(): void
    {
        $user = User::factory()->create();

        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Old',
            'last_name' => 'Draft',
            'school_year' => '2026-2027',
            'last_step' => 2,
        ]);

        $response = $this->actingAs($user)->delete('/enroll/draft', [
            'applicant_id' => $draft->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('clear_draft_cache', true);
        $this->assertDatabaseMissing('enrollment_applicants', [
            'id' => $draft->id,
        ]);
    }

    public function test_discard_draft_marks_next_form_load_to_skip_browser_cache_restore(): void
    {
        $user = User::factory()->create();

        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Cached',
            'last_name' => 'Draft',
            'school_year' => '2026-2027',
            'last_step' => 3,
        ]);

        $response = $this->followingRedirects()
            ->actingAs($user)
            ->delete('/enroll/draft', [
                'applicant_id' => $draft->id,
            ]);

        $response->assertOk();
        $response->assertSee('Draft cleared. You can start a fresh enrollment form.');
    }

    public function test_discard_draft_without_selected_applicant_does_not_delete_latest_draft(): void
    {
        $user = User::factory()->create();

        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Keep',
            'last_name' => 'Draft',
            'school_year' => '2026-2027',
            'last_step' => 2,
        ]);

        $response = $this->actingAs($user)->delete('/enroll/draft');

        $response->assertRedirect(route('enrollment.dashboard', absolute: false));
        $response->assertSessionHas('error', 'We could not find that child draft. Please refresh and try again.');
        $response->assertSessionMissing('clear_draft_cache');
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draft->id,
            'status' => 'draft',
        ]);
    }

    public function test_discard_draft_can_use_current_session_applicant_as_safe_fallback(): void
    {
        $user = User::factory()->create();

        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Session',
            'last_name' => 'Draft',
            'school_year' => '2026-2027',
            'last_step' => 2,
        ]);

        $response = $this->withSession(['current_enrollment_applicant_id' => $draft->id])
            ->actingAs($user)
            ->delete('/enroll/draft');

        $response->assertRedirect(route('enrollment.dashboard', absolute: false));
        $response->assertSessionHas('clear_draft_cache', true);
        $response->assertSessionHas('discarded_draft_applicant_id', $draft->id);
        $this->assertDatabaseMissing('enrollment_applicants', [
            'id' => $draft->id,
        ]);
    }

    public function test_discard_draft_does_not_delete_submitted_application(): void
    {
        $user = User::factory()->create();

        $applicant = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'student_type' => 'New',
            'first_name' => 'Submitted',
            'last_name' => 'Applicant',
            'school_year' => '2026-2027',
            'last_step' => 7,
        ]);

        $this->actingAs($user)->delete('/enroll/draft');

        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $applicant->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_remove_a_document_from_draft(): void
    {
        $user = User::factory()->create();

        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Doc',
            'last_name' => 'Draft',
            'photo_2x2_url' => 'documents/photo.jpg',
            'school_year' => '2026-2027',
            'last_step' => 7,
        ]);

        $response = $this->actingAs($user)->delete('/enroll/draft/document/photo_2x2');

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draft->id,
            'photo_2x2_url' => null,
        ]);
    }

    public function test_autosaving_rejected_application_keeps_it_rejected_until_final_submit(): void
    {
        $user = User::factory()->create();

        $applicant = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'rejected',
            'student_type' => 'New',
            'first_name' => 'Returned',
            'last_name' => 'Applicant',
            'school_year' => '2026-2027',
            'last_step' => 6,
            'document_statuses' => ['photo_2x2' => 'rejected'],
        ]);

        $response = $this->actingAs($user)->postJson('/enroll/draft', [
            'applicant_id' => $applicant->id,
            'student_type' => 'New',
            'first_name' => 'Returned',
            'last_name' => 'Applicant',
            'school_year' => '2026-2027',
            'last_step' => 6,
        ]);

        $response->assertOk();
        $this->assertSame('rejected', $applicant->fresh()->status);
    }

    public function test_rejected_application_with_existing_payment_resubmits_to_review(): void
    {
        $user = User::factory()->create();

        $applicant = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'family_application_id' => 1001,
            'status' => 'rejected',
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'grade_level' => 'Kinder 2',
            'first_name' => 'Returned',
            'last_name' => 'Applicant',
            'gender' => 'Male',
            'date_of_birth' => '2020-01-01',
            'place_of_birth' => 'Davao City',
            'religion' => 'Islam',
            'country' => 'Philippines',
            'street_address' => 'Sample Street',
            'address' => 'Sample Street, Philippines',
            'mobile_country_code' => '+63',
            'mobile_number' => '9123456789',
            'parent_country_code' => '+63',
            'parent_mobile' => '9123456789',
            'medical_has_concern' => 'No',
            'emergency_name' => 'Parent Applicant',
            'emergency_relationship' => 'Mother',
            'emergency_phone' => '9123456789',
            'photo_2x2_url' => 'documents/old-photo.jpg',
            'school_year' => '2026-2027',
            'last_step' => 6,
            'document_statuses' => [
                'photo_2x2' => 'rejected',
                'payment_proof' => 'approved',
            ],
            'review_remarks' => 'Please re-upload a clear photo.',
        ]);

        $applicant->payment()->create([
            'user_id' => $user->id,
            'method' => 'gcash',
            'amount' => 4000,
            'receipt_url' => 'documents/payment.jpg',
            'status' => 'verified',
            'paid_at' => now(),
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/enroll', [
            'applicant_id' => $applicant->id,
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'grade_level' => 'Kinder 2',
            'last_name' => 'Applicant',
            'first_name' => 'Returned',
            'gender' => 'Male',
            'date_of_birth' => '2020-01-01',
            'place_of_birth' => 'Davao City',
            'religion' => 'Islam',
            'country' => 'Philippines',
            'street_address' => 'Sample Street',
            'mobile_country_code' => '+63',
            'mobile_number' => '9123456789',
            'parent_country_code' => '+63',
            'parent_mobile' => '9123456789',
            'medical_has_concern' => 'No',
            'emergency_name' => 'Parent Applicant',
            'emergency_relationship' => 'Mother',
            'emergency_phone' => '9123456789',
            'school_year' => '2026-2027',
        ]);

        $response->assertRedirect(route('enrollment.dashboard', ['applicant' => $applicant->id], false));

        $freshApplicant = $applicant->fresh();
        $this->assertSame('submitted', $freshApplicant->status);
        $this->assertNull($freshApplicant->review_remarks);
        $this->assertSame(['payment_proof' => 'approved'], $freshApplicant->document_statuses);
    }

    public function test_pdf_document_upload_is_rejected_for_drafts(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/enroll/draft', [
            'school_year' => '2026-2027',
            'last_step' => 7,
            'photo_2x2' => UploadedFile::fake()->create('photo.pdf', 20, 'application/pdf'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('photo_2x2');
        $this->assertDatabaseCount('enrollment_applicants', 0);
    }

    public function test_timezone_is_saved_and_restored_from_database_draft(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/enroll/draft', [
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'learning_mode' => 'Flexible Online Learning - 1st Shift',
            'timezone' => 'Asia/Dubai',
            'school_year' => '2026-2027',
            'last_step' => 1,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('enrollment_applicants', [
            'user_id' => $user->id,
            'status' => 'draft',
            'timezone' => 'Asia/Dubai',
        ]);

        $this->actingAs($user)
            ->get('/enroll')
            ->assertOk()
            ->assertViewHas('applicant', fn ($applicant) => $applicant?->timezone === 'Asia/Dubai');
    }

    public function test_save_draft_returns_applicant_id_and_dashboard_shows_saved_draft(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/enroll/draft', [
            'student_type' => 'New',
            'grade_level' => 'Grade 9',
            'learning_mode' => 'Face-to-Face',
            'school_year' => '2026-2027',
            'last_step' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $applicantId = $response->json('applicant_id');

        $this->assertNotEmpty($applicantId);
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $applicantId,
            'user_id' => $user->id,
            'status' => 'draft',
            'grade_level' => 'Grade 9',
        ]);

        $this->actingAs($user)
            ->get('/enrollment/dashboard?applicant=' . $applicantId)
            ->assertOk()
            ->assertSee('Enrollment Applications')
            ->assertSee('Continue Draft');
    }

    public function test_incomplete_draft_cannot_open_payment(): void
    {
        $user = User::factory()->create();

        EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Partial',
            'last_name' => 'Draft',
            'school_year' => '2026-2027',
            'last_step' => 3,
        ]);

        $response = $this->actingAs($user)->get('/enrollment/payment');

        $response->assertRedirect();
        $response->assertSessionHas('info', 'Please complete your enrollment application first.');
    }

    public function test_complete_draft_cannot_open_payment_before_final_submission(): void
    {
        $user = User::factory()->create();

        EnrollmentApplicant::create($this->completeDraftData($user->id));

        $response = $this->actingAs($user)->get('/enrollment/payment');

        $response->assertRedirect(route('enrollment.dashboard', absolute: false));
        $response->assertSessionHas('info', 'Please complete your enrollment application first.');
    }

    public function test_child_form_submission_marks_application_ready_for_final_submission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/enroll', $this->validSubmissionPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment_applicants', [
            'user_id' => $user->id,
            'status' => 'ready_for_submission',
            'first_name' => 'Ready',
            'last_name' => 'Child',
        ]);
    }

    public function test_finalize_enrollment_submits_ready_applications(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $ready = EnrollmentApplicant::create(array_merge($this->completeDraftData($user->id), [
            'status' => 'ready_for_submission',
            'first_name' => 'Ready',
            'last_name' => 'Child',
        ]));

        $this->actingAs($user)
            ->get('/enrollment/finalize')
            ->assertRedirect(route('enrollment.dashboard', absolute: false));

        $this->actingAs($user)
            ->post('/enrollment/finalize')
            ->assertRedirect(route('enrollment.dashboard', absolute: false));

        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $ready->id,
            'status' => 'submitted',
        ]);
    }

    public function test_finalize_enrollment_waits_until_child_drafts_are_completed(): void
    {
        $user = User::factory()->create();

        EnrollmentApplicant::create(array_merge($this->completeDraftData($user->id), [
            'status' => 'ready_for_submission',
            'first_name' => 'Ready',
            'last_name' => 'Child',
        ]));
        $draft = EnrollmentApplicant::create(array_merge($this->completeDraftData($user->id), [
            'status' => 'draft',
            'first_name' => 'Draft',
            'last_name' => 'Child',
        ]));

        $response = $this->actingAs($user)->get('/enrollment/finalize');

        $response->assertRedirect(route('enrollment.dashboard', absolute: false));
        $response->assertSessionHas('info', 'Please complete all child drafts before finalizing enrollment.');
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draft->id,
            'status' => 'draft',
        ]);
    }

    public function test_add_another_child_is_blocked_until_first_child_is_ready(): void
    {
        $user = User::factory()->create();

        EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'school_year' => '2026-2027',
            'last_step' => 2,
        ]);

        $response = $this->actingAs($user)->get('/enroll/new');

        $response->assertRedirect(route('enrollment.dashboard', absolute: false));
        $response->assertSessionHas('info', 'Please complete your current child application first before adding another child.');
        $this->assertDatabaseCount('enrollment_applicants', 1);
    }

    public function test_enrollment_form_renders_cancel_confirmation_prompt(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/enroll');

        $response->assertOk();
        $response->assertSee('Leave this enrollment form?');
        $response->assertSee('confirm-overlay');
        $response->assertSee('Discard Unsaved Draft');
    }

    public function test_fresh_enrollment_form_ignores_current_session_draft_and_skips_local_restore(): void
    {
        $user = User::factory()->create();

        $draft = EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'student_type' => 'New',
            'grade_level' => 'Grade 9',
            'learning_mode' => 'Face-to-Face',
            'school_year' => '2026-2027',
            'last_step' => 3,
        ]);

        $response = $this->withSession(['current_enrollment_applicant_id' => $draft->id])
            ->actingAs($user)
            ->get('/enroll?fresh=1');

        $response->assertOk();
        $response->assertViewHas('applicant', null);
        $response->assertSee('const START_FRESH_FORM = true;', false);
        $response->assertSee("grade_level: ''", false);
        $response->assertSee("learning_mode: ''", false);
    }

    public function test_dashboard_new_enrollment_link_starts_clean_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/enrollment/dashboard');

        $response->assertOk();
        $response->assertSee(route('enrollment.form', ['fresh' => 1], absolute: false), false);
    }

    public function test_fresh_enrollment_form_starts_blank_on_step_one(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/enroll');

        $response->assertOk();
        $response->assertSee("student_type: ''", false);
        $response->assertSee("learning_mode: ''", false);
        $response->assertSee("ethnicity: ''", false);
        $response->assertSee("mobile_country_code: ''", false);
        $response->assertSee("parent_country_code: ''", false);
        $response->assertSee('step: 1', false);
    }

    public function test_add_another_child_starts_clean_draft_and_detects_sibling_discount(): void
    {
        $user = User::factory()->create();

        EnrollmentApplicant::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'student_type' => 'New',
            'first_name' => 'First',
            'last_name' => 'Child',
            'grade_level' => 'Grade 1',
            'parent_country_code' => '+63',
            'parent_mobile' => '9123456789',
            'parent_email' => 'parent@example.com',
            'father_first_name' => 'Father',
            'mother_first_name' => 'Mother',
            'referral_source' => 'Facebook',
            'medical_has_concern' => 'Yes',
            'allergies' => 'Peanuts',
            'emergency_name' => 'Parent Guardian',
            'emergency_relationship' => 'Parent',
            'emergency_phone' => '9123456789',
            'school_year' => '2026-2027',
            'last_step' => 6,
        ]);

        $response = $this->actingAs($user)->get('/enroll/new');

        $response->assertRedirect();

        $draft = EnrollmentApplicant::where('user_id', $user->id)
            ->where('status', 'draft')
            ->latest()
            ->first();

        $this->assertNotNull($draft);
        $this->assertNull($draft->parent_mobile);
        $this->assertNull($draft->parent_email);
        $this->assertNull($draft->first_name);
        $this->assertNull($draft->photo_2x2_url);
        $this->assertNull($draft->medical_has_concern);
        $this->assertNull($draft->allergies);
        $this->assertNull($draft->emergency_name);
        $this->assertNull($draft->referral_source);
        $this->assertSame(1, $draft->last_step);
        $this->assertSame(2, $draft->sibling_order);
        $this->assertSame('sibling', $draft->discount_type);
        $this->assertSame('10.00', $draft->discount_percentage);
    }

    private function completeDraftData(int $userId): array
    {
        return [
            'user_id' => $userId,
            'status' => 'draft',
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'grade_level' => 'Grade 1',
            'first_name' => 'Complete',
            'last_name' => 'Draft',
            'gender' => 'Male',
            'date_of_birth' => '2018-01-01',
            'place_of_birth' => 'Manila',
            'religion' => 'Islam',
            'ethnicity' => 'Filipino',
            'country' => 'Philippines',
            'street_address' => '123 Sample Street',
            'mobile_country_code' => '+63',
            'mobile_number' => '9123456789',
            'parent_country_code' => '+63',
            'parent_mobile' => '9123456789',
            'medical_has_concern' => 'No',
            'emergency_name' => 'Parent Guardian',
            'emergency_relationship' => 'Parent',
            'emergency_phone' => '9123456789',
            'photo_2x2_url' => 'documents/photo.jpg',
            'birth_cert_url' => 'documents/birth.png',
            'report_card_url' => 'documents/report.png',
            'school_year' => '2026-2027',
            'last_step' => 6,
        ];
    }

    private function validSubmissionPayload(): array
    {
        return [
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'lrn' => '123456789012',
            'grade_level' => 'Grade 1',
            'last_name' => 'Child',
            'first_name' => 'Ready',
            'gender' => 'Male',
            'date_of_birth' => '2018-01-01',
            'place_of_birth' => 'Manila',
            'religion' => 'Islam',
            'ethnicity' => 'Filipino',
            'country' => 'Philippines',
            'street_address' => '123 Sample Street',
            'mobile_country_code' => '+63',
            'mobile_number' => '9123456789',
            'parent_country_code' => '+63',
            'parent_mobile' => '9123456789',
            'parent_email' => 'parent@example.com',
            'medical_has_concern' => 'No',
            'emergency_name' => 'Parent Guardian',
            'emergency_relationship' => 'Parent',
            'emergency_phone' => '9123456789',
            'agreed_to_terms' => '1',
            'agreed_to_fee_policy' => '1',
            'agreed_to_data_privacy' => '1',
            'school_year' => '2026-2027',
            'photo_2x2' => UploadedFile::fake()->create('photo.jpg', 20, 'image/jpeg'),
            'birth_cert' => UploadedFile::fake()->create('birth.jpg', 20, 'image/jpeg'),
            'report_card' => UploadedFile::fake()->create('report.jpg', 20, 'image/jpeg'),
        ];
    }

    public function test_user_cannot_access_another_users_applicant_id(): void
    {
        $user1 = User::factory()->create(['account_status' => 'verified', 'email_verified_at' => now()]);
        $user2 = User::factory()->create(['account_status' => 'verified', 'email_verified_at' => now()]);

        $applicant2 = EnrollmentApplicant::create([
            'user_id' => $user2->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'school_year' => '2026-2027',
            'last_step' => 2,
        ]);

        $response = $this->actingAs($user1)->get('/enroll/' . $applicant2->id);
        $response->assertStatus(403);

        $responseDashboard = $this->actingAs($user1)->get('/enrollment/dashboard?applicant=' . $applicant2->id);
        $responseDashboard->assertStatus(403);
    }

    public function test_user_cannot_discard_another_users_draft(): void
    {
        $user1 = User::factory()->create(['account_status' => 'verified', 'email_verified_at' => now()]);
        $user2 = User::factory()->create(['account_status' => 'verified', 'email_verified_at' => now()]);

        $draft2 = EnrollmentApplicant::create([
            'user_id' => $user2->id,
            'status' => 'draft',
            'student_type' => 'New',
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'school_year' => '2026-2027',
            'last_step' => 2,
        ]);

        $response = $this->actingAs($user1)->delete('/enroll/draft', [
            'applicant_id' => $draft2->id,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draft2->id,
        ]);
    }
}

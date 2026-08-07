<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UploadValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_proof_accepts_png_jpg_jpeg_and_rejects_pdf(): void
    {
        $user = User::factory()->create();

        // 1. Payment receipt PNG - Allowed
        $resPng = $this->actingAs($user)->postJson('/enroll/draft', [
            'payment_receipt' => UploadedFile::fake()->create('receipt.png', 100, 'image/png'),
        ]);
        $resPng->assertJsonMissingValidationErrors('payment_receipt');

        // 2. Payment receipt JPG - Allowed
        $resJpg = $this->actingAs($user)->postJson('/enroll/draft', [
            'payment_receipt' => UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'),
        ]);
        $resJpg->assertJsonMissingValidationErrors('payment_receipt');

        // 3. Payment receipt PDF - Rejected
        $resPdf = $this->actingAs($user)->postJson('/enroll/draft', [
            'payment_receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);
        $resPdf->assertJsonValidationErrors('payment_receipt');
    }

    public function test_report_card_accepts_pdf_jpg_jpeg_and_rejects_png(): void
    {
        $user = User::factory()->create();

        // 1. Report card PDF - Allowed
        $resPdf = $this->actingAs($user)->postJson('/enroll/draft', [
            'report_card' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ]);
        $resPdf->assertJsonMissingValidationErrors('report_card');

        // 2. Report card JPG - Allowed
        $resJpg = $this->actingAs($user)->postJson('/enroll/draft', [
            'report_card' => UploadedFile::fake()->create('report.jpg', 100, 'image/jpeg'),
        ]);
        $resJpg->assertJsonMissingValidationErrors('report_card');

        // 3. Report card PNG - Rejected
        $resPng = $this->actingAs($user)->postJson('/enroll/draft', [
            'report_card' => UploadedFile::fake()->create('report.png', 100, 'image/png'),
        ]);
        $resPng->assertJsonValidationErrors('report_card');
    }

    public function test_photo_2x2_accepts_png_jpg_jpeg(): void
    {
        $user = User::factory()->create();

        foreach (['photo.png' => 'image/png', 'photo.jpg' => 'image/jpeg', 'photo.jpeg' => 'image/jpeg'] as $filename => $mime) {
            $res = $this->actingAs($user)->postJson('/enroll/draft', [
                'photo_2x2' => UploadedFile::fake()->create($filename, 100, $mime),
            ]);
            $res->assertJsonMissingValidationErrors('photo_2x2');
        }
    }

    public function test_laravel_file_size_restrictions_removed_for_uploads(): void
    {
        $user = User::factory()->create();

        // 8MB fake file (previously failed max:5120)
        $largePhoto = UploadedFile::fake()->create('large_photo.jpg', 8192, 'image/jpeg');

        $res = $this->actingAs($user)->postJson('/enroll/draft', [
            'photo_2x2' => $largePhoto,
        ]);

        $res->assertJsonMissingValidationErrors('photo_2x2');
    }

    public function test_duplicate_enrollment_via_ajax_returns_422_json_with_structured_error(): void
    {
        Mail::fake();

        // Active existing applicant
        $existing = EnrollmentApplicant::create([
            'user_id' => User::factory()->create()->id,
            'status' => 'submitted',
            'first_name' => 'JUAN',
            'last_name' => 'DELA CRUZ',
            'grade_level' => 'Grade 11',
            'school_year' => '2026-2027',
        ]);

        $currentUser = User::factory()->create();
        $draft = EnrollmentApplicant::create([
            'user_id' => $currentUser->id,
            'status' => 'draft',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'grade_level' => 'Grade 11',
            'school_year' => '2026-2027',
        ]);

        $payload = [
            'applicant_id' => $draft->id,
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'grade_level' => 'Grade 11',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'gender' => 'Male',
            'date_of_birth' => '2008-05-15',
            'place_of_birth' => 'Davao City',
            'religion' => 'Islam',
            'ethnicity' => 'Filipino',
            'country' => 'Philippines',
            'street_address' => 'Sample Street',
            'mobile_country_code' => '+63',
            'mobile_number' => '9123456789',
            'parent_country_code' => '+63',
            'parent_mobile' => '9123456789',
            'medical_has_concern' => 'No',
            'emergency_name' => 'Parent Dela Cruz',
            'emergency_relationship' => 'Parent',
            'emergency_phone' => '9123456789',
            'agreed_to_terms' => '1',
            'agreed_to_fee_policy' => '1',
            'agreed_to_data_privacy' => '1',
            'school_year' => '2026-2027',
            'photo_2x2' => UploadedFile::fake()->create('photo.jpg', 50, 'image/jpeg'),
            'report_card' => UploadedFile::fake()->create('report.pdf', 50, 'application/pdf'),
            'payment_receipt' => UploadedFile::fake()->create('receipt.jpg', 50, 'image/jpeg'),
        ];

        $response = $this->actingAs($currentUser)->postJson('/enroll', $payload);

        // MUST return 422 JSON and NOT 500
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'code' => 'DUPLICATE_ENROLLMENT',
            'duplicate' => true,
        ]);
        $this->assertStringContainsString('Duplicate enrollment detected', $response->json('message'));

        // Draft remains intact as draft
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draft->id,
            'status' => 'draft',
        ]);
    }

    public function test_duplicate_enrollment_via_browser_form_returns_back_with_validation_errors(): void
    {
        Mail::fake();

        // Active existing applicant
        EnrollmentApplicant::create([
            'user_id' => User::factory()->create()->id,
            'status' => 'submitted',
            'first_name' => 'MARIA',
            'last_name' => 'SANTOS',
            'grade_level' => 'Grade 7',
            'school_year' => '2026-2027',
        ]);

        $currentUser = User::factory()->create();
        $draft = EnrollmentApplicant::create([
            'user_id' => $currentUser->id,
            'status' => 'draft',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'grade_level' => 'Grade 7',
            'school_year' => '2026-2027',
        ]);

        $payload = [
            'applicant_id' => $draft->id,
            'student_type' => 'New',
            'learning_mode' => 'Face-to-Face',
            'grade_level' => 'Grade 7',
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'gender' => 'Female',
            'date_of_birth' => '2012-03-10',
            'place_of_birth' => 'Davao City',
            'religion' => 'Islam',
            'ethnicity' => 'Filipino',
            'country' => 'Philippines',
            'street_address' => 'Sample Street',
            'mobile_country_code' => '+63',
            'mobile_number' => '9123456789',
            'parent_country_code' => '+63',
            'parent_mobile' => '9123456789',
            'medical_has_concern' => 'No',
            'emergency_name' => 'Parent Santos',
            'emergency_relationship' => 'Parent',
            'emergency_phone' => '9123456789',
            'agreed_to_terms' => '1',
            'agreed_to_fee_policy' => '1',
            'agreed_to_data_privacy' => '1',
            'school_year' => '2026-2027',
            'photo_2x2' => UploadedFile::fake()->create('photo.jpg', 50, 'image/jpeg'),
            'report_card' => UploadedFile::fake()->create('report.pdf', 50, 'application/pdf'),
            'payment_receipt' => UploadedFile::fake()->create('receipt.jpg', 50, 'image/jpeg'),
        ];

        $response = $this->actingAs($currentUser)->post('/enroll', $payload);

        // Standard web submission returns back with errors, NOT 500
        $response->assertStatus(302);
        $response->assertSessionHasErrors('duplicate');

        // Draft remains intact as draft
        $this->assertDatabaseHas('enrollment_applicants', [
            'id' => $draft->id,
            'status' => 'draft',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Models\WorkflowEdge;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Delete previous fragmented workflows to clean up
        Workflow::query()->delete();

        // ── MASTER ALL-IN-ONE ENROLLMENT AUTOMATION WORKFLOW ─────────────────
        $workflow = Workflow::create([
            'name'          => 'AMIS All-In-One Master Enrollment Pipeline',
            'description'   => 'Complete end-to-end automated enrollment workflow for AMIS. Handles submission receipts, admin alerts, status transitions, payment notifications, and official enrollment confirmation.',
            'trigger_event' => 'enrollment.submitted',
            'is_active'     => true,
            'created_by'    => null,
        ]);

        $nodes = [
            // STEP 1: Trigger
            [
                'node_key' => 'node_start',
                'type'     => 'trigger',
                'label'    => '1. Parent Submits Form',
                'config'   => ['event' => 'enrollment.submitted'],
                'x'        => 180,
                'y'        => 60,
            ],
            // STEP 2: Send Email Receipt to Parent
            [
                'node_key' => 'node_parent_receipt',
                'type'     => 'send_email',
                'label'    => '2. Send Receipt Email to Parent',
                'config'   => [
                    'subject' => 'AMIS Enrollment Received – {{student_name}}',
                    'body'    => "Assalamualaikum Warahmatullahi Wabarakatuh,\n\nDear Parent/Guardian,\n\nWe have successfully received the online enrollment application for {{student_name}} (Grade {{grade_level}}) for School Year {{school_year}}.\n\nYour application is now officially recorded in our system and is currently being processed by the Registrar Office.\n\nSchool ID: 466150\nAl Munawwara Islamic School\nDon Julian Rodriguez Avenue, Ma-a, Davao City",
                ],
                'x'        => 180,
                'y'        => 200,
            ],
            // STEP 3: Change Status to Under Review
            [
                'node_key' => 'node_update_review',
                'type'     => 'change_status',
                'label'    => '3. Update Status → Under Review',
                'config'   => ['status' => 'under_review'],
                'x'        => 180,
                'y'        => 340,
            ],
            // STEP 4: Notify Registrar / Admin
            [
                'node_key' => 'node_notify_registrar',
                'type'     => 'notify_admin',
                'label'    => '4. Alert Registrar & Admin',
                'config'   => [
                    'admin_email' => 'amisonlinesupport@gmail.com',
                    'subject'     => '🔔 NEW ENROLLMENT: {{student_name}} (Grade {{grade_level}})',
                    'body'        => "A new enrollment application has been submitted and is ready for review.\n\nStudent: {{student_name}}\nGrade Level: {{grade_level}}\nSchool Year: {{school_year}}\nStatus: {{status}}\n\nPlease review documents at https://online.enrollment.amis.edu.ph",
                ],
                'x'        => 180,
                'y'        => 480,
            ],
            // STEP 5: Payment Proof Trigger Action
            [
                'node_key' => 'node_payment_ack',
                'type'     => 'send_email',
                'label'    => '5. Send Payment & Next Steps Guide',
                'config'   => [
                    'subject' => 'AMIS Payment & Verification Instructions – {{student_name}}',
                    'body'    => "Dear Parent/Guardian,\n\nTo complete the enrollment process for {{student_name}}, please upload your tuition fee payment proof via your portal dashboard:\n\nhttps://online.enrollment.amis.edu.ph/enrollment/payment\n\nThank you for choosing Al Munawwara Islamic School!",
                ],
                'x'        => 180,
                'y'        => 620,
            ],
        ];

        $edges = [
            ['edge_key' => 'edge_1', 'source_node_key' => 'node_start',          'target_node_key' => 'node_parent_receipt'],
            ['edge_key' => 'edge_2', 'source_node_key' => 'node_parent_receipt', 'target_node_key' => 'node_update_review'],
            ['edge_key' => 'edge_3', 'source_node_key' => 'node_update_review',  'target_node_key' => 'node_notify_registrar'],
            ['edge_key' => 'edge_4', 'source_node_key' => 'node_notify_registrar','target_node_key' => 'node_payment_ack'],
        ];

        foreach ($nodes as $n) {
            $workflow->nodes()->create([
                'node_key'   => $n['node_key'],
                'type'       => $n['type'],
                'label'      => $n['label'],
                'config'     => $n['config'],
                'position_x' => $n['x'],
                'position_y' => $n['y'],
            ]);
        }

        foreach ($edges as $e) {
            $workflow->edges()->create([
                'edge_key'        => $e['edge_key'],
                'source_node_key' => $e['source_node_key'],
                'target_node_key' => $e['target_node_key'],
                'condition_value' => null,
                'label'           => null,
            ]);
        }

        $this->command->info('✅ Master All-In-One AMIS Enrollment Workflow created!');
    }
}

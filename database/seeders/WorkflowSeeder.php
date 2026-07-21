<?php

namespace Database\Seeders;

use App\Models\Workflow;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // ── Workflow 1: New Enrollment Submitted ─────────────────────────────
        $this->createWorkflow(
            name: 'New Enrollment Submitted',
            description: 'Fires when a parent submits an enrollment form. Sends confirmation email to the parent/guardian.',
            trigger: 'enrollment.submitted',
            nodes: [
                ['node_key' => 'node_1', 'type' => 'trigger',    'label' => 'Enrollment Submitted',  'config' => [], 'x' => 120, 'y' => 80],
                ['node_key' => 'node_2', 'type' => 'send_email', 'label' => 'Send Confirmation Email', 'config' => [
                    'subject' => 'AMIS: Enrollment Received – {{student_name}}',
                    'body'    => "Dear Parent/Guardian,\n\nThank you! We have received the enrollment application for {{student_name}} (Grade {{grade_level}}) for School Year {{school_year}}.\n\nYour application is now under review. We will notify you of any updates.\n\nAssalamualaikum Warahmatullahi Wabarakatuh,\nAl Munawwara Islamic School\nSchool ID: 466150\nDon Julian Rodriguez Avenue, Ma-a, Davao City",
                ], 'x' => 120, 'y' => 240],
                ['node_key' => 'node_3', 'type' => 'change_status', 'label' => 'Mark as Under Review', 'config' => ['status' => 'under_review'], 'x' => 120, 'y' => 400],
                ['node_key' => 'node_4', 'type' => 'notify_admin', 'label' => 'Notify Admin', 'config' => [
                    'subject' => 'New Enrollment: {{student_name}}',
                    'body'    => "A new enrollment has been submitted.\n\nStudent: {{student_name}}\nGrade Level: {{grade_level}}\nSchool Year: {{school_year}}\nStatus: {{status}}\n\nPlease review in the AMIS Admin panel.",
                ], 'x' => 120, 'y' => 560],
            ],
            edges: [
                ['edge_key' => 'e1', 'source' => 'node_1', 'target' => 'node_2'],
                ['edge_key' => 'e2', 'source' => 'node_2', 'target' => 'node_3'],
                ['edge_key' => 'e3', 'source' => 'node_3', 'target' => 'node_4'],
            ],
        );

        // ── Workflow 2: Enrollment Approved ──────────────────────────────────
        $this->createWorkflow(
            name: 'Enrollment Approved',
            description: 'Fires when admin approves an enrollment. Notifies the parent to proceed to enrollment confirmation.',
            trigger: 'enrollment.approved',
            nodes: [
                ['node_key' => 'node_1', 'type' => 'trigger',    'label' => 'Enrollment Approved', 'config' => [], 'x' => 120, 'y' => 80],
                ['node_key' => 'node_2', 'type' => 'send_email', 'label' => 'Send Approval Email', 'config' => [
                    'subject' => 'AMIS: Enrollment APPROVED – {{student_name}} 🎉',
                    'body'    => "Dear Parent/Guardian,\n\nWe are pleased to inform you that the enrollment application for {{student_name}} (Grade {{grade_level}}) has been APPROVED for School Year {{school_year}}.\n\nPlease visit the enrollment portal to complete the enrollment process.\n\nhttps://enrollment.amis.edu.ph\n\nAssalamualaikum Warahmatullahi Wabarakatuh,\nAl Munawwara Islamic School",
                ], 'x' => 120, 'y' => 240],
            ],
            edges: [
                ['edge_key' => 'e1', 'source' => 'node_1', 'target' => 'node_2'],
            ],
        );

        // ── Workflow 3: Enrollment Rejected ──────────────────────────────────
        $this->createWorkflow(
            name: 'Enrollment Rejected',
            description: 'Fires when admin rejects an enrollment. Sends rejection email with instructions to resubmit.',
            trigger: 'enrollment.rejected',
            nodes: [
                ['node_key' => 'node_1', 'type' => 'trigger',    'label' => 'Enrollment Rejected', 'config' => [], 'x' => 120, 'y' => 80],
                ['node_key' => 'node_2', 'type' => 'send_email', 'label' => 'Send Rejection Email', 'config' => [
                    'subject' => 'AMIS: Action Required – Enrollment for {{student_name}}',
                    'body'    => "Dear Parent/Guardian,\n\nWe regret to inform you that the enrollment application for {{student_name}} (Grade {{grade_level}}) requires corrections.\n\nPlease log in to the enrollment portal to review the feedback and resubmit:\nhttps://enrollment.amis.edu.ph\n\nIf you have questions, please contact us.\n\nAssalamualaikum Warahmatullahi Wabarakatuh,\nAl Munawwara Islamic School",
                ], 'x' => 120, 'y' => 240],
            ],
            edges: [
                ['edge_key' => 'e1', 'source' => 'node_1', 'target' => 'node_2'],
            ],
        );

        // ── Workflow 4: Payment Submitted ────────────────────────────────────
        $this->createWorkflow(
            name: 'Payment Proof Submitted',
            description: 'Fires when a parent uploads payment proof. Notifies admin to verify payment.',
            trigger: 'enrollment.payment_submitted',
            nodes: [
                ['node_key' => 'node_1', 'type' => 'trigger',    'label' => 'Payment Submitted', 'config' => [], 'x' => 120, 'y' => 80],
                ['node_key' => 'node_2', 'type' => 'notify_admin', 'label' => 'Alert Admin: Payment Received', 'config' => [
                    'subject' => 'Payment Proof Uploaded – {{student_name}}',
                    'body'    => "A parent has uploaded payment proof.\n\nStudent: {{student_name}}\nGrade Level: {{grade_level}}\nSchool Year: {{school_year}}\n\nPlease verify in the AMIS Admin panel.",
                ], 'x' => 120, 'y' => 240],
                ['node_key' => 'node_3', 'type' => 'send_email', 'label' => 'Acknowledge to Parent', 'config' => [
                    'subject' => 'AMIS: Payment Received – {{student_name}}',
                    'body'    => "Dear Parent/Guardian,\n\nWe have received your payment submission for {{student_name}}.\n\nOur team will verify it within 1-2 business days. You will be notified once confirmed.\n\nAssalamualaikum Warahmatullahi Wabarakatuh,\nAl Munawwara Islamic School",
                ], 'x' => 380, 'y' => 240],
            ],
            edges: [
                ['edge_key' => 'e1', 'source' => 'node_1', 'target' => 'node_2'],
                ['edge_key' => 'e2', 'source' => 'node_1', 'target' => 'node_3'],
            ],
        );

        // ── Workflow 5: Enrollment Finalized / Enrolled ──────────────────────
        $this->createWorkflow(
            name: 'Enrollment Finalized',
            description: 'Fires when enrollment is fully confirmed. Sends welcome email to the student\'s family.',
            trigger: 'enrollment.finalized',
            nodes: [
                ['node_key' => 'node_1', 'type' => 'trigger',    'label' => 'Enrollment Finalized', 'config' => [], 'x' => 120, 'y' => 80],
                ['node_key' => 'node_2', 'type' => 'change_status', 'label' => 'Mark as Enrolled', 'config' => ['status' => 'enrolled'], 'x' => 120, 'y' => 240],
                ['node_key' => 'node_3', 'type' => 'send_email', 'label' => 'Send Welcome Email', 'config' => [
                    'subject' => 'AMIS: Welcome! {{student_name}} is now Enrolled 🎓',
                    'body'    => "Assalamualaikum Warahmatullahi Wabarakatuh!\n\nDear Parent/Guardian,\n\nAlhamdulillah! {{student_name}} is now officially enrolled at Al Munawwara Islamic School for School Year {{school_year}}.\n\nGrade Level: {{grade_level}}\n\nWelcome to the AMIS Family! May Allah bless your child's education.\n\nAl Munawwara Islamic School\nDon Julian Rodriguez Avenue, Ma-a, Davao City\nSchool ID: 466150",
                ], 'x' => 120, 'y' => 400],
            ],
            edges: [
                ['edge_key' => 'e1', 'source' => 'node_1', 'target' => 'node_2'],
                ['edge_key' => 'e2', 'source' => 'node_2', 'target' => 'node_3'],
            ],
        );

        $this->command->info('✅ 5 AMIS Workflows seeded successfully!');
    }

    private function createWorkflow(string $name, string $description, string $trigger, array $nodes, array $edges): void
    {
        // Skip if already exists
        if (Workflow::where('name', $name)->exists()) {
            $this->command->warn("⚠️  Workflow already exists: {$name}");
            return;
        }

        $workflow = Workflow::create([
            'name'          => $name,
            'description'   => $description,
            'trigger_event' => $trigger,
            'is_active'     => true,
            'created_by'    => null,
        ]);

        foreach ($nodes as $node) {
            $workflow->nodes()->create([
                'node_key'   => $node['node_key'],
                'type'       => $node['type'],
                'label'      => $node['label'],
                'config'     => $node['config'],
                'position_x' => $node['x'],
                'position_y' => $node['y'],
            ]);
        }

        foreach ($edges as $edge) {
            $workflow->edges()->create([
                'edge_key'        => $edge['edge_key'],
                'source_node_key' => $edge['source'],
                'target_node_key' => $edge['target'],
                'condition_value' => null,
                'label'           => null,
            ]);
        }
    }
}

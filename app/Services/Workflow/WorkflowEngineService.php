<?php

namespace App\Services\Workflow;

use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowRunLog;
use App\Models\EnrollmentApplicant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WorkflowEngineService
{
    /**
     * Fire all active workflows for a given trigger event and applicant.
     */
    public function fire(string $event, EnrollmentApplicant $applicant): void
    {
        $workflows = Workflow::where('is_active', true)
            ->where('trigger_event', $event)
            ->with(['nodes', 'edges'])
            ->get();

        foreach ($workflows as $workflow) {
            $this->runWorkflow($workflow, $applicant);
        }
    }

    /**
     * Execute a single workflow for the given applicant.
     */
    public function runWorkflow(Workflow $workflow, EnrollmentApplicant $applicant): WorkflowRun
    {
        $run = WorkflowRun::create([
            'workflow_id'  => $workflow->id,
            'applicant_id' => $applicant->id,
            'status'       => 'running',
            'context'      => ['applicant_id' => $applicant->id],
            'started_at'   => now(),
        ]);

        try {
            // Find trigger node
            $triggerNode = $workflow->nodes->firstWhere('type', 'trigger');
            if (!$triggerNode) {
                $run->update(['status' => 'failed', 'completed_at' => now()]);
                return $run;
            }

            // Walk the graph starting from trigger node
            $this->executeNode($workflow, $run, $triggerNode->node_key, $applicant);

            $run->update(['status' => 'completed', 'completed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error("WorkflowEngine error for run #{$run->id}: " . $e->getMessage());
            $run->update(['status' => 'failed', 'completed_at' => now()]);
        }

        return $run;
    }

    /**
     * Recursively execute a node then follow edges to next nodes.
     */
    protected function executeNode(Workflow $workflow, WorkflowRun $run, string $nodeKey, EnrollmentApplicant $applicant, int $depth = 0): void
    {
        if ($depth > 50) return; // safety max depth

        $node = $workflow->nodes->firstWhere('node_key', $nodeKey);
        if (!$node) return;

        $run->update(['current_node_key' => $nodeKey]);

        $result = $this->executeNodeAction($node->type, $node->config ?? [], $applicant, $run);

        WorkflowRunLog::create([
            'workflow_run_id' => $run->id,
            'node_key'        => $nodeKey,
            'node_type'       => $node->type,
            'status'          => $result['status'],
            'message'         => $result['message'] ?? null,
            'output'          => $result['output'] ?? null,
            'executed_at'     => now(),
        ]);

        if ($result['status'] === 'failed') return;

        // Follow outgoing edges
        $edges = $workflow->edges->where('source_node_key', $nodeKey);

        foreach ($edges as $edge) {
            // For condition nodes, only follow matching branch
            if ($node->type === 'condition' && $edge->condition_value !== null) {
                if ($edge->condition_value !== ($result['output']['condition_result'] ?? null)) {
                    continue;
                }
            }
            $this->executeNode($workflow, $run, $edge->target_node_key, $applicant, $depth + 1);
        }
    }

    /**
     * Execute the actual action of a node type.
     */
    protected function executeNodeAction(string $type, array $config, EnrollmentApplicant $applicant, WorkflowRun $run): array
    {
        try {
            return match ($type) {
                'trigger'       => ['status' => 'success', 'message' => 'Trigger fired'],
                'send_email'    => $this->actionSendEmail($config, $applicant),
                'change_status' => $this->actionChangeStatus($config, $applicant),
                'condition'     => $this->actionCondition($config, $applicant),
                'notify_admin'  => $this->actionNotifyAdmin($config, $applicant),
                'delay'         => ['status' => 'success', 'message' => 'Delay node (async not yet implemented — skipped)'],
                default         => ['status' => 'skipped', 'message' => "Unknown node type: {$type}"],
            };
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    // ─── Node Actions ──────────────────────────────────────

    protected function actionSendEmail(array $config, EnrollmentApplicant $applicant): array
    {
        // TEST MODE: If WORKFLOW_TEST_EMAIL is set, ALL emails go to that address only
        $testEmail  = env('WORKFLOW_TEST_EMAIL');
        $realTo     = $applicant->email ?? ($applicant->user->email ?? null);
        $to         = $testEmail ?: $realTo;
        $subject    = $this->interpolate($config['subject'] ?? 'AMIS Enrollment Update', $applicant);
        $body       = $this->interpolate($config['body'] ?? '', $applicant);

        if (!$to) {
            return ['status' => 'failed', 'message' => 'No email address for applicant'];
        }

        $testNote = $testEmail ? "[TEST MODE — originally for: {$realTo}]\n\n" : '';

        Mail::raw($testNote . $body, function ($msg) use ($to, $subject, $testEmail) {
            $msg->to($to)->subject(($testEmail ? '[TEST] ' : '') . $subject);
        });

        $sentTo = $testEmail ? "{$testEmail} (TEST MODE)" : $to;
        return ['status' => 'success', 'message' => "Email sent to {$sentTo}", 'output' => ['to' => $sentTo, 'subject' => $subject]];
    }

    protected function actionChangeStatus(array $config, EnrollmentApplicant $applicant): array
    {
        $newStatus = $config['status'] ?? null;
        if (!$newStatus) {
            return ['status' => 'failed', 'message' => 'No target status configured'];
        }

        $oldStatus = $applicant->status;
        $applicant->update(['status' => $newStatus]);

        return ['status' => 'success', 'message' => "Status changed: {$oldStatus} → {$newStatus}", 'output' => ['old' => $oldStatus, 'new' => $newStatus]];
    }

    protected function actionCondition(array $config, EnrollmentApplicant $applicant): array
    {
        $field    = $config['field'] ?? null;
        $operator = $config['operator'] ?? '==';
        $value    = $config['value'] ?? null;

        $actual = data_get($applicant, $field);

        $result = match ($operator) {
            '=='        => $actual == $value,
            '!='        => $actual != $value,
            'contains'  => str_contains((string)$actual, (string)$value),
            'in'        => in_array($actual, (array)$value),
            default     => false,
        };

        return [
            'status'  => 'success',
            'message' => "Condition [{$field} {$operator} {$value}] = " . ($result ? 'yes' : 'no'),
            'output'  => ['condition_result' => $result ? 'yes' : 'no'],
        ];
    }

    protected function actionNotifyAdmin(array $config, EnrollmentApplicant $applicant): array
    {
        // TEST MODE: override admin email too
        $testEmail  = env('WORKFLOW_TEST_EMAIL');
        $adminEmail = $testEmail ?: ($config['admin_email'] ?? config('mail.from.address'));
        $subject    = $this->interpolate($config['subject'] ?? 'AMIS Admin Notification', $applicant);
        $body       = $this->interpolate($config['body'] ?? 'A workflow action was triggered for applicant {{student_name}}.', $applicant);

        Mail::raw($body, function ($msg) use ($adminEmail, $subject, $testEmail) {
            $msg->to($adminEmail)->subject(($testEmail ? '[TEST] ' : '') . $subject);
        });

        $sentTo = $testEmail ? "{$testEmail} (TEST MODE)" : $adminEmail;
        return ['status' => 'success', 'message' => "Admin notified at {$sentTo}", 'output' => ['to' => $sentTo]];
    }

    // ─── Helpers ──────────────────────────────────────────

    /**
     * Replace {{variable}} placeholders with actual applicant data.
     */
    protected function interpolate(string $text, EnrollmentApplicant $applicant): string
    {
        $vars = [
            '{{student_name}}'   => trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? '')),
            '{{student_id}}'     => $applicant->student_number ?? $applicant->id,
            '{{grade_level}}'    => $applicant->grade_level ?? '',
            '{{status}}'         => $applicant->status ?? '',
            '{{email}}'          => $applicant->email ?? '',
            '{{school_year}}'    => $applicant->school_year ?? '',
        ];

        return str_replace(array_keys($vars), array_values($vars), $text);
    }
}

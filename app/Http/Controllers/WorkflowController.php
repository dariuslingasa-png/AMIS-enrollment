<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Models\WorkflowEdge;
use App\Models\WorkflowRun;
use App\Models\EnrollmentApplicant;
use App\Services\Workflow\WorkflowEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowController extends Controller
{
    public function __construct(private WorkflowEngineService $engine) {}

    // ─── Pages ────────────────────────────────────────────

    /** Main workflow builder list page */
    public function index()
    {
        $workflows = Workflow::withCount('runs')
            ->orderByDesc('created_at')
            ->get();
        return view('workflow.index', compact('workflows'));
    }

    /** Visual canvas builder page */
    public function builder(Workflow $workflow)
    {
        $workflow->load(['nodes', 'edges']);
        return view('workflow.builder', compact('workflow'));
    }

    /** Workflow runs / logs page */
    public function runs(Workflow $workflow)
    {
        $runs = $workflow->runs()
            ->with(['logs', 'applicant'])
            ->orderByDesc('created_at')
            ->paginate(20);
        return view('workflow.runs', compact('workflow', 'runs'));
    }

    /** Global dashboard showing all stages */
    public function dashboard()
    {
        $stages = [
            'draft'       => 'Draft',
            'submitted'   => 'Submitted',
            'under_review'=> 'Under Review',
            'approved'    => 'Approved',
            'rejected'    => 'Rejected',
            'enrolled'    => 'Enrolled',
        ];

        $applicantsByStage = [];
        foreach (array_keys($stages) as $stage) {
            $applicantsByStage[$stage] = EnrollmentApplicant::where('status', $stage)
                ->select('id', 'first_name', 'last_name', 'grade_level', 'status', 'created_at', 'student_number')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        }

        $recentRuns = WorkflowRun::with(['workflow', 'applicant'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('workflow.dashboard', compact('stages', 'applicantsByStage', 'recentRuns'));
    }

    // ─── API ──────────────────────────────────────────────

    /** List all workflows (JSON) */
    public function apiIndex()
    {
        return response()->json(Workflow::withCount('runs')->orderByDesc('created_at')->get());
    }

    /** Get a single workflow with nodes/edges for the canvas */
    public function apiShow(Workflow $workflow)
    {
        $workflow->load(['nodes', 'edges']);
        return response()->json($workflow);
    }

    /** Create new workflow */
    public function apiStore(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'trigger_event'     => 'required|string',
            'trigger_conditions'=> 'nullable|array',
        ]);

        $workflow = Workflow::create([...$data, 'created_by' => Auth::id()]);
        return response()->json($workflow, 201);
    }

    /** Update workflow meta */
    public function apiUpdate(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes|boolean',
            'trigger_event' => 'sometimes|string',
        ]);

        $workflow->update($data);
        return response()->json($workflow);
    }

    /** Save entire canvas (nodes + edges) */
    public function apiSaveCanvas(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'nodes'              => 'required|array',
            'nodes.*.node_key'   => 'required|string',
            'nodes.*.type'       => 'required|string',
            'nodes.*.label'      => 'required|string',
            'nodes.*.config'     => 'nullable|array',
            'nodes.*.position_x' => 'required|numeric',
            'nodes.*.position_y' => 'required|numeric',
            'edges'              => 'nullable|array',
            'edges.*.edge_key'         => 'required|string',
            'edges.*.source_node_key'  => 'required|string',
            'edges.*.target_node_key'  => 'required|string',
            'edges.*.condition_value'  => 'nullable|string',
            'edges.*.label'            => 'nullable|string',
        ]);

        // Replace all nodes and edges atomically
        $workflow->nodes()->delete();
        $workflow->edges()->delete();

        foreach ($data['nodes'] as $node) {
            $workflow->nodes()->create([
                'node_key'   => $node['node_key'],
                'type'       => $node['type'],
                'label'      => $node['label'],
                'config'     => $node['config'] ?? null,
                'position_x' => $node['position_x'],
                'position_y' => $node['position_y'],
            ]);
        }

        foreach ($data['edges'] ?? [] as $edge) {
            $workflow->edges()->create([
                'edge_key'        => $edge['edge_key'],
                'source_node_key' => $edge['source_node_key'],
                'target_node_key' => $edge['target_node_key'],
                'condition_value' => $edge['condition_value'] ?? null,
                'label'           => $edge['label'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'nodes' => $data['nodes'], 'edges' => $data['edges'] ?? []]);
    }

    /** Toggle active/inactive */
    public function apiToggle(Workflow $workflow)
    {
        $workflow->update(['is_active' => !$workflow->is_active]);
        return response()->json(['is_active' => $workflow->is_active]);
    }

    /** Delete workflow */
    public function apiDestroy(Workflow $workflow)
    {
        $workflow->delete();
        return response()->json(['success' => true]);
    }

    /** Manually trigger a workflow for a test applicant */
    public function apiTestRun(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'applicant_id' => 'required|integer|exists:enrollment_applicants,id',
        ]);

        $applicant = EnrollmentApplicant::findOrFail($data['applicant_id']);
        $run = $this->engine->runWorkflow($workflow, $applicant);
        $run->load('logs');

        return response()->json($run);
    }

    /** Get runs for a workflow */
    public function apiRuns(Workflow $workflow)
    {
        $runs = $workflow->runs()
            ->with(['logs'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
        return response()->json($runs);
    }

    /** Get dashboard stats */
    public function apiDashboardStats()
    {
        $stages = ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'enrolled'];
        $counts = [];
        foreach ($stages as $stage) {
            $counts[$stage] = EnrollmentApplicant::where('status', $stage)->count();
        }

        return response()->json([
            'stage_counts'   => $counts,
            'total_runs'     => WorkflowRun::count(),
            'active_workflows' => Workflow::where('is_active', true)->count(),
            'recent_runs'    => WorkflowRun::with(['workflow'])->orderByDesc('created_at')->limit(5)->get(),
        ]);
    }
}

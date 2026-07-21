<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workflow Dashboard | AMIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0f1a; color: #e2e8f0; min-height: 100vh; }
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; height: 56px; background: #0f1623; border-bottom: 1px solid #1e293b;
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-brand { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 10px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; }
        .topbar-nav { display: flex; gap: 4px; }
        .topbar-nav a { padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #94a3b8; text-decoration: none; transition: all 0.15s; }
        .topbar-nav a:hover, .topbar-nav a.active { background: #1e293b; color: #fff; }
        .page { padding: 28px 24px; max-width: 1400px; margin: 0 auto; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 900; color: #fff; margin-bottom: 4px; }
        .page-subtitle { font-size: 13px; color: #64748b; margin-bottom: 28px; }

        .stats-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 28px; }
        .stat-card {
            background: #0f1623; border: 1px solid #1e293b; border-radius: 14px; padding: 16px;
            display: flex; flex-direction: column; gap: 4px; cursor: pointer; transition: border-color 0.15s;
        }
        .stat-card:hover { border-color: #334155; }
        .stat-card.active-filter { border-color: #10b981; }
        .stat-dot { width: 6px; height: 6px; border-radius: 50%; }
        .stat-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; display: flex; align-items: center; gap: 6px; }
        .stat-value { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 900; }
        .stat-sub { font-size: 10px; color: #475569; }

        .kanban-board { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; }
        .kanban-col { background: #0f1623; border: 1px solid #1e293b; border-radius: 16px; overflow: hidden; min-height: 400px; }
        .col-header { padding: 14px 16px; border-bottom: 1px solid #1e293b; display: flex; align-items: center; justify-content: space-between; }
        .col-title { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
        .col-count { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #1e293b; color: #64748b; }
        .col-body { padding: 10px; display: flex; flex-direction: column; gap: 8px; }

        .applicant-card {
            background: #0a0f1a; border: 1px solid #1e293b; border-radius: 10px;
            padding: 10px 12px; transition: border-color 0.15s; cursor: default;
        }
        .applicant-card:hover { border-color: #334155; }
        .app-name { font-size: 12.5px; font-weight: 700; color: #fff; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .app-meta { font-size: 10.5px; color: #475569; }

        .col-draft .col-title { color: #94a3b8; }
        .col-submitted .col-title { color: #60a5fa; }
        .col-under_review .col-title { color: #f59e0b; }
        .col-approved .col-title { color: #10b981; }
        .col-rejected .col-title { color: #ef4444; }
        .col-enrolled .col-title { color: #8b5cf6; }

        .recent-runs { margin-top: 28px; }
        .section-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 14px; }
        .runs-table { background: #0f1623; border: 1px solid #1e293b; border-radius: 14px; overflow: hidden; }
        .runs-table table { width: 100%; border-collapse: collapse; }
        .runs-table th { font-size: 10px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.08em; padding: 12px 16px; text-align: left; border-bottom: 1px solid #1e293b; }
        .runs-table td { padding: 12px 16px; font-size: 12.5px; color: #cbd5e1; border-bottom: 1px solid #0a0f1a; }
        .runs-table tr:last-child td { border-bottom: none; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .badge-green { background: rgba(16,185,129,0.1); color: #34d399; }
        .badge-red { background: rgba(239,68,68,0.1); color: #fca5a5; }
        .badge-blue { background: rgba(59,130,246,0.1); color: #60a5fa; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-brand"><div class="dot"></div> AMIS Workflow</div>
    <nav class="topbar-nav">
        <a href="{{ route('workflow.index') }}">Workflows</a>
        <a href="{{ route('workflow.dashboard') }}" class="active">Dashboard</a>
        <a href="{{ route('enrollment.dashboard') }}">← Enrollment</a>
    </nav>
</div>

<div class="page">
    <div class="page-title">Enrollment Pipeline Dashboard</div>
    <div class="page-subtitle">Live overview of all applicants across enrollment stages</div>

    <!-- Stats Row -->
    <div class="stats-row">
        @foreach($stages as $key => $label)
        @php
            $colors = ['draft'=>'#94a3b8','submitted'=>'#60a5fa','under_review'=>'#f59e0b','approved'=>'#10b981','rejected'=>'#ef4444','enrolled'=>'#8b5cf6'];
            $color = $colors[$key] ?? '#64748b';
            $cnt = $applicantsByStage[$key]->count();
        @endphp
        <div class="stat-card">
            <div class="stat-label">
                <div class="stat-dot" style="background: {{ $color }};"></div>
                {{ $label }}
            </div>
            <div class="stat-value" style="color: {{ $color }};">{{ $cnt }}</div>
            <div class="stat-sub">applicants</div>
        </div>
        @endforeach
    </div>

    <!-- Kanban Board -->
    <div class="kanban-board">
        @foreach($stages as $key => $label)
        @php
            $colors = ['draft'=>'#94a3b8','submitted'=>'#60a5fa','under_review'=>'#f59e0b','approved'=>'#10b981','rejected'=>'#ef4444','enrolled'=>'#8b5cf6'];
            $color = $colors[$key] ?? '#64748b';
            $applicants = $applicantsByStage[$key];
        @endphp
        <div class="kanban-col col-{{ $key }}">
            <div class="col-header">
                <div class="col-title" style="color: {{ $color }};">{{ $label }}</div>
                <div class="col-count">{{ $applicants->count() }}</div>
            </div>
            <div class="col-body">
                @forelse($applicants as $app)
                <div class="applicant-card">
                    <div class="app-name">{{ $app->first_name }} {{ $app->last_name }}</div>
                    <div class="app-meta">{{ $app->grade_level }} · #{{ $app->student_number ?: $app->id }}</div>
                </div>
                @empty
                <div style="text-align:center; padding: 20px; font-size: 11px; color: #334155;">Empty</div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    <!-- Recent Workflow Runs -->
    <div class="recent-runs">
        <div class="section-title">Recent Workflow Runs</div>
        <div class="runs-table">
            <table>
                <thead>
                    <tr>
                        <th>Run ID</th>
                        <th>Workflow</th>
                        <th>Applicant</th>
                        <th>Status</th>
                        <th>Started</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRuns as $run)
                    <tr>
                        <td style="font-family: monospace; color: #475569;">#{{ $run->id }}</td>
                        <td>{{ $run->workflow->name ?? '—' }}</td>
                        <td>
                            @if($run->applicant)
                                {{ $run->applicant->first_name }} {{ $run->applicant->last_name }}
                            @else
                                <span style="color:#475569;">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $run->status === 'completed' ? 'badge-green' : ($run->status === 'failed' ? 'badge-red' : 'badge-blue') }}">
                                {{ $run->status }}
                            </span>
                        </td>
                        <td style="color: #475569;">{{ $run->created_at?->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 32px; color: #334155;">No workflow runs yet. Activate a workflow to get started.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>

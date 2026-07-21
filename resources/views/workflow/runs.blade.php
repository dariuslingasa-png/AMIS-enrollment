<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $workflow->name }} — Runs | AMIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0f1a; color: #e2e8f0; min-height: 100vh; }
        .topbar { display: flex; align-items: center; gap: 12px; padding: 0 24px; height: 56px; background: #0f1623; border-bottom: 1px solid #1e293b; }
        .back-btn { color: #64748b; text-decoration: none; font-size: 13px; font-weight: 600; padding: 6px 10px; border-radius: 8px; transition: all 0.15s; }
        .back-btn:hover { background: #1e293b; color: #fff; }
        .page { padding: 28px 24px; max-width: 1100px; margin: 0 auto; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 900; color: #fff; margin-bottom: 4px; }
        .page-subtitle { font-size: 13px; color: #64748b; margin-bottom: 24px; }

        .runs-list { display: flex; flex-direction: column; gap: 12px; }
        .run-card { background: #0f1623; border: 1px solid #1e293b; border-radius: 14px; overflow: hidden; }
        .run-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px; cursor: pointer; transition: background 0.15s;
        }
        .run-header:hover { background: #0f1f35; }
        .run-id { font-family: monospace; font-size: 12px; color: #475569; }
        .run-info { display: flex; align-items: center; gap: 12px; }
        .run-meta { font-size: 12px; color: #64748b; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 10.5px; font-weight: 700; }
        .badge-green { background: rgba(16,185,129,0.1); color: #34d399; }
        .badge-red { background: rgba(239,68,68,0.1); color: #fca5a5; }
        .badge-blue { background: rgba(59,130,246,0.1); color: #60a5fa; }

        .run-logs { padding: 12px 18px 16px; border-top: 1px solid #1e293b; display: none; }
        .run-logs.open { display: block; }
        .log-item { display: flex; align-items: flex-start; gap: 10px; padding: 6px 0; border-bottom: 1px solid #0a0f1a; font-size: 12px; }
        .log-item:last-child { border-bottom: none; }
        .log-icon { font-size: 13px; flex-shrink: 0; }
        .log-type { color: #475569; font-size: 10px; font-weight: 700; text-transform: uppercase; min-width: 80px; }
        .log-msg { color: #94a3b8; }
        .log-status-success { color: #34d399; }
        .log-status-failed { color: #fca5a5; }
    </style>
</head>
<body>
<div class="topbar">
    <a href="{{ route('workflow.index') }}" class="back-btn">← Workflows</a>
    <span style="color:#1e293b">|</span>
    <a href="{{ route('workflow.builder', $workflow) }}" class="back-btn">⚡ {{ $workflow->name }}</a>
    <span style="color:#1e293b">|</span>
    <span style="font-size:13px; color:#64748b;">Run Logs</span>
</div>

<div class="page">
    <div class="page-title">Run Logs — {{ $workflow->name }}</div>
    <div class="page-subtitle">History of all executions for this workflow</div>

    @if($runs->isEmpty())
        <div style="text-align:center; padding: 60px; color: #334155;">
            <div style="font-size: 40px; margin-bottom: 12px;">📋</div>
            <div style="font-family: Outfit, sans-serif; font-size: 18px; font-weight: 800;">No runs yet</div>
            <p style="margin-top: 8px; font-size: 13px;">Activate the workflow and trigger an enrollment event to see runs here.</p>
        </div>
    @else
        <div class="runs-list">
            @foreach($runs as $run)
            <div class="run-card">
                <div class="run-header" onclick="this.nextElementSibling.classList.toggle('open')">
                    <div class="run-info">
                        <div class="run-id">#{{ $run->id }}</div>
                        @if($run->applicant)
                            <div style="font-size:13px; font-weight: 700; color: #cbd5e1;">{{ $run->applicant->first_name }} {{ $run->applicant->last_name }}</div>
                        @endif
                        <span class="badge {{ $run->status === 'completed' ? 'badge-green' : ($run->status === 'failed' ? 'badge-red' : 'badge-blue') }}">
                            {{ $run->status }}
                        </span>
                        <span style="font-size:11px; color:#475569;">{{ $run->logs->count() }} steps</span>
                    </div>
                    <div class="run-meta">{{ $run->created_at?->format('M d, Y h:i A') }}</div>
                </div>
                <div class="run-logs">
                    @foreach($run->logs as $log)
                    <div class="log-item">
                        <div class="log-icon">{{ $log->status === 'success' ? '✓' : ($log->status === 'failed' ? '✗' : '—') }}</div>
                        <div class="log-type log-status-{{ $log->status }}">{{ $log->node_type }}</div>
                        <div class="log-msg">{{ $log->message }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        <div style="margin-top: 16px;">{{ $runs->links() }}</div>
    @endif
</div>
</body>
</html>

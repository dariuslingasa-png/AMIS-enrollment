<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMIS Workflow Automation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0f1a; color: #e2e8f0; min-height: 100vh; }

        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; height: 56px;
            background: #0f1623; border-bottom: 1px solid #1e293b;
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-brand {
            display: flex; align-items: center; gap: 10px;
            font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #fff;
        }
        .topbar-brand .dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; }
        .topbar-nav { display: flex; gap: 4px; }
        .topbar-nav a {
            padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
            color: #94a3b8; text-decoration: none; transition: all 0.15s;
        }
        .topbar-nav a:hover, .topbar-nav a.active { background: #1e293b; color: #fff; }
        .topbar-actions { display: flex; gap: 8px; }

        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
            border: none; cursor: pointer; text-decoration: none; transition: all 0.15s;
        }
        .btn-primary { background: #10b981; color: #fff; }
        .btn-primary:hover { background: #059669; }
        .btn-ghost { background: #1e293b; color: #94a3b8; }
        .btn-ghost:hover { background: #263548; color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }

        .page { padding: 32px 24px; max-width: 1280px; margin: 0 auto; }

        .page-header {
            display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 32px;
        }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 900; color: #fff; }
        .page-subtitle { font-size: 13px; color: #64748b; margin-top: 4px; }

        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
        .stat-card {
            background: #0f1623; border: 1px solid #1e293b; border-radius: 16px;
            padding: 20px; display: flex; flex-direction: column; gap: 6px;
        }
        .stat-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; }
        .stat-value { font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 900; color: #fff; }
        .stat-sub { font-size: 11px; color: #475569; }

        .workflows-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
        .workflow-card {
            background: #0f1623; border: 1px solid #1e293b; border-radius: 18px;
            padding: 22px; transition: border-color 0.2s; position: relative; overflow: hidden;
        }
        .workflow-card:hover { border-color: #334155; }
        .workflow-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .workflow-name { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 800; color: #fff; }
        .workflow-desc { font-size: 12.5px; color: #64748b; margin-bottom: 16px; line-height: 1.5; }

        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 999px; font-size: 10.5px; font-weight: 700;
        }
        .badge-green { background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
        .badge-gray { background: rgba(100,116,139,0.1); color: #64748b; border: 1px solid rgba(100,116,139,0.2); }
        .badge-blue { background: rgba(59,130,246,0.1); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); }

        .workflow-meta { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; }
        .workflow-actions { display: flex; gap: 8px; }
        .workflow-actions .btn { padding: 6px 12px; font-size: 12px; }

        .toggle-switch { position: relative; display: inline-flex; align-items: center; cursor: pointer; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-track {
            width: 40px; height: 22px; border-radius: 999px;
            background: #1e293b; border: 1px solid #334155;
            transition: all 0.2s; position: relative;
        }
        .toggle-track::after {
            content: ''; position: absolute; left: 2px; top: 2px;
            width: 16px; height: 16px; border-radius: 50%; background: #475569;
            transition: all 0.2s;
        }
        .toggle-switch input:checked + .toggle-track { background: #10b981; border-color: #10b981; }
        .toggle-switch input:checked + .toggle-track::after { left: 20px; background: #fff; }

        /* Create Workflow Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: #0f1623; border: 1px solid #1e293b; border-radius: 20px;
            padding: 28px; width: min(100%, 480px); margin: 20px;
        }
        .modal-title { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
        .form-input {
            width: 100%; padding: 10px 14px; border-radius: 10px;
            background: #0a0f1a; border: 1px solid #1e293b; color: #fff;
            font-size: 13.5px; font-family: inherit; transition: border-color 0.15s;
        }
        .form-input:focus { outline: none; border-color: #10b981; }
        .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 24px; }

        .empty-state {
            text-align: center; padding: 80px 20px; color: #475569;
        }
        .empty-icon { font-size: 48px; margin-bottom: 16px; }
        .empty-title { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #64748b; margin-bottom: 8px; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-brand">
        <div class="dot"></div>
        AMIS Workflow
    </div>
    <nav class="topbar-nav">
        <a href="{{ route('workflow.index') }}" class="active">Workflows</a>
        <a href="{{ route('workflow.dashboard') }}">Dashboard</a>
        <a href="{{ route('enrollment.dashboard') }}">← Enrollment</a>
    </nav>
    <div class="topbar-actions">
        <button class="btn btn-primary" onclick="document.getElementById('createModal').classList.add('open')">
            + New Workflow
        </button>
    </div>
</div>

<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Workflow Automation</div>
            <div class="page-subtitle">Build automated enrollment pipelines — like n8n, for AMIS.</div>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total Workflows</div>
            <div class="stat-value">{{ $workflows->count() }}</div>
            <div class="stat-sub">Configured automations</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active</div>
            <div class="stat-value" style="color: #10b981;">{{ $workflows->where('is_active', true)->count() }}</div>
            <div class="stat-sub">Running automations</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Runs</div>
            <div class="stat-value">{{ $workflows->sum('runs_count') }}</div>
            <div class="stat-sub">All-time executions</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Trigger Events</div>
            <div class="stat-value" style="color: #60a5fa;">{{ $workflows->pluck('trigger_event')->unique()->count() }}</div>
            <div class="stat-sub">Unique event types</div>
        </div>
    </div>

    @if($workflows->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">⚡</div>
            <div class="empty-title">No Workflows Yet</div>
            <p style="margin-bottom: 24px; font-size: 14px;">Create your first automation to get started.</p>
            <button class="btn btn-primary" onclick="document.getElementById('createModal').classList.add('open')">
                + Create First Workflow
            </button>
        </div>
    @else
        <div class="workflows-grid">
            @foreach($workflows as $workflow)
            <div class="workflow-card">
                <div class="workflow-card-header">
                    <div class="workflow-name">{{ $workflow->name }}</div>
                    <label class="toggle-switch" title="{{ $workflow->is_active ? 'Deactivate' : 'Activate' }}">
                        <input type="checkbox" {{ $workflow->is_active ? 'checked' : '' }}
                               onchange="toggleWorkflow({{ $workflow->id }}, this)">
                        <div class="toggle-track"></div>
                    </label>
                </div>
                <div class="workflow-desc">{{ $workflow->description ?: 'No description provided.' }}</div>
                <div class="workflow-meta">
                    <span class="badge {{ $workflow->is_active ? 'badge-green' : 'badge-gray' }}">
                        {{ $workflow->is_active ? '● Active' : '○ Inactive' }}
                    </span>
                    <span class="badge badge-blue">{{ str_replace('.', ' › ', $workflow->trigger_event) }}</span>
                    <span class="badge badge-gray">{{ $workflow->runs_count }} runs</span>
                </div>
                <div class="workflow-actions">
                    <a href="{{ route('workflow.builder', $workflow) }}" class="btn btn-primary">
                        ⚡ Open Builder
                    </a>
                    <a href="{{ route('workflow.runs', $workflow) }}" class="btn btn-ghost">
                        📋 Logs
                    </a>
                    <button class="btn btn-danger" onclick="deleteWorkflow({{ $workflow->id }}, this)">✕</button>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Create Workflow Modal -->
<div class="modal-overlay" id="createModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal">
        <div class="modal-title">Create New Workflow</div>
        <form id="createForm">
            @csrf
            <div class="form-group">
                <label class="form-label">Workflow Name *</label>
                <input class="form-input" name="name" placeholder="e.g. New Enrollment Notification" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" name="description" rows="2" placeholder="What does this workflow do?"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Trigger Event *</label>
                <select class="form-input" name="trigger_event">
                    <option value="enrollment.submitted">enrollment.submitted — When student submits form</option>
                    <option value="enrollment.approved">enrollment.approved — When admin approves</option>
                    <option value="enrollment.rejected">enrollment.rejected — When admin rejects</option>
                    <option value="enrollment.payment_submitted">enrollment.payment_submitted — When payment uploaded</option>
                    <option value="enrollment.finalized">enrollment.finalized — When enrollment finalized</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('createModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Workflow</button>
            </div>
        </form>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}';

document.getElementById('createForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const body = Object.fromEntries(fd.entries());
    const res = await fetch('/workflow/api/workflows', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body)
    });
    if (res.ok) {
        const wf = await res.json();
        window.location.href = `/workflow/${wf.id}/builder`;
    } else {
        alert('Error creating workflow. Please try again.');
    }
});

async function toggleWorkflow(id, el) {
    const res = await fetch(`/workflow/api/workflows/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF }
    });
    if (!res.ok) {
        el.checked = !el.checked;
        alert('Failed to toggle workflow.');
    }
}

async function deleteWorkflow(id, btn) {
    if (!confirm('Delete this workflow and all its runs? This cannot be undone.')) return;
    const res = await fetch(`/workflow/api/workflows/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF }
    });
    if (res.ok) {
        btn.closest('.workflow-card').remove();
    } else {
        alert('Failed to delete workflow.');
    }
}
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $workflow->name }} — Workflow Builder | AMIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #060b14; color: #e2e8f0; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }

        /* TOP BAR */
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px; height: 52px; background: #0a0f1a;
            border-bottom: 1px solid #1e293b; flex-shrink: 0; z-index: 100;
        }
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .back-btn {
            display: flex; align-items: center; gap: 6px; color: #64748b;
            text-decoration: none; font-size: 13px; font-weight: 600;
            padding: 6px 10px; border-radius: 8px; transition: all 0.15s;
        }
        .back-btn:hover { background: #1e293b; color: #fff; }
        .wf-name { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 800; color: #fff; }
        .wf-status { display: flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
        .wf-status.active { background: rgba(16,185,129,0.1); color: #34d399; }
        .wf-status.inactive { background: rgba(100,116,139,0.1); color: #64748b; }

        .topbar-actions { display: flex; gap: 8px; align-items: center; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 9px; font-size: 12.5px; font-weight: 700;
            border: none; cursor: pointer; transition: all 0.15s;
        }
        .btn-primary { background: #10b981; color: #fff; }
        .btn-primary:hover { background: #059669; }
        .btn-ghost { background: #1e293b; color: #94a3b8; }
        .btn-ghost:hover { background: #263548; color: #fff; }
        .btn-blue { background: #3b82f6; color: #fff; }
        .btn-blue:hover { background: #2563eb; }

        /* MAIN LAYOUT */
        .builder-layout { display: flex; flex: 1; overflow: hidden; }

        /* LEFT SIDEBAR — node palette */
        .sidebar {
            width: 220px; background: #0a0f1a; border-right: 1px solid #1e293b;
            display: flex; flex-direction: column; overflow-y: auto; flex-shrink: 0;
        }
        .sidebar-section { padding: 12px; }
        .sidebar-title { font-size: 10px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
        .node-palette-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px; margin-bottom: 6px;
            background: #0f1623; border: 1px solid #1e293b;
            cursor: grab; font-size: 12.5px; font-weight: 600; color: #cbd5e1;
            transition: all 0.15s; user-select: none;
        }
        .node-palette-item:hover { border-color: #334155; color: #fff; transform: translateX(2px); }
        .node-palette-item:active { cursor: grabbing; }
        .node-icon { font-size: 16px; }

        /* CANVAS */
        .canvas-container { flex: 1; position: relative; overflow: hidden; background: #060b14; }
        .canvas {
            position: absolute; inset: 0; overflow: hidden;
            background-image: radial-gradient(circle, #1e293b 1px, transparent 1px);
            background-size: 28px 28px;
        }
        .canvas-inner { position: relative; width: 100%; height: 100%; transform-origin: 0 0; }

        /* SVG Edges */
        .edges-svg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; overflow: visible; }

        /* Workflow Nodes */
        .wf-node {
            position: absolute; min-width: 190px; border-radius: 14px;
            border: 2px solid transparent; cursor: move; user-select: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            transition: box-shadow 0.15s, border-color 0.15s;
        }
        .wf-node:hover { border-color: #334155; box-shadow: 0 8px 32px rgba(0,0,0,0.6); }
        .wf-node.selected { border-color: #10b981 !important; }
        .wf-node.dragging { opacity: 0.9; z-index: 999; }

        .wf-node-header { display: flex; align-items: center; gap: 8px; padding: 12px 14px 10px; border-radius: 12px 12px 0 0; }
        .wf-node-icon { font-size: 18px; }
        .wf-node-type { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.7; }
        .wf-node-body { padding: 10px 14px 14px; border-radius: 0 0 12px 12px; }
        .wf-node-label { font-size: 13.5px; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .wf-node-summary { font-size: 11px; color: #64748b; line-height: 1.4; }

        /* Ports (connection points) */
        .wf-node-port {
            position: absolute; width: 12px; height: 12px; border-radius: 50%;
            background: #1e293b; border: 2px solid #334155;
            cursor: crosshair; transition: all 0.15s; z-index: 10;
        }
        .wf-node-port:hover { background: #10b981; border-color: #10b981; transform: scale(1.3); }
        .port-top { top: -6px; left: 50%; transform: translateX(-50%); }
        .port-bottom { bottom: -6px; left: 50%; transform: translateX(-50%); }
        .port-left { left: -6px; top: 50%; transform: translateY(-50%); }
        .port-right { right: -6px; top: 50%; transform: translateY(-50%); }

        /* Node color themes */
        .node-trigger .wf-node-header { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
        .node-trigger { background: #1a1030; }
        .node-send_email .wf-node-header { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .node-send_email { background: #0f1a30; }
        .node-change_status .wf-node-header { background: linear-gradient(135deg, #d97706, #b45309); }
        .node-change_status { background: #1a1400; }
        .node-condition .wf-node-header { background: linear-gradient(135deg, #db2777, #be185d); }
        .node-condition { background: #1a0a14; }
        .node-notify_admin .wf-node-header { background: linear-gradient(135deg, #059669, #047857); }
        .node-notify_admin { background: #001a10; }
        .node-delay .wf-node-header { background: linear-gradient(135deg, #475569, #334155); }
        .node-delay { background: #0f1219; }

        /* RIGHT PANEL — node config */
        .config-panel {
            width: 300px; background: #0a0f1a; border-left: 1px solid #1e293b;
            display: flex; flex-direction: column; overflow-y: auto; flex-shrink: 0;
            transition: transform 0.2s;
        }
        .config-panel.hidden { transform: translateX(100%); width: 0; border: none; }
        .config-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px; border-bottom: 1px solid #1e293b; flex-shrink: 0;
        }
        .config-title { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 800; color: #fff; }
        .close-panel { background: none; border: none; color: #64748b; cursor: pointer; font-size: 18px; padding: 4px; }
        .close-panel:hover { color: #fff; }
        .config-body { padding: 16px; flex: 1; }
        .form-group { margin-bottom: 14px; }
        .form-label { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
        .form-input {
            width: 100%; padding: 9px 12px; border-radius: 9px;
            background: #0f1623; border: 1px solid #1e293b; color: #fff;
            font-size: 13px; font-family: inherit; transition: border-color 0.15s;
        }
        .form-input:focus { outline: none; border-color: #10b981; }
        .hint { font-size: 11px; color: #475569; margin-top: 4px; }
        .config-footer { padding: 12px 16px; border-top: 1px solid #1e293b; display: flex; gap: 8px; }
        .config-footer .btn { flex: 1; justify-content: center; }

        /* Toast */
        .toast-container { position: fixed; bottom: 24px; right: 24px; display: flex; flex-direction: column; gap: 8px; z-index: 9999; }
        .toast {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 18px; border-radius: 12px; font-size: 13px; font-weight: 600;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4); animation: slideIn 0.2s ease;
        }
        .toast-success { background: #064e3b; border: 1px solid #065f46; color: #34d399; }
        .toast-error { background: #450a0a; border: 1px solid #7f1d1d; color: #fca5a5; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        /* Test Run Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal { background: #0f1623; border: 1px solid #1e293b; border-radius: 18px; padding: 24px; width: min(100%, 460px); margin: 20px; }
        .modal-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 18px; }
        .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px; }

        .empty-canvas {
            position: absolute; inset: 0; display: flex; flex-direction: column;
            align-items: center; justify-content: center; color: #1e293b; pointer-events: none;
        }
        .empty-canvas-icon { font-size: 64px; margin-bottom: 16px; opacity: 0.3; }
        .empty-canvas-text { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; opacity: 0.3; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="{{ route('workflow.index') }}" class="back-btn">← Back</a>
        <span style="color: #1e293b;">|</span>
        <div class="wf-name">{{ $workflow->name }}</div>
        <div class="wf-status {{ $workflow->is_active ? 'active' : 'inactive' }}" id="statusBadge">
            {{ $workflow->is_active ? '● Active' : '○ Inactive' }}
        </div>
    </div>
    <div class="topbar-actions">
        <button class="btn btn-ghost" onclick="openTestModal()">▶ Test Run</button>
        <button class="btn btn-ghost" id="toggleBtn" onclick="toggleActive()">
            {{ $workflow->is_active ? '⏸ Deactivate' : '▶ Activate' }}
        </button>
        <button class="btn btn-primary" onclick="saveCanvas()">💾 Save</button>
    </div>
</div>

<div class="builder-layout">

    <!-- LEFT: Node Palette -->
    <div class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Triggers</div>
            <div class="node-palette-item" draggable="true" data-type="trigger" data-label="Enrollment Trigger">
                <span class="node-icon">⚡</span> Trigger
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Actions</div>
            <div class="node-palette-item" draggable="true" data-type="send_email" data-label="Send Email">
                <span class="node-icon">📧</span> Send Email
            </div>
            <div class="node-palette-item" draggable="true" data-type="change_status" data-label="Change Status">
                <span class="node-icon">🔄</span> Change Status
            </div>
            <div class="node-palette-item" draggable="true" data-type="notify_admin" data-label="Notify Admin">
                <span class="node-icon">🔔</span> Notify Admin
            </div>
            <div class="node-palette-item" draggable="true" data-type="delay" data-label="Delay">
                <span class="node-icon">⏱</span> Delay
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Logic</div>
            <div class="node-palette-item" draggable="true" data-type="condition" data-label="Condition (If/Else)">
                <span class="node-icon">🔀</span> Condition
            </div>
        </div>
        <div class="sidebar-section" style="margin-top: auto;">
            <div class="sidebar-title">Tip</div>
            <p style="font-size: 11px; color: #475569; line-height: 1.5;">Drag nodes onto the canvas. Click a node to configure it. Connect nodes by dragging from a port.</p>
        </div>
    </div>

    <!-- CENTER: Canvas -->
    <div class="canvas-container" id="canvasContainer">
        <div class="canvas" id="canvas" ondragover="event.preventDefault()" ondrop="onDrop(event)">
            <div class="canvas-inner" id="canvasInner">
                <svg class="edges-svg" id="edgesSvg"></svg>
                <div class="empty-canvas" id="emptyHint">
                    <div class="empty-canvas-icon">⚡</div>
                    <div class="empty-canvas-text">Drag nodes here to start building</div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Config Panel -->
    <div class="config-panel hidden" id="configPanel">
        <div class="config-header">
            <div class="config-title" id="configTitle">Node Config</div>
            <button class="close-panel" onclick="closeConfigPanel()">✕</button>
        </div>
        <div class="config-body" id="configBody"></div>
        <div class="config-footer">
            <button class="btn btn-ghost" onclick="deleteSelectedNode()">🗑 Delete</button>
            <button class="btn btn-primary" onclick="applyConfig()">Apply</button>
        </div>
    </div>

</div>

<!-- Test Run Modal -->
<div class="modal-overlay" id="testModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal">
        <div class="modal-title">▶ Test Run Workflow</div>
        <div class="form-group">
            <label class="form-label">Applicant ID</label>
            <input class="form-input" id="testApplicantId" type="number" placeholder="Enter enrollment applicant ID">
            <div class="hint">The workflow will run on this applicant's data. Check logs after.</div>
        </div>
        <div id="testResult" style="display:none; margin-top: 12px;"></div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="document.getElementById('testModal').classList.remove('open')">Cancel</button>
            <button class="btn btn-blue" onclick="runTest()">▶ Run Now</button>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
const CSRF = '{{ csrf_token() }}';
const WORKFLOW_ID = {{ $workflow->id }};
let nodes = []; // { id, type, label, config, x, y }
let edges = []; // { id, source, target, conditionValue, label }
let selectedNodeId = null;
let nodeCounter = 0;
let edgeCounter = 0;
let isConnecting = false;
let connectFromId = null;
let isDraggingNode = false;
let dragNodeId = null;
let dragOffsetX = 0, dragOffsetY = 0;

const nodeConfig = {
    trigger:       { icon: '⚡', typeLabel: 'Trigger' },
    send_email:    { icon: '📧', typeLabel: 'Send Email' },
    change_status: { icon: '🔄', typeLabel: 'Change Status' },
    condition:     { icon: '🔀', typeLabel: 'Condition' },
    notify_admin:  { icon: '🔔', typeLabel: 'Notify Admin' },
    delay:         { icon: '⏱', typeLabel: 'Delay' },
};

// ── Load existing nodes from server ──────────────────────────────────
const serverNodes = @json($workflow->nodes);
const serverEdges = @json($workflow->edges);

window.addEventListener('DOMContentLoaded', () => {
    serverNodes.forEach(n => {
        nodeCounter = Math.max(nodeCounter, parseInt(n.node_key.replace('node_', '') || 0) + 1);
        addNodeToCanvas({
            id: n.node_key, type: n.type, label: n.label,
            config: n.config || {}, x: n.position_x, y: n.position_y,
        });
    });
    serverEdges.forEach(e => {
        edgeCounter = Math.max(edgeCounter, parseInt(e.edge_key.replace('edge_', '') || 0) + 1);
        edges.push({ id: e.edge_key, source: e.source_node_key, target: e.target_node_key, conditionValue: e.condition_value, label: e.label });
    });
    renderEdges();
    updateEmptyHint();
});

// ── Drag from palette ─────────────────────────────────────────────────
document.querySelectorAll('.node-palette-item').forEach(item => {
    item.addEventListener('dragstart', e => {
        e.dataTransfer.setData('node-type', item.dataset.type);
        e.dataTransfer.setData('node-label', item.dataset.label);
    });
});

function onDrop(e) {
    const type = e.dataTransfer.getData('node-type');
    const label = e.dataTransfer.getData('node-label');
    if (!type) return;
    const rect = document.getElementById('canvas').getBoundingClientRect();
    const x = e.clientX - rect.left - 95;
    const y = e.clientY - rect.top - 40;
    const id = `node_${nodeCounter++}`;
    addNodeToCanvas({ id, type, label, config: {}, x, y });
    nodes.push({ id, type, label, config: {}, x, y });
    updateEmptyHint();
}

// ── Render a single node ──────────────────────────────────────────────
function addNodeToCanvas(node) {
    if (!nodes.find(n => n.id === node.id)) nodes.push(node);
    const cfg = nodeConfig[node.type] || { icon: '❓', typeLabel: node.type };
    const el = document.createElement('div');
    el.className = `wf-node node-${node.type}`;
    el.id = `wfnode-${node.id}`;
    el.style.left = node.x + 'px';
    el.style.top = node.y + 'px';
    el.innerHTML = `
        <div class="wf-node-port port-top" data-node="${node.id}" data-side="top"></div>
        <div class="wf-node-header">
            <span class="wf-node-icon">${cfg.icon}</span>
            <span class="wf-node-type">${cfg.typeLabel}</span>
        </div>
        <div class="wf-node-body">
            <div class="wf-node-label">${node.label}</div>
            <div class="wf-node-summary" id="summary-${node.id}">${getSummary(node)}</div>
        </div>
        <div class="wf-node-port port-bottom" data-node="${node.id}" data-side="bottom"></div>
        <div class="wf-node-port port-right" data-node="${node.id}" data-side="right"></div>
    `;
    el.addEventListener('mousedown', (e) => startDragNode(e, node.id));
    el.addEventListener('click', (e) => {
        if (!isDraggingNode) selectNode(node.id);
    });
    // Port click → start connection
    el.querySelectorAll('.wf-node-port').forEach(port => {
        port.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            startConnecting(e, node.id);
        });
    });
    document.getElementById('canvasInner').appendChild(el);
}

function getSummary(node) {
    const c = node.config || {};
    if (node.type === 'send_email') return c.subject ? `Subject: ${c.subject}` : 'Click to configure email';
    if (node.type === 'change_status') return c.status ? `→ ${c.status}` : 'Click to set target status';
    if (node.type === 'condition') return c.field ? `${c.field} ${c.operator||'=='} ${c.value||''}` : 'Click to set condition';
    if (node.type === 'notify_admin') return c.admin_email || 'Click to configure';
    if (node.type === 'delay') return c.minutes ? `Wait ${c.minutes} min` : 'Click to set delay';
    if (node.type === 'trigger') return '{{ $workflow->trigger_event }}';
    return '';
}

// ── Node drag ─────────────────────────────────────────────────────────
function startDragNode(e, nodeId) {
    if (e.target.classList.contains('wf-node-port')) return;
    isDraggingNode = false;
    dragNodeId = nodeId;
    const el = document.getElementById(`wfnode-${nodeId}`);
    const rect = el.getBoundingClientRect();
    dragOffsetX = e.clientX - rect.left;
    dragOffsetY = e.clientY - rect.top;

    const onMove = (ev) => {
        isDraggingNode = true;
        const canvas = document.getElementById('canvas').getBoundingClientRect();
        const x = ev.clientX - canvas.left - dragOffsetX;
        const y = ev.clientY - canvas.top - dragOffsetY;
        el.style.left = x + 'px';
        el.style.top = y + 'px';
        const n = nodes.find(n => n.id === nodeId);
        if (n) { n.x = x; n.y = y; }
        renderEdges();
    };
    const onUp = () => {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
        setTimeout(() => { isDraggingNode = false; }, 50);
        dragNodeId = null;
    };
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
}

// ── Connection drawing ────────────────────────────────────────────────
let tempLine = null;
function startConnecting(e, nodeId) {
    e.preventDefault();
    connectFromId = nodeId;
    isConnecting = true;
    const canvas = document.getElementById('canvas').getBoundingClientRect();
    const n = nodes.find(n => n.id === nodeId);
    if (!n) return;
    const sx = n.x + 95, sy = n.y + 40;

    tempLine = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    tempLine.setAttribute('stroke', '#10b981');
    tempLine.setAttribute('stroke-width', '2');
    tempLine.setAttribute('fill', 'none');
    tempLine.setAttribute('stroke-dasharray', '6,4');
    document.getElementById('edgesSvg').appendChild(tempLine);

    const onMove = (ev) => {
        const tx = ev.clientX - canvas.left, ty = ev.clientY - canvas.top;
        tempLine.setAttribute('d', makePath(sx, sy, tx, ty));
    };
    const onUp = (ev) => {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
        if (tempLine) { tempLine.remove(); tempLine = null; }
        isConnecting = false;

        // Check if dropped on a node
        const target = document.elementFromPoint(ev.clientX, ev.clientY);
        const targetNode = target?.closest('.wf-node');
        if (targetNode) {
            const targetId = targetNode.id.replace('wfnode-', '');
            if (targetId !== connectFromId) {
                const eid = `edge_${edgeCounter++}`;
                edges.push({ id: eid, source: connectFromId, target: targetId, conditionValue: null, label: null });
                renderEdges();
            }
        }
        connectFromId = null;
    };
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
}

function makePath(x1, y1, x2, y2) {
    const dx = (x2 - x1) / 2;
    return `M ${x1} ${y1} C ${x1+dx} ${y1} ${x2-dx} ${y2} ${x2} ${y2}`;
}

function renderEdges() {
    const svg = document.getElementById('edgesSvg');
    // Clear existing permanent edges
    svg.querySelectorAll('.perm-edge').forEach(e => e.remove());

    edges.forEach(edge => {
        const sn = nodes.find(n => n.id === edge.source);
        const tn = nodes.find(n => n.id === edge.target);
        if (!sn || !tn) return;
        const sx = sn.x + 95, sy = sn.y + 80;
        const tx = tn.x + 95, ty = tn.y;

        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.classList.add('perm-edge');

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', makePath(sx, sy, tx, ty));
        path.setAttribute('stroke', edge.conditionValue === 'yes' ? '#10b981' : edge.conditionValue === 'no' ? '#ef4444' : '#334155');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        path.style.cursor = 'pointer';
        path.addEventListener('click', () => {
            if (confirm('Remove this connection?')) {
                edges = edges.filter(e => e.id !== edge.id);
                renderEdges();
            }
        });
        g.appendChild(path);

        // Arrow
        const arrow = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        arrow.setAttribute('cx', tx); arrow.setAttribute('cy', ty);
        arrow.setAttribute('r', '4'); arrow.setAttribute('fill', '#334155');
        g.appendChild(arrow);

        // Label
        if (edge.label || edge.conditionValue) {
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            const mx = (sx + tx) / 2, my = (sy + ty) / 2;
            text.setAttribute('x', mx); text.setAttribute('y', my - 6);
            text.setAttribute('fill', '#64748b'); text.setAttribute('font-size', '11');
            text.setAttribute('text-anchor', 'middle'); text.setAttribute('font-family', 'Inter, sans-serif');
            text.textContent = edge.conditionValue || edge.label || '';
            g.appendChild(text);
        }
        svg.appendChild(g);
    });
}

// ── Select Node ───────────────────────────────────────────────────────
function selectNode(nodeId) {
    document.querySelectorAll('.wf-node').forEach(n => n.classList.remove('selected'));
    const el = document.getElementById(`wfnode-${nodeId}`);
    if (el) el.classList.add('selected');
    selectedNodeId = nodeId;
    openConfigPanel(nodeId);
}

function openConfigPanel(nodeId) {
    const node = nodes.find(n => n.id === nodeId);
    if (!node) return;
    const cfg = nodeConfig[node.type] || {};
    document.getElementById('configTitle').textContent = `${cfg.icon || ''} ${cfg.typeLabel || node.type}`;
    document.getElementById('configBody').innerHTML = buildConfigForm(node);
    document.getElementById('configPanel').classList.remove('hidden');
}

function closeConfigPanel() {
    document.getElementById('configPanel').classList.add('hidden');
    document.querySelectorAll('.wf-node').forEach(n => n.classList.remove('selected'));
    selectedNodeId = null;
}

function buildConfigForm(node) {
    const c = node.config || {};
    let html = `
        <div class="form-group">
            <label class="form-label">Node Label</label>
            <input class="form-input" id="cfg_label" value="${node.label || ''}">
        </div>
    `;
    if (node.type === 'send_email' || node.type === 'notify_admin') {
        if (node.type === 'notify_admin') {
            html += `<div class="form-group"><label class="form-label">Admin Email</label><input class="form-input" id="cfg_admin_email" value="${c.admin_email || ''}"></div>`;
        }
        html += `
            <div class="form-group">
                <label class="form-label">Subject</label>
                <input class="form-input" id="cfg_subject" value="${c.subject || ''}">
                <div class="hint">Use @{{student_name}}, @{{grade_level}}, @{{status}}, @{{school_year}}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Body</label>
                <textarea class="form-input" id="cfg_body" rows="5">${c.body || ''}</textarea>
            </div>
        `;
    } else if (node.type === 'change_status') {
        html += `
            <div class="form-group">
                <label class="form-label">Target Status</label>
                <select class="form-input" id="cfg_status">
                    ${['draft','submitted','under_review','approved','rejected','enrolled','paid'].map(s => `<option value="${s}" ${c.status===s?'selected':''}>${s}</option>`).join('')}
                </select>
            </div>
        `;
    } else if (node.type === 'condition') {
        html += `
            <div class="form-group">
                <label class="form-label">Field</label>
                <select class="form-input" id="cfg_field">
                    ${['grade_level','status','school_year','learning_mode'].map(f => `<option value="${f}" ${c.field===f?'selected':''}>${f}</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Operator</label>
                <select class="form-input" id="cfg_operator">
                    ${['==','!=','contains','in'].map(op => `<option value="${op}" ${c.operator===op?'selected':''}>${op}</option>`).join('')}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Value</label>
                <input class="form-input" id="cfg_value" value="${c.value || ''}">
            </div>
        `;
    } else if (node.type === 'delay') {
        html += `
            <div class="form-group">
                <label class="form-label">Minutes to Wait</label>
                <input class="form-input" id="cfg_minutes" type="number" value="${c.minutes || 60}" min="1">
            </div>
        `;
    } else if (node.type === 'trigger') {
        html += `<div class="form-group"><div class="hint" style="color:#7c3aed;">This is the entry point. The workflow starts here when the trigger event fires.</div></div>`;
    }
    return html;
}

function applyConfig() {
    if (!selectedNodeId) return;
    const node = nodes.find(n => n.id === selectedNodeId);
    if (!node) return;
    const labelEl = document.getElementById('cfg_label');
    if (labelEl) node.label = labelEl.value;
    node.config = node.config || {};
    const fields = ['subject','body','status','field','operator','value','minutes','admin_email'];
    fields.forEach(f => {
        const el = document.getElementById(`cfg_${f}`);
        if (el) node.config[f] = el.value;
    });
    // Update DOM
    const el = document.getElementById(`wfnode-${selectedNodeId}`);
    if (el) {
        el.querySelector('.wf-node-label').textContent = node.label;
        el.querySelector(`#summary-${selectedNodeId}`).textContent = getSummary(node);
    }
    toast('Config applied!', 'success');
}

function deleteSelectedNode() {
    if (!selectedNodeId) return;
    if (!confirm('Delete this node?')) return;
    nodes = nodes.filter(n => n.id !== selectedNodeId);
    edges = edges.filter(e => e.source !== selectedNodeId && e.target !== selectedNodeId);
    document.getElementById(`wfnode-${selectedNodeId}`)?.remove();
    renderEdges();
    closeConfigPanel();
    updateEmptyHint();
}

function updateEmptyHint() {
    document.getElementById('emptyHint').style.display = nodes.length === 0 ? 'flex' : 'none';
}

// ── Save Canvas ───────────────────────────────────────────────────────
async function saveCanvas() {
    const payload = {
        nodes: nodes.map(n => ({ node_key: n.id, type: n.type, label: n.label, config: n.config || {}, position_x: n.x, position_y: n.y })),
        edges: edges.map(e => ({ edge_key: e.id, source_node_key: e.source, target_node_key: e.target, condition_value: e.conditionValue, label: e.label })),
    };
    const res = await fetch(`/workflow/api/workflows/${WORKFLOW_ID}/canvas`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(payload),
    });
    if (res.ok) { toast('Workflow saved!', 'success'); }
    else { toast('Save failed. Please try again.', 'error'); }
}

// ── Toggle Active ─────────────────────────────────────────────────────
async function toggleActive() {
    const res = await fetch(`/workflow/api/workflows/${WORKFLOW_ID}/toggle`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }
    });
    if (res.ok) {
        const data = await res.json();
        const badge = document.getElementById('statusBadge');
        const btn = document.getElementById('toggleBtn');
        if (data.is_active) {
            badge.textContent = '● Active'; badge.className = 'wf-status active';
            btn.textContent = '⏸ Deactivate';
        } else {
            badge.textContent = '○ Inactive'; badge.className = 'wf-status inactive';
            btn.textContent = '▶ Activate';
        }
        toast(data.is_active ? 'Workflow activated!' : 'Workflow deactivated.', 'success');
    }
}

// ── Test Run ──────────────────────────────────────────────────────────
function openTestModal() { document.getElementById('testModal').classList.add('open'); }

async function runTest() {
    const applicantId = document.getElementById('testApplicantId').value;
    if (!applicantId) { alert('Enter an applicant ID.'); return; }
    const res = await fetch(`/workflow/api/workflows/${WORKFLOW_ID}/test-run`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ applicant_id: parseInt(applicantId) }),
    });
    const result = document.getElementById('testResult');
    result.style.display = 'block';
    if (res.ok) {
        const data = await res.json();
        const logs = data.logs || [];
        result.innerHTML = `
            <div style="background:#064e3b;border:1px solid #065f46;border-radius:10px;padding:12px;">
                <div style="color:#34d399;font-weight:700;font-size:13px;margin-bottom:8px;">✅ Run #${data.id} — ${data.status.toUpperCase()}</div>
                ${logs.map(l => `<div style="font-size:11.5px;padding:4px 0;color:${l.status==='success'?'#a7f3d0':l.status==='failed'?'#fca5a5':'#94a3b8'};border-bottom:1px solid #065f46;">
                    ${l.status==='success'?'✓':'✗'} [${l.node_type}] ${l.message}</div>`).join('')}
            </div>`;
    } else {
        result.innerHTML = `<div style="background:#450a0a;border:1px solid #7f1d1d;border-radius:10px;padding:12px;color:#fca5a5;font-size:13px;">❌ Error running workflow. Check that the applicant ID exists.</div>`;
    }
}

// ── Toast ─────────────────────────────────────────────────────────────
function toast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.textContent = type === 'success' ? '✓ ' + msg : '✗ ' + msg;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(() => t.remove(), 3500);
}
</script>
</body>
</html>

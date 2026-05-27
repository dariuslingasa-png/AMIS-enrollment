<style>
    .family-group-card {
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 18px;
        box-shadow: 0 12px 35px rgba(15, 23, 42, 0.06);
        padding: 1.15rem;
    }

    .family-group-header,
    .family-group-actions,
    .family-child-card,
    .family-child-main,
    .family-child-footer,
    .family-review-items,
    .family-card-actions,
    .family-draft-card {
        display: flex;
        gap: 0.75rem;
    }

    .family-group-header {
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .family-group-title h3 {
        margin: 0.45rem 0 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 900;
    }

    .family-group-title h3 span,
    .family-group-title p,
    .family-muted {
        color: #64748b;
    }

    .family-group-title p {
        margin: 0.45rem 0 0;
        font-size: 0.9rem;
        line-height: 1.45;
    }

    .family-section-kicker {
        display: inline-flex;
        width: fit-content;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 0.68rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .family-group-actions,
    .family-review-items,
    .family-card-actions {
        align-items: center;
        flex-wrap: wrap;
    }

    .family-group-actions,
    .family-card-actions {
        justify-content: flex-end;
    }

    .family-list {
        display: grid;
        gap: 0.85rem;
    }

    .family-child-card {
        align-items: stretch;
        padding: 0.95rem;
        border: 1px solid #dbe3ee;
        border-radius: 18px;
        background: #fff;
    }

    .family-child-photo {
        width: 118px;
        height: 118px;
        flex: 0 0 118px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #dbe3ee;
        border-radius: 16px;
        background: #f8fafc;
    }

    .family-child-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .family-photo-placeholder {
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .family-child-main {
        min-width: 0;
        flex: 1;
        flex-direction: column;
        justify-content: space-between;
    }

    .family-child-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: flex-start;
    }

    .family-child-name {
        display: block;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        line-height: 1.25;
        text-transform: uppercase;
    }

    .family-child-meta,
    .family-muted,
    .family-draft-title {
        font-size: 0.73rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .family-child-meta {
        display: block;
        margin-top: 0.35rem;
        color: #64748b;
    }

    .family-child-footer {
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        padding-top: 0.75rem;
        border-top: 1px solid #eef2f7;
    }

    .family-group-count,
    .family-add-yes,
    .family-finalize,
    .family-status-badge,
    .family-chip,
    .family-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
    }

    .family-group-count {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #047857;
        padding: 0.35rem 0.8rem;
    }

    .family-add-yes {
        width: 100%;
        min-height: 46px;
        font-size: 0.8rem;
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #047857;
        padding: 0.5rem 1.2rem;
        transition: all 0.2s ease;
    }

    .family-add-yes:hover {
        background: #dcfce7;
        border-color: #86efac;
    }


    .family-finalize,
    .family-action-primary {
        background: #059669;
        color: #fff;
        padding: 0.45rem 0.9rem;
    }

    .family-action-danger {
        background: #dc2626;
        color: #fff;
        border: 1px solid #dc2626;
        padding: 0.45rem 0.85rem;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .family-action-danger:hover {
        background: #b91c1c;
        border-color: #b91c1c;
    }

    .family-action-payment {
        background: #f59e0b;
        color: #fff;
        padding: 0.45rem 0.9rem;
    }

    .family-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.32rem 0.75rem;
        text-transform: uppercase;
    }

    .family-status-badge.is-draft { background: #f1f5f9; color: #475569; }
    .family-status-badge.is-submitted { background: #dbeafe; color: #1d4ed8; }
    .family-status-badge.is-review { background: #fef3c7; color: #92400e; }
    .family-status-badge.is-complete { background: #dcfce7; color: #166534; }
    .family-status-badge.is-rejected { background: #fee2e2; color: #991b1b; }
    .family-status-badge.is-neutral { background: #f1f5f9; color: #334155; }

    .magnifying-glass-anim {
        animation: magnifyingScan 1.6s infinite ease-in-out;
    }

    @keyframes magnifyingScan {
        0%, 100% { transform: scale(1) translate(0, 0); }
        50% { transform: scale(1.18) translate(1px, 1px); }
    }

    .family-chip {
        min-height: 29px;
        gap: 0.35rem;
        padding: 0.3rem 0.7rem;
        border: 1px solid #fde68a;
        background: #fffaf0;
        color: #78350f;
    }

    .family-chip.is-missing {
        border-color: #fecaca;
        background: #fff1f2;
        color: #991b1b;
    }

    .family-chip.is-verified {
        border-color: #a7f3d0;
        background: #ecfdf5;
        color: #047857;
    }

    .family-chip.is-pending-grey {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #64748b;
    }

    .check-icon-anim {
        stroke-dasharray: 20;
        stroke-dashoffset: 20;
        transform-origin: center;
        animation: checkDraw 0.45s cubic-bezier(0.4, 0, 0.2, 1) forwards, checkBounce 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) 0.35s forwards;
    }

    @keyframes checkDraw {
        to {
            stroke-dashoffset: 0;
        }
    }

    @keyframes checkBounce {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }

    .family-dot-animation {
        display: inline-flex;
        align-items: center;
        gap: 2.5px;
        margin-right: 1.5px;
    }

    .family-dot-animation .dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        animation: familyDotPulse 1.4s infinite ease-in-out both;
    }

    .family-dot-animation.is-pending .dot {
        background-color: #f59e0b;
    }

    .family-dot-animation.is-verified .dot {
        background-color: #10b981;
    }

    .family-dot-animation .dot:nth-child(1) { animation-delay: -0.32s; }
    .family-dot-animation .dot:nth-child(2) { animation-delay: -0.16s; }

    @keyframes familyDotPulse {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
        40% { transform: scale(1.2); opacity: 1; }
    }

    .family-x {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fee2e2;
        color: #991b1b;
        font-size: 0.68rem;
        font-weight: 900;
        line-height: 1;
    }

    .family-draft-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    .family-payment-action-row {
        display: flex;
        justify-content: flex-end;
        margin-top: 1rem;
    }

    .family-draft-title {
        display: block;
        margin-bottom: 0.7rem;
        color: #64748b;
    }

    .family-draft-card {
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        padding: 0.85rem 0.95rem;
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        background: #f8fafc;
    }

    .family-draft-name {
        color: #0f172a;
        font-size: 0.92rem;
        font-weight: 950;
        text-transform: uppercase;
    }

    .family-card-actions form {
        margin: 0;
    }

    .family-card-actions button {
        border: 0;
        cursor: pointer;
        font-family: inherit;
    }

    @media (max-width: 760px) {
        .family-group-header,
        .family-child-card,
        .family-child-footer,
        .family-draft-card {
            flex-direction: column;
        }

        .family-child-top {
            grid-template-columns: 1fr;
        }

        .family-child-photo,
        .family-group-actions,
        .family-card-actions,
        .family-add-yes,
        .family-finalize {
            width: 100%;
        }

        .family-child-photo {
            height: 190px;
            flex-basis: auto;
        }

        .family-card-actions {
            justify-content: flex-start;
        }
    }

    /* Sibling Duplication Modal Styles */
    .duplicate-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .duplicate-modal-container {
        width: 100%;
        max-width: 540px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        padding: 1.75rem;
        border: 1px solid #e2e8f0;
    }

    .duplicate-modal-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.85rem;
    }

    .duplicate-modal-header h3 {
        margin: 0;
        color: #0f172a;
        font-size: 1.3rem;
        font-weight: 900;
    }

    .duplicate-modal-description {
        color: #475569;
        font-size: 0.92rem;
        line-height: 1.5;
        margin: 0 0 1.25rem;
    }

    .duplicate-modal-linked-data {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .linked-data-title {
        font-size: 0.75rem;
        font-weight: 900;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.6rem;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 0.35rem;
    }

    .linked-data-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .linked-data-list li {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .linked-data-list li strong {
        font-size: 0.72rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
    }

    .linked-data-list li span {
        font-size: 0.88rem;
        font-weight: 750;
        color: #0f172a;
    }

    .duplicate-modal-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .duplicate-btn-cancel,
    .duplicate-btn-fresh,
    .duplicate-btn-confirm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 900;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        padding: 0.5rem 1.15rem;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .duplicate-btn-cancel {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .duplicate-btn-cancel:hover {
        background: #e2e8f0;
    }

    .duplicate-btn-fresh {
        background: #fff;
        color: #0f172a;
        border: 1px solid #cbd5e1;
    }

    .duplicate-btn-fresh:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .duplicate-btn-confirm {
        background: #059669;
        color: #fff;
        border: 1px solid #059669;
    }

    .duplicate-btn-confirm:hover {
        background: #047857;
        border-color: #047857;
    }

    @media (max-width: 540px) {
        .duplicate-modal-actions {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
        }
        .duplicate-btn-cancel,
        .duplicate-btn-fresh,
        .duplicate-btn-confirm {
            width: 100%;
        }
    }
</style>

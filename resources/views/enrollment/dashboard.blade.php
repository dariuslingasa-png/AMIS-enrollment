<x-guest-layout>
    <div class="dashboard-page" x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 1200)">
        <!-- Loading Skeleton -->
        <div x-show="loading" x-cloak>
            <x-skeleton-dashboard />
        </div>

        <!-- Actual Dashboard Content -->
        <div x-show="!loading" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="dashboard-logo">
                    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo">
                    <div>
                        <h1>AMIS Enrollment Portal</h1>
                        <p>Welcome, {{ $user->name }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>

            <div class="dashboard-content">

                @if (session('info'))
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1d4ed8;font-size:0.875rem;margin-bottom:1.25rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ session('info') }}
                    </div>
                @endif

                @if (session('success'))
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#065f46;font-size:0.875rem;margin-bottom:1.25rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Smart Status Banner -->
                @php
                    $photo = $applicant?->photo_2x2_url;
                    $firstName = $applicant?->first_name ?? $user->name;
                    $docStatuses = $applicant?->document_statuses ?? [];
                    $rejectedDocs = collect([
                        'photo_2x2'   => '2x2 Photo',
                        'birth_cert'  => 'Birth Certificate',
                        'report_card' => 'Report Card',
                    ])->filter(fn($l, $k) => ($docStatuses[$k] ?? '') === 'rejected');
                @endphp

                @if (!$applicant || $applicant->status === 'draft' || !$applicant->status)
                    {{-- Blue: No application / draft --}}
                    <div style="background:linear-gradient(135deg,#1d4ed8,#1e40af);border-radius:16px;padding:1.75rem 1.5rem;margin-bottom:1.5rem;color:white;display:flex;align-items:center;gap:1.25rem;">
                        <div style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:1.125rem;font-weight:800;margin-bottom:0.25rem;">Hello, {{ $user->name }}!</div>
                            <div style="font-size:0.9rem;opacity:0.9;">
                                @if ($applicant && $applicant->status === 'draft')
                                    You have an enrollment in progress. Continue where you left off.
                                @else
                                    Start your enrollment application for SY 2026–2027.
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('enrollment.form') }}"
                           style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;background:white;color:#1d4ed8;border-radius:8px;font-size:0.875rem;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;">
                            {{ $applicant && $applicant->status === 'draft' ? 'Continue' : 'Start Now' }}
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>

                @elseif ($applicant->status === 'rejected')
                    {{-- Red: Rejected --}}
                    <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);border-radius:16px;padding:1.75rem 1.5rem;margin-bottom:1.5rem;color:white;display:flex;align-items:center;gap:1.25rem;">
                        <div style="flex-shrink:0;">
                            @if ($photo)
                                <img src="{{ asset('storage/' . $photo) }}" alt="Photo" style="width:60px;height:60px;object-fit:cover;border-radius:50%;border:2px solid rgba(255,255,255,0.4);">
                            @else
                                <div style="width:60px;height:60px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                            @endif
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:1.125rem;font-weight:800;margin-bottom:0.25rem;">Unfortunately, {{ $firstName }}.</div>
                            <div style="font-size:0.9rem;opacity:0.9;margin-bottom:0.5rem;">Your application was rejected. Please re-upload the following:</div>
                            @if ($rejectedDocs->count())
                                <div style="display:flex;flex-wrap:wrap;gap:0.375rem;">
                                    @foreach ($rejectedDocs as $docName)
                                        <span style="background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);border-radius:999px;padding:0.2rem 0.75rem;font-size:0.8125rem;font-weight:600;">
                                            {{ $docName }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('enrollment.form') }}"
                           style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;background:white;color:#dc2626;border-radius:8px;font-size:0.875rem;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;">
                            Re-upload
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>

                @elseif ($applicant->status === 'approved')
                    {{-- Green: Approved + animated confetti --}}
                    <style>
                        @keyframes confettiFall {
                            0%   { transform: translateY(-10px) rotate(0deg); opacity:1; }
                            100% { transform: translateY(80px) rotate(360deg); opacity:0; }
                        }
                        .confetti-piece {
                            position:absolute;
                            width:8px; height:8px;
                            border-radius:2px;
                            animation: confettiFall linear infinite;
                        }
                    </style>
                    <div style="background:linear-gradient(135deg,#059669,#047857);border-radius:16px;padding:1.75rem 1.5rem;margin-bottom:1.5rem;color:white;display:flex;align-items:center;gap:1.25rem;position:relative;overflow:hidden;">
                        {{-- Animated confetti --}}
                        <div style="position:absolute;inset:0;pointer-events:none;">
                            @php
                                $pieces = [
                                    ['8%',  '5%',  '#fde68a', '2.1s', '0s',   'rect'],
                                    ['18%', '80%', '#a7f3d0', '2.4s', '0.3s', 'circle'],
                                    ['30%', '20%', '#fca5a5', '1.9s', '0.6s', 'rect'],
                                    ['42%', '65%', '#bfdbfe', '2.6s', '0.1s', 'circle'],
                                    ['55%', '10%', '#fde68a', '2.2s', '0.8s', 'rect'],
                                    ['65%', '50%', '#a7f3d0', '2.0s', '0.4s', 'circle'],
                                    ['75%', '30%', '#fca5a5', '2.5s', '0.2s', 'rect'],
                                    ['85%', '75%', '#bfdbfe', '1.8s', '0.7s', 'circle'],
                                    ['92%', '45%', '#fde68a', '2.3s', '0.5s', 'rect'],
                                    ['50%', '90%', '#fca5a5', '2.1s', '0.9s', 'circle'],
                                    ['22%', '40%', '#bfdbfe', '2.7s', '0.3s', 'rect'],
                                    ['70%', '15%', '#a7f3d0', '1.9s', '0.6s', 'circle'],
                                ];
                            @endphp
                            @foreach ($pieces as [$top, $left, $color, $dur, $delay, $shape])
                                <div class="confetti-piece" style="
                                    top:{{ $top }};left:{{ $left }};
                                    background:{{ $color }};
                                    border-radius:{{ $shape === 'circle' ? '50%' : '2px' }};
                                    animation-duration:{{ $dur }};
                                    animation-delay:{{ $delay }};
                                    width:{{ $shape === 'circle' ? '7px' : '9px' }};
                                    height:{{ $shape === 'circle' ? '7px' : '6px' }};
                                    opacity:0.75;
                                "></div>
                            @endforeach
                        </div>

                        <div style="flex-shrink:0;position:relative;">
                            @if ($photo)
                                <img src="{{ asset('storage/' . $photo) }}" alt="Photo" style="width:64px;height:64px;object-fit:cover;border-radius:50%;border:3px solid rgba(255,255,255,0.5);">
                            @else
                                <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                            @endif
                        </div>
                        <div style="position:relative;">
                            <div style="font-size:0.8125rem;font-weight:600;opacity:0.85;margin-bottom:0.25rem;letter-spacing:0.05em;text-transform:uppercase;">OFFICIALLY ENROLLED — SY {{ $applicant->school_year }}</div>
                            <div style="font-size:1.375rem;font-weight:900;line-height:1.2;margin-bottom:0.25rem;">Congratulations, {{ $firstName }}!</div>
                            <div style="font-size:0.9rem;opacity:0.9;">Your enrollment has been approved. Please check your personal email inbox — your Microsoft school account credentials have been sent there.</div>
                        </div>
                    </div>

                @else
                    {{-- Default: pending/under review --}}
                    <div class="dashboard-welcome">
                        <div class="welcome-icon">
                            @if ($photo)
                                <img src="{{ asset('storage/' . $photo) }}" alt="Photo" style="width:56px;height:56px;object-fit:cover;border-radius:50%;border:2px solid rgba(255,255,255,0.4);">
                            @else
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="white"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            @endif
                        </div>
                        <div>
                            <h2>Hello, {{ $user->name }}!</h2>
                            <p>Your enrollment application has been submitted and is under review.</p>
                        </div>
                    </div>
                @endif

                @if ($applicant && $applicant->status === 'draft')
                    {{-- ── Draft Progress Card ── --}}
                    <div x-data="{ pct: {{ $applicant->completion_percentage }}, lastSaved: '{{ $applicant->updated_at->diffForHumans() }}', step: {{ $applicant->last_step }} }"
                         x-init="
                            fetch('{{ route('enrollment.status') }}')
                                .then(r => r.json())
                                .then(d => { if (d.percentage !== undefined) pct = d.percentage; if (d.last_step) step = d.last_step; if (d.last_saved) lastSaved = d.last_saved; })
                                .catch(() => {});
                         "
                         style="background:white;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                        <div style="padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                            <div style="flex:1;min-width:200px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                                    <span style="font-weight:700;font-size:0.9375rem;color:#111827;">Enrollment in Progress</span>
                                    <span style="font-weight:700;font-size:0.9375rem;color:#059669;" x-text="pct + '%'"></span>
                                </div>
                                <div style="height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;">
                                    <div :style="'height:100%;width:' + pct + '%;background:linear-gradient(90deg,#059669,#34d399);border-radius:999px;transition:width 0.6s;'"></div>
                                </div>
                                <div style="font-size:0.8125rem;color:#6b7280;margin-top:0.5rem;">
                                    Last saved: <span x-text="lastSaved"></span> &nbsp;·&nbsp; Step <span x-text="step"></span> of 5
                                </div>
                            </div>
                            <a href="{{ route('enrollment.form') }}"
                               style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;background:#059669;color:white;border-radius:8px;font-size:0.875rem;font-weight:600;text-decoration:none;white-space:nowrap;flex-shrink:0;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                Continue Application
                            </a>
                        </div>
                    </div>

                @elseif ($applicant)
                    {{-- ── Application Summary Card ── --}}
                    <div style="background:white;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                        {{-- Header --}}
                        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <div style="width:40px;height:40px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:1rem;color:#111827;">{{ $applicant->last_name }}, {{ $applicant->first_name }} {{ $applicant->middle_name }}</div>
                                    <div style="font-size:0.8125rem;color:#6b7280;">Submitted {{ $applicant->created_at->format('F j, Y') }}</div>
                                </div>
                            </div>
                            @php
                                $statusColors = [
                                    'pending'      => ['bg'=>'#fef9c3','color'=>'#854d0e','label'=>'Pending Review'],
                                    'submitted'    => ['bg'=>'#dbeafe','color'=>'#1e40af','label'=>'Submitted'],
                                    'under_review' => ['bg'=>'#ede9fe','color'=>'#5b21b6','label'=>'Under Review'],
                                    'approved'     => ['bg'=>'#dcfce7','color'=>'#166534','label'=>'Approved'],
                                    'rejected'     => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Rejected'],
                                ];
                                $sc = $statusColors[$applicant->status] ?? ['bg'=>'#f3f4f6','color'=>'#374151','label'=>ucfirst($applicant->status)];
                            @endphp
                            <span style="padding:0.3rem 0.875rem;border-radius:999px;font-size:0.8125rem;font-weight:600;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};">
                                {{ $sc['label'] }}
                            </span>
                        </div>

                        {{-- Details Grid --}}
                        <div style="padding:1.25rem 1.5rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
                            @php
                                $details = [
                                    'Student Type'     => $applicant->student_type . ' Student',
                                    'Grade Level'      => $applicant->grade_level,
                                    'Learning Mode'    => $applicant->learning_mode,
                                    'School Year'      => $applicant->school_year,
                                    'LRN'              => $applicant->lrn,
                                    'Gender'           => $applicant->gender,
                                    'Date of Birth'    => $applicant->date_of_birth?->format('M j, Y'),
                                    'Mobile'           => $applicant->mobile_number,
                                ];
                            @endphp
                            @foreach ($details as $label => $value)
                                <div>
                                    <div style="font-size:0.75rem;color:#9ca3af;font-weight:500;margin-bottom:0.2rem;">{{ $label }}</div>
                                    <div style="font-size:0.875rem;color:#111827;font-weight:600;">{{ $value ?? '—' }}</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Documents --}}
                        @php
                            $docs = [
                                '2x2 Picture'       => $applicant->photo_2x2_url,
                                'Birth Certificate' => $applicant->birth_cert_url,
                                'Report Card'       => $applicant->report_card_url,
                                'Marriage Contract' => $applicant->marriage_contract_url,
                                'Medical Record'    => $applicant->medical_record_url,
                            ];
                            $uploadedDocs = array_filter($docs);
                        @endphp
                        @if (count($uploadedDocs))
                            <div style="padding:0 1.5rem 1.25rem;">
                                <div style="font-size:0.75rem;color:#9ca3af;font-weight:500;margin-bottom:0.625rem;">UPLOADED DOCUMENTS</div>
                                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                                    @foreach ($uploadedDocs as $docLabel => $docPath)
                                        <a href="{{ asset('storage/' . $docPath) }}" target="_blank"
                                           style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:0.8125rem;color:#065f46;text-decoration:none;font-weight:500;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            {{ $docLabel }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Congratulations banner already shown above --}}

                    {{-- ── Rejection Notice (rejected) ── --}}
                    @if ($applicant->status === 'rejected')
                        @php
                            $docStatuses = $applicant->document_statuses ?? [];
                            $rejectedDocs = collect([
                                'photo_2x2'   => '2x2 Picture',
                                'birth_cert'  => 'Birth Certificate',
                                'report_card' => 'Report Card',
                            ])->filter(fn($l, $k) => ($docStatuses[$k] ?? '') === 'rejected');
                        @endphp
                        <div style="background:white;border-radius:16px;border:1.5px solid #fecdd3;overflow:hidden;margin-bottom:1.5rem;box-shadow:0 2px 8px rgba(220,38,38,0.08);">
                            <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem;">
                                <div style="width:44px;height:44px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </div>
                                <div>
                                    <div style="font-size:1rem;font-weight:800;color:white;">Application Rejected</div>
                                    <div style="font-size:0.8125rem;color:rgba(255,255,255,0.85);">Please fix the issues below and resubmit.</div>
                                </div>
                            </div>
                            <div style="padding:1.25rem 1.5rem;">
                                @if ($rejectedDocs->count())
                                    <div style="font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.75rem;">Documents that need to be re-uploaded:</div>
                                    <div style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1.25rem;">
                                        @foreach ($rejectedDocs as $key => $label)
                                            <div style="display:flex;align-items:center;gap:0.625rem;padding:0.625rem 0.875rem;background:#fff1f2;border:1px solid #fecdd3;border-radius:8px;font-size:0.875rem;color:#be123c;font-weight:500;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                {{ $label }} — rejected
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <a href="{{ route('enrollment.form') }}"
                                   style="display:flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;padding:0.875rem;background:#dc2626;color:white;border-radius:10px;font-size:0.9375rem;font-weight:700;text-decoration:none;transition:background 0.15s;"
                                   onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Fix & Resubmit Application
                                </a>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- ── Action Cards (no application yet) ── --}}
                    <div class="dashboard-cards">
                        <a href="{{ route('enrollment.form') }}" class="dashboard-card">
                            <div class="card-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10 9 9 9 8 9"/>
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>New Enrollment</h3>
                                <p>Submit a new student enrollment application</p>
                            </div>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-arrow">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    </div>
                @endif

                {{-- ── Payment for Enrollment Fee Card ── --}}
                <div class="dashboard-cards" style="margin-top:0;">
                    @php
                        $paymentEnabled = $applicant && in_array($applicant->status, ['pending','submitted','under_review','approved']);
                    @endphp

                    @if ($payment)
                        {{-- Payment submitted — show status card --}}
                        @php
                            $pmColors = [
                                'pending'  => ['bg'=>'#fef9c3','color'=>'#854d0e','label'=>'Pending Verification'],
                                'verified' => ['bg'=>'#dcfce7','color'=>'#166534','label'=>'Verified'],
                                'rejected' => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Rejected'],
                            ];
                            $pm = $pmColors[$payment->status] ?? ['bg'=>'#f3f4f6','color'=>'#374151','label'=>ucfirst($payment->status)];
                            $methodLogos = ['gcash'=>'GCASH.png','maya'=>'MAYA.png','bdo'=>'BDO.png'];
                            $logo = $methodLogos[$payment->method] ?? null;
                        @endphp
                        <div style="background:white;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                            <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                                <div style="display:flex;align-items:center;gap:0.75rem;">
                                    <div style="width:40px;height:40px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
                                            <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:1rem;color:#111827;">Enrollment Fee Payment</div>
                                        <div style="font-size:0.8125rem;color:#6b7280;">Submitted {{ $payment->paid_at->format('F j, Y \a\t g:i A') }}</div>
                                    </div>
                                </div>
                                <span style="padding:0.3rem 0.875rem;border-radius:999px;font-size:0.8125rem;font-weight:600;background:{{ $pm['bg'] }};color:{{ $pm['color'] }};">
                                    {{ $pm['label'] }}
                                </span>
                            </div>
                            <div style="padding:1.25rem 1.5rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;align-items:center;">
                                <div>
                                    <div style="font-size:0.75rem;color:#9ca3af;font-weight:500;margin-bottom:0.2rem;">Amount Paid</div>
                                    <div style="font-size:1rem;font-weight:800;color:#059669;">₱{{ number_format($payment->amount, 2) }}</div>
                                </div>
                                <div>
                                    <div style="font-size:0.75rem;color:#9ca3af;font-weight:500;margin-bottom:0.2rem;">Payment Method</div>
                                    @if ($logo)
                                        <img src="{{ asset('images/mode_of_payments/' . $logo) }}" alt="{{ $payment->method }}" style="height:22px;object-fit:contain;">
                                    @else
                                        <div style="font-size:0.875rem;font-weight:600;color:#111827;">{{ strtoupper($payment->method) }}</div>
                                    @endif
                                </div>
                                @if ($payment->receipt_url)
                                <div>
                                    <div style="font-size:0.75rem;color:#9ca3af;font-weight:500;margin-bottom:0.2rem;">Receipt</div>
                                    <a href="{{ asset('storage/' . $payment->receipt_url) }}" target="_blank"
                                       style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.3rem 0.75rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:0.8125rem;color:#065f46;text-decoration:none;font-weight:500;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        View
                                    </a>
                                </div>
                                @endif
                                @if ($payment->status === 'verified' && $payment->verified_at)
                                <div>
                                    <div style="font-size:0.75rem;color:#9ca3af;font-weight:500;margin-bottom:0.2rem;">Verified On</div>
                                    <div style="font-size:0.875rem;font-weight:600;color:#111827;">{{ $payment->verified_at->format('M j, Y') }}</div>
                                </div>
                                @endif
                            </div>
                            @if ($payment->status === 'pending')
                            <div style="padding:0.75rem 1.5rem;background:#fffbeb;border-top:1px solid #fde68a;display:flex;align-items:center;gap:0.5rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span style="font-size:0.8125rem;color:#92400e;">Your payment is being reviewed. The Finance Office will verify it within 1–2 business days.</span>
                            </div>
                            @elseif ($payment->status === 'rejected')
                            <div style="padding:0.75rem 1.5rem;background:#fff1f2;border-top:1px solid #fecdd3;display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
                                <span style="font-size:0.8125rem;color:#be123c;">{{ $payment->remarks ?? 'Your payment was rejected. Please resubmit with a valid receipt.' }}</span>
                                <a href="{{ route('enrollment.payment') }}" style="font-size:0.8125rem;font-weight:600;color:#059669;text-decoration:none;">Resubmit →</a>
                            </div>
                            @endif
                        </div>

                    @elseif ($paymentEnabled)
                        <a href="{{ route('enrollment.payment') }}" class="dashboard-card">
                            <div class="card-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Payment for Enrollment Fee</h3>
                                <p>Pay your enrollment fee for School Year 2026–2027</p>
                            </div>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-arrow">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    @else
                        <div class="dashboard-card disabled">
                            <div class="card-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Payment for Enrollment Fee</h3>
                                <p>Available after submitting your enrollment application</p>
                            </div>
                            <span class="coming-soon">Locked</span>
                        </div>
                    @endif
                </div>

                <!-- Help Section -->
                <div class="dashboard-info">
                    <h3>Need Help?</h3>
                    <p>Contact our admissions office for assistance</p>
                    <div class="info-contacts">
                        <div class="info-item">
                            <strong>Email:</strong> admission@almunawwara.edu.ph
                        </div>
                        <div class="info-item">
                            <strong>Phone:</strong> (02) 1234-5678
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>

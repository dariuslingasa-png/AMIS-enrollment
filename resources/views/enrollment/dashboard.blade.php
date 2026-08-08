<x-guest-layout>
    <style>
        @keyframes active-pulse {
            0% { transform: scale(0.9); opacity: 0.6; }
            50% { transform: scale(1.25); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.6; }
        }
    </style>
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
                        <h1>AMIS Enrollment System</h1>
                        <p style="margin-bottom: 4px;">Welcome, {{ $user->name }}</p>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; align-items: center;">
                            <!-- Activity Status Badge -->
                            @if($user->isActive())
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2.5px 10px; border-radius: 999px; background: #ecfdf5; border: 1.5px solid #a7f3d0; color: #047857; font-size: 11px; font-weight: 700; line-height: 1; text-transform: uppercase; tracking-wider;">
                                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #10b981; animation: active-pulse 1.5s infinite;"></span>
                                    Active Session
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2.5px 10px; border-radius: 999px; background: #f1f5f9; border: 1.5px solid #cbd5e1; color: #475569; font-size: 11px; font-weight: 700; line-height: 1; text-transform: uppercase; tracking-wider;">
                                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #64748b;"></span>
                                    Inactive Session
                                </span>
                            @endif

                            <!-- Last Active Time Badge -->
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2.5px 10px; border-radius: 999px; background: #f8fafc; border: 1.5px solid #e2e8f0; color: #64748b; font-size: 11px; font-weight: 600; line-height: 1;">
                                Last active: {{ $user->last_active_at ? $user->last_active_at->diffForHumans() : 'Never' }}
                            </span>

                            <!-- Email Verified Badge -->
                            @if($user->hasVerifiedEmail() && $user->account_status === 'verified')
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2.5px 10px; border-radius: 999px; background: #ecfdf5; border: 1.5px solid #a7f3d0; color: #047857; font-size: 11px; font-weight: 700; line-height: 1; text-transform: uppercase; tracking-wider;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" style="flex-shrink: 0;"><polyline points="20 6 9 17 4 12"/></svg>
                                    Email Verified
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2.5px 10px; border-radius: 999px; background: #fffbeb; border: 1.5px solid #fde68a; color: #b45309; font-size: 11px; font-weight: 700; line-height: 1; text-transform: uppercase; tracking-wider;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    Email Unverified
                                </span>
                            @endif
                        </div>
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

                <!-- Smart Status Banner -->
                @php
                    $applicants = $applicants ?? collect([$applicant])->filter();
                    
                    // Sibling Banner Prioritisation Logic:
                    // 1. Rejected (requires urgent user correction)
                    // 2. Ready for Submission (requires finalizing and paying)
                    // 3. Draft (work in progress)
                    // 4. Approved (all enrolled)
                    // 5. Pending (under administrative review)
                    $rejectedApplicant = $applicants->where('status', 'rejected')->first();
                    $readyApplicant = $applicants->where('status', 'ready_for_submission')->first();
                    $draftApplicant = $applicants->where('status', 'draft')->first();
                    $approvedCount = $applicants->where('status', 'approved')->count();
                    
                    if ($rejectedApplicant) {
                        $bannerType = 'rejected';
                        $activeApplicant = $rejectedApplicant;
                    } elseif ($readyApplicant) {
                        $bannerType = 'ready_for_submission';
                        $activeApplicant = $readyApplicant;
                    } elseif ($applicants->count() === 0 || $draftApplicant) {
                        $bannerType = 'draft';
                        $activeApplicant = $draftApplicant;
                    } elseif ($applicants->count() > 0 && $approvedCount === $applicants->count()) {
                        $bannerType = 'approved';
                        $activeApplicant = $applicants->first();
                    } else {
                        $bannerType = 'pending';
                        $activeApplicant = $applicants->first();
                    }
                    
                    $photo = $activeApplicant?->photo_2x2_url;
                    $firstName = $activeApplicant?->first_name ?? $user->name;
                    $studentDisplayName = $activeApplicant ? (trim(($activeApplicant->last_name ?? '') . ', ' . ($activeApplicant->first_name ?? '') . ' ' . ($activeApplicant->middle_name ?? '')) ?: $firstName) : $user->name;
                    $docStatuses = $activeApplicant?->document_statuses ?? [];
                    
                    $rejectedDocs = collect([
                        'photo_2x2'   => '2x2 Photo',
                        'birth_cert'  => 'Birth Certificate',
                        'report_card' => 'Report Card',
                    ])->filter(fn($l, $k) => ($docStatuses[$k] ?? '') === 'rejected');
                    
                    $requiredGuide = [
                        ['label' => 'Student profile', 'done' => $activeApplicant && $activeApplicant->first_name && $activeApplicant->last_name && $activeApplicant->grade_level],
                        ['label' => 'Religion, country, and contact number', 'done' => $activeApplicant && $activeApplicant->religion && $activeApplicant->country && $activeApplicant->mobile_number],
                        ['label' => 'Parent or guardian contact', 'done' => $activeApplicant && $activeApplicant->parent_mobile],
                        ['label' => 'Emergency contact', 'done' => $activeApplicant && $activeApplicant->emergency_name && $activeApplicant->emergency_phone],
                        ['label' => 'Recent 1:1 or annual photo', 'done' => $activeApplicant && $activeApplicant->photo_2x2_url],
                        ['label' => 'Report card or signed temporary proof', 'done' => $activeApplicant && (in_array($activeApplicant->grade_level, ['Kinder 1', 'Kinder 2'], true) || $activeApplicant->report_card_url || $activeApplicant->affidavit_url)],
                    ];
                    
                    $optionalGuide = [
                        ['label' => 'Birth certificate copy, if available', 'done' => $activeApplicant && $activeApplicant->birth_cert_url],
                        ['label' => 'Medical record or health history', 'done' => $activeApplicant && ($activeApplicant->medical_record_url || $activeApplicant->psych_testing || $activeApplicant->prescription_med)],
                        ['label' => 'Marriage contract, if applicable', 'done' => $activeApplicant && $activeApplicant->marriage_contract_url],
                        ['label' => 'Physician details, if available', 'done' => $activeApplicant && ($activeApplicant->family_physician || $activeApplicant->physician_phone)],
                    ];
                    
                    $canAddAnotherChild = $canAddAnotherChild ?? false;
                    $readyApplications = $readyApplications ?? collect();
                    $draftApplications = $draftApplications ?? collect();
                    $multipleApplicants = $applicants->count() > 1;
                    $submittedApplications = $applicants->filter(fn ($item) => in_array($item->status, ['pending', 'submitted', 'under_review'], true));
                    $rejectedApplications = $applicants->where('status', 'rejected');
                    $approvedApplications = $applicants->where('status', 'approved');
                    
                    $familyStatusSummary = collect([
                        $submittedApplications->count() ? $submittedApplications->count() . ' under review' : null,
                        $readyApplications->count() ? $readyApplications->count() . ' ready' : null,
                        $draftApplications->count() ? $draftApplications->count() . ' draft' : null,
                        $rejectedApplications->count() ? $rejectedApplications->count() . ' needs fixing' : null,
                        $approvedApplications->count() ? $approvedApplications->count() . ' approved' : null,
                    ])->filter()->implode(', ');

                    $totalSiblings = $applicants->count();
                    $pendingCount = $submittedApplications->count();
                    $approvedCount = $approvedApplications->count();
                    $draftCount = $draftApplications->count();
                    $rejectedCount = $rejectedApplications->count();
                    $readyCount = $applicants->where('status', 'ready_for_submission')->count();
                @endphp

                @if ($bannerType === 'draft')
                    {{-- Blue: No application / draft --}}
                    <div style="background:linear-gradient(135deg,#1d4ed8,#1e40af);border-radius:16px;padding:1.75rem 1.5rem;margin-bottom:1.5rem;color:white;display:flex;align-items:center;gap:1.25rem;">
                        <div style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:1.125rem;font-weight:800;margin-bottom:0.25rem;">Hello, {{ $user->name }}!</div>
                            <div style="font-size:0.9rem;opacity:0.9;">
                                @if ($activeApplicant && $activeApplicant->status === 'draft')
                                    You have an enrollment in progress. Continue where you left off.
                                @else
                                    Start your enrollment application for SY 2026–2027.
                                @endif
                            </div>
                        </div>
                        <a href="{{ $activeApplicant ? route('enrollment.form.child', $activeApplicant) : route('enrollment.form', ['fresh' => 1]) }}"
                           style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;background:white;color:#1d4ed8;border-radius:8px;font-size:0.875rem;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;">
                            {{ $activeApplicant && $activeApplicant->status === 'draft' ? 'Continue' : 'Start Now' }}
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>

                @elseif ($bannerType === 'rejected')
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
                            <div style="font-size:1.125rem;font-weight:800;margin-bottom:0.25rem;">Unfortunately, {{ $studentDisplayName }}.</div>
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
                        <a href="{{ route('enrollment.form.child', $activeApplicant) }}"
                           style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;background:white;color:#dc2626;border-radius:8px;font-size:0.875rem;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;">
                            Re-upload
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>

                @elseif ($bannerType === 'ready_for_submission')
                    @php
                        $readyNames = $applicants->where('status', 'ready_for_submission')->pluck('first_name')->filter()->map(fn($n) => strtoupper($n))->toArray();
                        $readyCount = count($readyNames);
                        $totalSiblings = $applicants->count();
                        
                        $pendingCount = $applicants->whereIn('status', ['pending', 'submitted', 'under_review'])->count();
                        $approvedCount = $applicants->where('status', 'approved')->count();
                        $draftCount = $applicants->where('status', 'draft')->count();
                        $rejectedCount = $applicants->where('status', 'rejected')->count();
                    @endphp
                    <div style="background:linear-gradient(135deg,#0f766e,#115e59); border-radius:16px; padding:1.75rem 1.5rem; margin-bottom:1.5rem; color:white; text-align: left;">
                        <div style="text-align: left;">
                            @if ($readyCount >= 2)
                                <h2 style="font-size:1.25rem; font-weight:800; margin:0 0 0.5rem 0; line-height:1.25; color: white;">{{ $readyCount }} siblings are ready for submission.</h2>
                                <div style="font-size:0.9rem; opacity:0.95; line-height:1.45; margin-bottom:0.75rem;">
                                    <strong>Dear Parents,</strong> the enrollment applications for <strong style="text-decoration:underline;">{{ implode(', ', $readyNames) }}</strong> are fully completed and ready to be submitted.
                                </div>
                            @else
                                <h2 style="font-size:1.25rem; font-weight:800; margin:0 0 0.5rem 0; line-height:1.25; color: white;">{{ strtoupper($studentDisplayName) }} IS READY FOR SUBMISSION</h2>
                                <div style="font-size:0.9rem; opacity:0.95; line-height:1.45; margin-bottom:0.75rem;">
                                    <strong>Dear Parents,</strong> the enrollment application for <strong style="text-decoration:underline;">{{ strtoupper($studentDisplayName) }}</strong> is fully completed and ready to be submitted.
                                </div>
                            @endif
                            
                            <div style="font-size:0.875rem; opacity:0.9; line-height:1.45;">
                                Please proceed to <strong>Finalize & Submit</strong> below to settle the payment and complete the enrollment process. You may also add another child first if you wish to enroll multiple siblings together.
                            </div>

                            {{-- Dynamic sibling status summary --}}
                            @if ($totalSiblings > 1)
                                <div style="margin-top:1.15rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.18); font-size:0.82rem; opacity:0.95; display:flex; flex-direction:column; gap:0.4rem;">
                                    <div style="font-weight:700; color:#fde68a; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.025em; margin-bottom:0.15rem;">Sibling Application Status Summary</div>
                                    
                                    @if ($pendingCount > 0)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span style="display:inline-block; width:6px; height:6px; background:#fde68a; border-radius:50%;"></span>
                                            <span>{{ $pendingCount }} sibling {{ \Illuminate\Support\Str::plural('application', $pendingCount) }}: <strong style="color:#fde68a;">Pending Review by Admin/Registrar</strong> (verification takes 1–2 banking/business days). No action is required. We are currently verifying the details.</span>
                                        </div>
                                    @endif
                                    @if ($approvedCount > 0)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span style="display:inline-block; width:6px; height:6px; background:#34d399; border-radius:50%;"></span>
                                            <span>{{ $approvedCount }} sibling {{ \Illuminate\Support\Str::plural('application', $approvedCount) }}: <strong style="color:#a7f3d0;">Approved & Enrolled</strong>. Check your email inbox for welcome packs and updates.</span>
                                        </div>
                                    @endif
                                    @if ($draftCount > 0)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span style="display:inline-block; width:6px; height:6px; background:#60a5fa; border-radius:50%;"></span>
                                            <span>{{ $draftCount }} sibling {{ \Illuminate\Support\Str::plural('application', $draftCount) }}: <strong>Draft Stage</strong> (in progress). You can complete their profiles to submit them.</span>
                                        </div>
                                    @endif
                                    @if ($rejectedCount > 0)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span style="display:inline-block; width:6px; height:6px; background:#f87171; border-radius:50%;"></span>
                                            <span style="color:#fecaca;">{{ $rejectedCount }} sibling {{ \Illuminate\Support\Str::plural('application', $rejectedCount) }}: <strong style="color:#fca5a5;">Returned for Correction</strong>. Click "Re-upload" on their cards below to fix.</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                @elseif ($bannerType === 'approved')
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
                            <div style="font-size:0.8125rem;font-weight:600;opacity:0.85;margin-bottom:0.25rem;letter-spacing:0.05em;text-transform:uppercase;">OFFICIALLY ENROLLED — SY {{ $activeApplicant->school_year }}</div>
                            <div style="font-size:1.375rem;font-weight:900;line-height:1.2;margin-bottom:0.25rem;">Congratulations, {{ $studentDisplayName }}!</div>
                            <div style="font-size:0.9rem;opacity:0.9;">Your enrollment has been approved. Please check your personal email inbox for important enrollment details, class schedule, and further school updates.</div>
                        </div>
                    </div>

                @else
                    {{-- Default: pending/under review --}}
                    <div style="background:linear-gradient(135deg,#0f766e,#115e59); border-radius:16px; padding:1.75rem 1.5rem; margin-bottom:1.5rem; color:white; text-align: left;">
                        <div style="text-align: left;">
                            <h2 style="font-size:1.25rem; font-weight:800; margin:0 0 0.5rem 0; line-height:1.25; color: white; text-transform: uppercase; letter-spacing: 0.05em;">ENROLLMENT APPLICATIONS UNDER REVIEW</h2>
                            <div style="font-size:0.9rem; opacity:0.95; line-height:1.45; margin-bottom:0.75rem;">
                                <strong>Dear Parents,</strong> your enrollment {{ $totalSiblings > 1 ? 'applications have' : 'application has' }} been successfully submitted and {{ $totalSiblings > 1 ? 'are' : 'is' }} currently pending review by the school registrar and admin office. We will verify your submitted documents and transaction references shortly. <strong>Please wait 1–2 banking/business days for verification.</strong> No further action is required at this stage.
                            </div>

                            <!-- Fast-Track Approval / Facebook Follow-Up Card -->
                            <div style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.25); border-radius: 14px; padding: 0.95rem 1.15rem; margin-top: 0.85rem; backdrop-filter: blur(8px);">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #ffffff; color: #0f766e; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 0.75rem; font-weight: 800; color: #fde68a; text-transform: uppercase; letter-spacing: 0.05em;">Need Faster Approval?</div>
                                        <div style="font-size: 0.88rem; color: #ffffff; font-weight: 500; margin-top: 0.15rem; line-height: 1.4;">
                                            Message <strong>Sir Mohaymen Unos</strong> directly on Facebook to fast-track your enrollment approval.
                                        </div>
                                        <a href="https://web.facebook.com/sirmo.amis" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 0.35rem; margin-top: 0.55rem; background: #ffffff; color: #0f766e; font-size: 0.82rem; font-weight: 800; padding: 0.45rem 0.9rem; border-radius: 8px; text-decoration: none; transition: background 0.15s ease;">
                                            <span>Message Sir Mohaymen on Facebook</span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="7" y1="17" x2="17" y2="7"></line>
                                                <polyline points="7 7 17 7 17 17"></polyline>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Dynamic sibling status summary --}}
                            @if ($totalSiblings > 1)
                                <div style="margin-top:1.15rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.18); font-size:0.82rem; opacity:0.95; display:flex; flex-direction:column; gap:0.4rem;">
                                    <div style="font-weight:700; color:#fde68a; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.025em; margin-bottom:0.15rem;">Sibling Application Status Summary</div>
                                    
                                    @if ($pendingCount > 0)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span style="display:inline-block; width:6px; height:6px; background:#fde68a; border-radius:50%;"></span>
                                            <span>{{ $pendingCount }} sibling {{ \Illuminate\Support\Str::plural('application', $pendingCount) }}: <strong style="color:#fde68a;">Pending Review by Admin/Registrar</strong> (verification takes 1–2 banking/business days). No action is required. We are currently verifying the details.</span>
                                        </div>
                                    @endif
                                    @if ($approvedCount > 0)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span style="display:inline-block; width:6px; height:6px; background:#34d399; border-radius:50%;"></span>
                                            <span>{{ $approvedCount }} sibling {{ \Illuminate\Support\Str::plural('application', $approvedCount) }}: <strong style="color:#a7f3d0;">Approved & Enrolled</strong>. Check your email inbox for welcome packs and updates.</span>
                                        </div>
                                    @endif
                                    @if ($draftCount > 0)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span style="display:inline-block; width:6px; height:6px; background:#60a5fa; border-radius:50%;"></span>
                                            <span>{{ $draftCount }} sibling {{ \Illuminate\Support\Str::plural('application', $draftCount) }}: <strong>Draft Stage</strong> (in progress). You can complete their profiles to submit them.</span>
                                        </div>
                                    @endif
                                    @if ($rejectedCount > 0)
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <span style="display:inline-block; width:6px; height:6px; background:#f87171; border-radius:50%;"></span>
                                            <span style="color:#fecaca;">{{ $rejectedCount }} sibling {{ \Illuminate\Support\Str::plural('application', $rejectedCount) }}: <strong style="color:#fca5a5;">Returned for Correction</strong>. Click "Re-upload" on their cards below to fix.</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif


                @if ($applicants->count())
                    <x-dashboard.family-group
                        :user="$user"
                        :applicants="$applicants"
                        :applicant="$applicant"
                        :can-add-another-child="$canAddAnotherChild"
                        :ready-applications="$readyApplications"
                        :draft-applications="$draftApplications"
                    />
                @endif

                @if (false)
                    {{-- legacy draft card disabled; progress now lives in each child card --}}
                    <div class="dashboard-action-grid enrollment-progress-grid">
                    <div class="enrollment-progress-card" x-data="{ pct: {{ $applicant->completion_percentage }}, lastSaved: '{{ $applicant->updated_at->diffForHumans() }}', step: {{ $applicant->last_step }} }"
                         x-init="
                            fetch('{{ route('enrollment.status', ['applicant' => $applicant->id]) }}')
                                .then(r => r.json())
                                .then(d => { if (d.percentage !== undefined) pct = d.percentage; if (d.last_step) step = d.last_step; if (d.last_saved) lastSaved = d.last_saved; })
                                .catch(() => {});
                         "
                         style="background:white;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:0;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                        <div style="padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                            <div style="flex:1;min-width:200px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
                                    <span style="font-weight:700;font-size:0.9375rem;color:#111827;">Enrollment in Progress</span>
                                    <span style="font-weight:700;font-size:0.9375rem;color:#059669;" x-text="pct + '%'"></span>
                                </div>
                                <div style="font-size:0.92rem;color:#111827;font-weight:800;margin:-0.15rem 0 0.55rem;">
                                    {{ $applicant->full_name ?: 'Student Name' }}
                                </div>
                                <div style="height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;">
                                    <div :style="'height:100%;width:' + pct + '%;background:linear-gradient(90deg,#059669,#34d399);border-radius:999px;transition:width 0.6s;'"></div>
                                </div>
                                <div style="font-size:0.8125rem;color:#6b7280;margin-top:0.5rem;">
                                    Last saved: <span x-text="lastSaved"></span> &nbsp;·&nbsp; Step <span x-text="Math.min(step, 7)"></span> of 7
                                </div>
                            </div>
                            <div class="draft-action-group">
                                <a href="{{ route('enrollment.form.child', $applicant) }}" class="draft-action-primary">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                    Continue Draft
                                </a>
                                <form method="POST" action="{{ route('enrollment.draft.discard') }}" data-clear-draft-form data-confirm-message="Discard changes? This will remove the saved draft for this child.">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
                                    <button type="submit" class="draft-action-danger">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6l-1 14H6L5 6"/>
                                            <path d="M10 11v6"/>
                                            <path d="M14 11v6"/>
                                            <path d="M9 6V4h6v2"/>
                                        </svg>
                                        Delete Draft
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    </div>

                @elseif (false)
                    <x-dashboard.application-summary :applicant="$applicant" />
                @elseif (!$applicants->count())
                    {{-- ── Action Cards (no application yet) ── --}}
                    <div class="dashboard-cards">
                        <a href="{{ route('enrollment.form', ['fresh' => 1]) }}" class="dashboard-card">
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
                @if (false)
                <div class="dashboard-action-grid" style="margin-top:0;">
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
                                <a href="{{ route('enrollment.payment', ['applicant' => $applicant->id]) }}" style="font-size:0.8125rem;font-weight:600;color:#059669;text-decoration:none;">Resubmit →</a>
                            </div>
                            @endif
                        </div>

                    @elseif ($paymentEnabled)
                        <a href="{{ route('enrollment.payment', ['applicant' => $applicant->id]) }}" class="dashboard-card">
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
                @endif

                <!-- Enrollment FAQ Section -->
                <x-enrollment-faq-modal />

                <!-- Help Section (Static Display) -->
                <div class="dashboard-info" style="margin-top: 1.5rem;">
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        Need Help?
                    </h3>
                    <p style="margin: 4px 0 1.15rem 0; font-size: 0.85rem; color: #64748b; font-weight: 500;">
                        Contact our technical support staff for assistance.
                    </p>

                    <div style="max-width: 480px;">
                        <a href="https://web.facebook.com/sirmo.amis" target="_blank" rel="noopener" class="support-card" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.15rem; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.75rem; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 0.05em;">Technical Staff</div>
                                <div style="font-size: 1rem; font-weight: 800; color: #0f172a; margin-top: 0.1rem;">Sir Mohaymen Unos</div>
                                <div style="font-size: 0.82rem; color: #1d4ed8; font-weight: 600; margin-top: 0.2rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <span>Message on Facebook</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="7" y1="17" x2="17" y2="7"></line>
                                        <polyline points="7 7 17 7 17 17"></polyline>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>

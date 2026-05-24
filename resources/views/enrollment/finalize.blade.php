<x-guest-layout>
    <div class="dashboard-page">
        <div class="dashboard-header">
            <div class="dashboard-logo">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo">
                <div>
                    <h1>Finalize Enrollment</h1>
                    <p>Review and confirm before official submission.</p>
                </div>
            </div>
            <a href="{{ route('enrollment.dashboard') }}" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;background:#f3f4f6;color:#374151;font-size:0.85rem;font-weight:600;border-radius:8px;text-decoration:none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </a>
        </div>

        <div class="dashboard-content">
            @if (session('error'))
                <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:0.75rem 1rem;border-radius:10px;font-size:0.85rem;margin-bottom:1.25rem;">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Parent Summary Card --}}
            @php
                $parentSource = $readyApplications->first();
                $parentDetails = [
                    ['label' => 'Account', 'value' => $user->email, 'icon' => 'mail'],
                    ['label' => 'Parent Mobile', 'value' => trim(($parentSource?->parent_country_code ?? '') . ' ' . ($parentSource?->parent_mobile ?? '')), 'icon' => 'phone'],
                    ['label' => 'Father', 'value' => trim(($parentSource?->father_first_name ?? '') . ' ' . ($parentSource?->father_last_name ?? '')), 'icon' => 'user'],
                    ['label' => 'Mother', 'value' => trim(($parentSource?->mother_first_name ?? '') . ' ' . ($parentSource?->mother_last_name ?? '')), 'icon' => 'user'],
                    ['label' => 'Emergency', 'value' => trim(($parentSource?->emergency_name ?? '') . ' · ' . ($parentSource?->emergency_phone ?? '')), 'icon' => 'alert'],
                ];
            @endphp

            <div style="background:white;border:1px solid #e5e7eb;border-radius:14px;padding:1.5rem 1.75rem;margin-bottom:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
                    <h3 style="font-size:1.2rem;font-weight:700;color:#111827;margin:0;">Enrollment Summary</h3>
                    <span style="font-size:0.85rem;font-weight:600;color:#059669;background:#dcfce7;padding:0.3rem 0.8rem;border-radius:20px;">{{ $readyApplications->count() }} Ready</span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                    @foreach ($parentDetails as $detail)
                        @if (trim($detail['value']) && trim($detail['value']) !== '·')
                            <div style="padding:0.5rem 0;">
                                <div style="font-size:0.8rem;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">{{ $detail['label'] }}</div>
                                <div style="font-size:1rem;color:#1f2937;font-weight:600;margin-top:0.2rem;">{{ $detail['value'] }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            @if ($draftApplications->count())
                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.82rem;color:#92400e;display:flex;align-items:center;gap:0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    {{ $draftApplications->count() }} incomplete {{ \Illuminate\Support\Str::plural('draft', $draftApplications->count()) }} will not be included.
                </div>
            @endif

            {{-- Applicant Cards --}}
            <div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:1.5rem;">
                @foreach ($readyApplications as $child)
                    @php
                        $childName = trim(($child->first_name ?? '') . ' ' . ($child->middle_name ?? '') . ' ' . ($child->last_name ?? ''));
                        $documents = collect([
                            '2x2 Photo' => $child->photo_2x2_url,
                            'Birth Cert' => $child->birth_cert_url,
                            'Report Card' => $child->report_card_url,
                            'Marriage' => $child->marriage_contract_url,
                            'Medical' => $child->medical_record_url,
                            'Affidavit' => $child->affidavit_url,
                            'Payment Proof' => $child->payment?->receipt_url,
                        ])->filter();
                        $missing = $incompleteApplications->get($child->id, []);
                    @endphp
                    <div style="background:white;border:1px solid #e5e7eb;border-radius:12px;padding:1.25rem 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);display:flex;gap:1.25rem;align-items:flex-start;">
                        {{-- Photo --}}
                        <div style="width:64px;height:64px;min-width:64px;border-radius:12px;overflow:hidden;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            @if ($child->photo_2x2_url)
                                <img src="{{ asset('storage/' . $child->photo_2x2_url) }}" alt="{{ $childName }}" style="width:64px;height:64px;object-fit:cover;">
                            @else
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#047857" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            @endif
                        </div>
                        {{-- Info --}}
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:1.1rem;color:#111827;text-transform:uppercase;">{{ $loop->iteration }}) {{ $childName }}</div>
                            <div style="font-size:0.9rem;color:#4b5563;margin-top:0.3rem;">
                                {{ strtoupper($child->student_type) }} · {{ $child->grade_level }} · {{ $child->learning_mode }}
                                @if ($child->timezone) · {{ $child->timezone }} @endif
                            </div>
                            <div style="font-size:0.85rem;color:#6b7280;margin-top:0.4rem;">
                                Documents: {{ $documents->keys()->join(', ') ?: 'None' }}
                            </div>
                            @if ((float) $child->discount_percentage > 0)
                                <div style="font-size:0.85rem;color:#059669;margin-top:0.3rem;font-weight:600;">
                                    Sibling Discount: {{ rtrim(rtrim(number_format((float) $child->discount_percentage, 2), '0'), '.') }}%
                                </div>
                            @endif
                            @if ($missing)
                                <div style="font-size:0.85rem;color:#dc2626;margin-top:0.3rem;font-weight:600;">
                                    ⚠ Missing: {{ implode(', ', $missing) }}
                                </div>
                            @endif
                        </div>
                        {{-- Action --}}
                        <a href="{{ route('enrollment.form.child', $child) }}" style="padding:0.5rem 1rem;background:#f3f4f6;color:#374151;font-size:0.9rem;font-weight:600;border-radius:8px;text-decoration:none;white-space:nowrap;flex-shrink:0;">Review</a>
                    </div>
                @endforeach
            </div>

            {{-- Submit Section --}}
            <div style="background:linear-gradient(135deg,#f0fdf4,#ffffff);border:1px solid #bbf7d0;border-radius:14px;padding:1.5rem 1.75rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div>
                    <div style="font-size:1.1rem;font-weight:700;color:#166534;">Confirm Final Submission</div>
                    <div style="font-size:0.9rem;color:#4b5563;margin-top:0.25rem;">All applications will be locked and sent for admin review.</div>
                </div>
                <form method="POST" action="{{ route('enrollment.finalize.confirm') }}">
                    @csrf
                    <button type="submit" style="padding:0.75rem 2rem;background:#059669;color:white;font-size:1rem;font-weight:700;border-radius:10px;border:none;cursor:pointer;white-space:nowrap;transition:background 0.15s;"
                        @if($incompleteApplications->isNotEmpty()) disabled style="opacity:.5;cursor:not-allowed;padding:0.75rem 2rem;background:#059669;color:white;font-size:1rem;font-weight:700;border-radius:10px;border:none;" @endif>
                        Submit Enrollment
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>

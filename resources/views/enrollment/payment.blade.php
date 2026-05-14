<x-guest-layout>
<div x-data="{ loaded: false, method: 'gcash' }" x-init="setTimeout(() => loaded = true, 800)" class="enrollment-page">

    {{-- Initial loading --}}
    <div x-show="!loaded" x-cloak
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="initial-loading-screen">
        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="initial-loading-logo">
        <div class="three-dots-loading">
            <div class="dot"></div><div class="dot"></div><div class="dot"></div>
        </div>
    </div>

    <div x-show="loaded" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        {{-- Header --}}
        <div class="enrollment-header">
            <div class="enrollment-header-content">
                <div class="enrollment-header-left">
                    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="enrollment-header-logo">
                    <div class="enrollment-header-text">
                        <div class="arabic">المدرسة المنورة الإسلامية</div>
                        <div class="school">Al Munawwara Islamic School</div>
                    </div>
                </div>
                <div class="enrollment-header-right">
                    <h1>Enrollment Fee Payment</h1>
                    <div class="school-year">School Year 2026–2027</div>
                </div>
            </div>
        </div>

        {{-- Main --}}
        <div class="enrollment-main">
            <div class="enrollment-form-container" style="max-width:680px;position:relative;">

                {{-- X close --}}
                <a href="{{ route('enrollment.dashboard') }}"
                   style="position:absolute;top:1.5rem;right:1.5rem;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:#f3f4f6;color:#6b7280;text-decoration:none;border:1px solid #e5e7eb;"
                   onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>

                {{-- Form header --}}
                <form method="POST" action="{{ route('enrollment.payment.submit') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="method" :value="method">

                <div class="enrollment-form-header">
                    <h2>Payment for Enrollment Fee</h2>
                    <p>Review your fees and choose a payment method</p>
                </div>

                {{-- Applicant info --}}
                <div style="display:flex;align-items:center;gap:0.75rem;padding:1rem 1.25rem;background:#f0fdf4;border:1px solid #d1fae5;border-radius:10px;margin-bottom:1.75rem;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:0.9375rem;color:#111827;">
                            {{ $applicant->last_name }}, {{ $applicant->first_name }} {{ $applicant->middle_name }}
                        </div>
                        <div style="font-size:0.8125rem;color:#6b7280;">
                            {{ $applicant->grade_level }} &nbsp;·&nbsp; {{ $applicant->student_type }} Student &nbsp;·&nbsp; SY {{ $applicant->school_year }}
                        </div>
                    </div>
                </div>

                {{-- Fee breakdown --}}
                @php
                    $total = 4000.00;
                @endphp
                <div style="margin-bottom:1.75rem;">
                    <div style="font-size:0.6875rem;font-weight:700;letter-spacing:0.08em;color:#9ca3af;margin-bottom:0.75rem;">FEE BREAKDOWN</div>
                    <div style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem 1.25rem;background:#f0fdf4;border-bottom:2px solid #d1fae5;">
                            <div>
                                <div style="font-size:1rem;font-weight:700;color:#065f46;">Enrollment Fee</div>
                                <div style="font-size:0.8125rem;color:#6b7280;margin-top:0.2rem;">Non-refundable</div>
                            </div>
                            <span style="font-size:1.25rem;font-weight:800;color:#059669;">₱{{ number_format($total, 2) }}</span>
                        </div>
                        <div style="padding:0.75rem 1.25rem;background:#fffbeb;display:flex;align-items:center;gap:0.5rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span style="font-size:0.8125rem;color:#92400e;">This fee is non-refundable once paid.</span>
                        </div>
                    </div>
                </div>

                {{-- Payment method selector --}}
                <div style="margin-bottom:1.75rem;">
                    <div style="font-size:0.6875rem;font-weight:700;letter-spacing:0.08em;color:#9ca3af;margin-bottom:0.75rem;">SELECT PAYMENT METHOD</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;">

                        <button type="button" @click="method = 'gcash'"
                            :style="method === 'gcash' ? 'border:2px solid #059669;background:#f0fdf4;' : 'border:2px solid #e5e7eb;background:white;'"
                            style="padding:0;border-radius:10px;cursor:pointer;font-family:inherit;display:flex;flex-direction:column;align-items:center;overflow:hidden;transition:all 0.15s;">
                            <div style="width:100%;height:72px;display:flex;align-items:center;justify-content:center;padding:0.75rem;background:white;">
                                <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash" style="max-height:100%;max-width:100%;object-fit:contain;">
                            </div>
                            <div style="width:100%;padding:0.5rem;text-align:center;border-top:1px solid #f3f4f6;">
                                <span style="font-size:0.8125rem;font-weight:600;color:#374151;">GCash</span>
                            </div>
                        </button>

                        <button type="button" @click="method = 'maya'"
                            :style="method === 'maya' ? 'border:2px solid #059669;background:#f0fdf4;' : 'border:2px solid #e5e7eb;background:white;'"
                            style="padding:0;border-radius:10px;cursor:pointer;font-family:inherit;display:flex;flex-direction:column;align-items:center;overflow:hidden;transition:all 0.15s;">
                            <div style="width:100%;height:72px;display:flex;align-items:center;justify-content:center;padding:0.75rem;background:white;">
                                <img src="{{ asset('images/mode_of_payments/MAYA.png') }}" alt="Maya" style="max-height:100%;max-width:100%;object-fit:contain;">
                            </div>
                            <div style="width:100%;padding:0.5rem;text-align:center;border-top:1px solid #f3f4f6;">
                                <span style="font-size:0.8125rem;font-weight:600;color:#374151;">Maya</span>
                            </div>
                        </button>

                        <button type="button" @click="method = 'bdo'"
                            :style="method === 'bdo' ? 'border:2px solid #059669;background:#f0fdf4;' : 'border:2px solid #e5e7eb;background:white;'"
                            style="padding:0;border-radius:10px;cursor:pointer;font-family:inherit;display:flex;flex-direction:column;align-items:center;overflow:hidden;transition:all 0.15s;">
                            <div style="width:100%;height:72px;display:flex;align-items:center;justify-content:center;padding:0.75rem;background:white;">
                                <img src="{{ asset('images/mode_of_payments/BDO.png') }}" alt="BDO" style="max-height:100%;max-width:100%;object-fit:contain;">
                            </div>
                            <div style="width:100%;padding:0.5rem;text-align:center;border-top:1px solid #f3f4f6;">
                                <span style="font-size:0.8125rem;font-weight:600;color:#374151;">BDO Bank</span>
                            </div>
                        </button>

                    </div>
                </div>

                {{-- ── GCash Panel ── --}}
                <div x-show="method === 'gcash'" x-transition>
                    <div style="font-size:0.6875rem;font-weight:700;letter-spacing:0.08em;color:#9ca3af;margin-bottom:0.75rem;">HOW TO PAY VIA GCASH</div>

                    {{-- Account info --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;background:#f0fdf4;border:1px solid #d1fae5;border-radius:10px;margin-bottom:1rem;">
                        <div>
                            <div style="font-size:0.75rem;color:#6b7280;margin-bottom:0.2rem;">Send to GCash Number</div>
                            <div style="font-size:1.125rem;font-weight:800;color:#059669;letter-spacing:0.05em;">0917-123-4567</div>
                            <div style="font-size:0.8125rem;color:#374151;font-weight:600;">AMIS Finance Office</div>
                        </div>
                        <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash" style="height:36px;object-fit:contain;opacity:0.85;">
                    </div>

                    {{-- Steps --}}
                    @php $gcashSteps = [
                        ['Open GCash App', 'Launch the GCash app on your mobile phone and log in to your account.'],
                        ['Tap "Send Money"', 'On the home screen, tap the "Send Money" button.'],
                        ['Enter the number', 'Type in 0917-123-4567 as the recipient number.'],
                        ['Enter the amount', 'Input ₱4,000.00 as the amount to send.'],
                        ['Add a note', 'In the message/note field, type your full name and grade level (e.g., Juan Dela Cruz – Grade 7).'],
                        ['Confirm & send', 'Review the details and tap "Send". Take a screenshot of the confirmation screen.'],
                        ['Upload receipt below', 'Upload your GCash confirmation screenshot in the receipt field below.'],
                    ]; @endphp
                    <div style="display:flex;flex-direction:column;gap:0.625rem;margin-bottom:1.25rem;">
                        @foreach ($gcashSteps as $i => $s)
                        <div style="display:flex;align-items:flex-start;gap:0.875rem;padding:0.875rem 1rem;background:white;border:1px solid #e5e7eb;border-radius:8px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:#059669;color:white;display:flex;align-items:center;justify-content:center;font-size:0.8125rem;font-weight:700;flex-shrink:0;">{{ $i + 1 }}</div>
                            <div>
                                <div style="font-weight:700;font-size:0.9rem;color:#111827;margin-bottom:0.2rem;">{{ $s[0] }}</div>
                                <div style="font-size:0.8125rem;color:#6b7280;line-height:1.5;">{{ $s[1] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── Maya Panel ── --}}
                <div x-show="method === 'maya'" x-transition>
                    <div style="font-size:0.6875rem;font-weight:700;letter-spacing:0.08em;color:#9ca3af;margin-bottom:0.75rem;">HOW TO PAY VIA MAYA</div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;background:#f0fdf4;border:1px solid #d1fae5;border-radius:10px;margin-bottom:1rem;">
                        <div>
                            <div style="font-size:0.75rem;color:#6b7280;margin-bottom:0.2rem;">Send to Maya Number</div>
                            <div style="font-size:1.125rem;font-weight:800;color:#059669;letter-spacing:0.05em;">0998-765-4321</div>
                            <div style="font-size:0.8125rem;color:#374151;font-weight:600;">AMIS Finance Office</div>
                        </div>
                        <img src="{{ asset('images/mode_of_payments/MAYA.png') }}" alt="Maya" style="height:36px;object-fit:contain;opacity:0.85;">
                    </div>

                    @php $mayaSteps = [
                        ['Open Maya App', 'Launch the Maya app on your mobile phone and log in to your account.'],
                        ['Tap "Send Money"', 'On the home screen, tap the "Send Money" or "Pay" button.'],
                        ['Enter the number', 'Type in 0998-765-4321 as the recipient number.'],
                        ['Enter the amount', 'Input ₱4,000.00 as the amount to send.'],
                        ['Add a note', 'In the message/note field, type your full name and grade level (e.g., Juan Dela Cruz – Grade 7).'],
                        ['Confirm & send', 'Review the details and tap "Send". Take a screenshot of the confirmation screen.'],
                        ['Upload receipt below', 'Upload your Maya confirmation screenshot in the receipt field below.'],
                    ]; @endphp
                    <div style="display:flex;flex-direction:column;gap:0.625rem;margin-bottom:1.25rem;">
                        @foreach ($mayaSteps as $i => $s)
                        <div style="display:flex;align-items:flex-start;gap:0.875rem;padding:0.875rem 1rem;background:white;border:1px solid #e5e7eb;border-radius:8px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:#059669;color:white;display:flex;align-items:center;justify-content:center;font-size:0.8125rem;font-weight:700;flex-shrink:0;">{{ $i + 1 }}</div>
                            <div>
                                <div style="font-weight:700;font-size:0.9rem;color:#111827;margin-bottom:0.2rem;">{{ $s[0] }}</div>
                                <div style="font-size:0.8125rem;color:#6b7280;line-height:1.5;">{{ $s[1] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── BDO Panel ── --}}
                <div x-show="method === 'bdo'" x-transition>
                    <div style="font-size:0.6875rem;font-weight:700;letter-spacing:0.08em;color:#9ca3af;margin-bottom:0.75rem;">HOW TO PAY VIA BDO BANK TRANSFER</div>

                    <div style="padding:1rem 1.25rem;background:#f0fdf4;border:1px solid #d1fae5;border-radius:10px;margin-bottom:1rem;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.875rem;">
                            <span style="font-size:0.75rem;color:#6b7280;">BDO Account Details</span>
                            <img src="{{ asset('images/mode_of_payments/BDO.png') }}" alt="BDO" style="height:28px;object-fit:contain;opacity:0.85;">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.625rem;">
                            <div>
                                <div style="font-size:0.75rem;color:#9ca3af;">Account Name</div>
                                <div style="font-size:0.875rem;font-weight:700;color:#111827;">Al Munawwara Islamic School</div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem;color:#9ca3af;">Account Number</div>
                                <div style="font-size:0.875rem;font-weight:700;color:#059669;letter-spacing:0.05em;">0012-3456-7890</div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem;color:#9ca3af;">Bank</div>
                                <div style="font-size:0.875rem;font-weight:700;color:#111827;">BDO Unibank</div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem;color:#9ca3af;">Amount</div>
                                <div style="font-size:0.875rem;font-weight:700;color:#059669;">₱{{ number_format($total, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    @php $bdoSteps = [
                        ['Log in to BDO Online / App', 'Open BDO Online Banking or the BDO app and log in to your account.'],
                        ['Go to "Fund Transfer"', 'Select "Fund Transfer" or "Transfer Money" from the main menu.'],
                        ['Select "Other BDO Account"', 'Choose to transfer to another BDO account.'],
                        ['Enter account number', 'Type in account number 0012-3456-7890 (Al Munawwara Islamic School).'],
                        ['Enter the amount', 'Input ₱4,000.00 as the transfer amount.'],
                        ['Add remarks', 'In the remarks field, type your full name and grade level (e.g., Juan Dela Cruz – Grade 7).'],
                        ['Confirm transfer', 'Review all details carefully and confirm the transaction. Save or screenshot the confirmation.'],
                        ['Upload proof below', 'Upload your transfer confirmation screenshot or deposit slip in the receipt field below.'],
                    ]; @endphp
                    <div style="display:flex;flex-direction:column;gap:0.625rem;margin-bottom:1.25rem;">
                        @foreach ($bdoSteps as $i => $s)
                        <div style="display:flex;align-items:flex-start;gap:0.875rem;padding:0.875rem 1rem;background:white;border:1px solid #e5e7eb;border-radius:8px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:#059669;color:white;display:flex;align-items:center;justify-content:center;font-size:0.8125rem;font-weight:700;flex-shrink:0;">{{ $i + 1 }}</div>
                            <div>
                                <div style="font-weight:700;font-size:0.9rem;color:#111827;margin-bottom:0.2rem;">{{ $s[0] }}</div>
                                <div style="font-size:0.8125rem;color:#6b7280;line-height:1.5;">{{ $s[1] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Receipt upload --}}
                <div x-data="{ preview: null, fileName: '', fileSize: '' }" style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.875rem;font-weight:600;color:#374151;margin-bottom:0.5rem;">
                        Upload Payment Receipt <span class="required">*</span>
                    </label>

                    {{-- Upload area (hidden when preview shown) --}}
                    <div x-show="!preview">
                        <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.5rem;padding:1.5rem;border:2px dashed #d1d5db;border-radius:10px;background:#fafafa;cursor:pointer;transition:border-color 0.15s,background 0.15s;"
                               onmouseover="this.style.borderColor='#059669';this.style.background='#f0fdf4'"
                               onmouseout="this.style.borderColor='#d1d5db';this.style.background='#fafafa'">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                                <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                                <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
                            </svg>
                            <span style="font-size:0.875rem;font-weight:600;color:#059669;">Click to upload</span>
                            <span style="font-size:0.75rem;color:#9ca3af;">JPG, PNG or PDF — max 5MB</span>
                            <input type="file" name="receipt" accept="image/*,.pdf" style="display:none;"
                                @change="
                                    const file = $event.target.files[0];
                                    if (!file) return;
                                    fileName = file.name;
                                    fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                                    if (file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = e => preview = e.target.result;
                                        reader.readAsDataURL(file);
                                    } else {
                                        preview = 'pdf';
                                    }
                                ">
                        </label>
                    </div>

                    {{-- Preview --}}
                    <div x-show="preview" style="border:1.5px solid #d1fae5;border-radius:10px;overflow:hidden;background:#f0fdf4;">
                        {{-- Image preview --}}
                        <template x-if="preview && preview !== 'pdf'">
                            <img :src="preview" alt="Receipt preview"
                                 style="width:100%;max-height:280px;object-fit:contain;background:white;display:block;">
                        </template>
                        {{-- PDF indicator --}}
                        <template x-if="preview === 'pdf'">
                            <div style="display:flex;align-items:center;justify-content:center;padding:2rem;background:white;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                        </template>
                        {{-- File info + remove --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 1rem;gap:0.75rem;">
                            <div style="min-width:0;">
                                <div style="font-size:0.875rem;font-weight:600;color:#065f46;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="fileName"></div>
                                <div style="font-size:0.75rem;color:#6b7280;" x-text="fileSize"></div>
                            </div>
                            <button type="button" @click="preview = null; fileName = ''; fileSize = ''; $el.closest('[x-data]').querySelector('input[type=file]').value = '';"
                                style="display:inline-flex;align-items:center;gap:0.25rem;padding:0.375rem 0.75rem;background:white;border:1px solid #fca5a5;border-radius:6px;color:#dc2626;font-size:0.75rem;font-weight:500;cursor:pointer;white-space:nowrap;font-family:inherit;flex-shrink:0;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:0.875rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Confirm Payment Submission
                </button>
                <p style="text-align:center;font-size:0.8125rem;color:#9ca3af;margin-top:0.75rem;">
                    Payment will be verified by the Finance Office within 1–2 business days.
                </p>

                </form>

            </div>
        </div>

        <div class="enrollment-footer">
            &copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.
        </div>
    </div>
</div>
</x-guest-layout>

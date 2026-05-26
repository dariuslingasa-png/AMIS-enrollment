<x-guest-layout>
<div x-data="{ loaded: false, method: 'gcash_maya' }" x-init="setTimeout(() => loaded = true, 800)" class="enrollment-page">

    {{-- Initial loading --}}
    <x-page-loader
        x-show="!loaded"
        x-cloak
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    />

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
            <div class="enrollment-form-container payment-container">

                {{-- Close button --}}
                <a href="{{ route('enrollment.dashboard') }}" class="payment-close-btn" aria-label="Close">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </a>

                <form method="POST" action="{{ route('enrollment.payment.submit') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="method" :value="method">
                <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">

                {{-- Form header --}}
                <div class="enrollment-form-header">
                    <h2>Payment for Enrollment Fee</h2>
                    <p>Review the official payment channels and upload your proof of payment.</p>
                </div>

                @if ($errors->any())
                    <div class="enrollment-error">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                {{-- Applicant info card --}}
                <div class="payment-applicant-card">
                    <div class="payment-applicant-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="payment-applicant-name">{{ $applicant->last_name }}, {{ $applicant->first_name }} {{ $applicant->middle_name }}</div>
                        <div class="payment-applicant-meta">{{ $applicant->grade_level }} · {{ strtoupper($applicant->student_type) }} Student · SY {{ $applicant->school_year }}</div>
                    </div>
                </div>

                @php
                    $invoiceApplicants = collect($invoiceApplicants ?? [$applicant])->values();
                    $perChildAmount = 4000.00;
                    $invoiceTotal = $invoiceApplicants->count() * $perChildAmount;
                    $total = (float) old('amount', $payment->amount ?? $invoiceTotal);
                    $invoiceRootId = $applicant->family_application_id ?: $applicant->id;
                    $invoiceNumber = 'INV-ENR-' . str_pad((string) $invoiceRootId, 5, '0', STR_PAD_LEFT);
                    $paymentStatus = strtolower((string) ($payment->status ?? 'pending'));
                    $isPaid = $paymentStatus === 'verified';
                    $paymentMethodLabel = strtoupper(str_replace('_', '/', (string) ($payment->method ?? 'gcash_maya')));
                    $learningModeLabel = function ($mode) {
                        $normalized = strtolower(trim((string) $mode));

                        return match ($normalized) {
                            'face_to_face', 'face-to-face', 'face to face', 'f2f' => 'FACE TO FACE',
                            'flexible_1st_shift', 'flexible learning - 1st shift', 'flexible 1st shift', '1st shift' => 'FLEXIBLE LEARNING - 1ST SHIFT',
                            'flexible_2nd_shift', 'flexible learning - 2nd shift', 'flexible 2nd shift', '2nd shift' => 'FLEXIBLE LEARNING - 2ND SHIFT',
                            default => $mode ? strtoupper((string) $mode) : 'LEARNING MODE PENDING',
                        };
                    };
                @endphp

                {{-- Official payment channels --}}
                <div class="payment-section">
                    {{-- Payment reminder notice --}}
                    <div class="payment-reminder-notice">
                        <div class="payment-reminder-header">
                            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="payment-reminder-logo">
                            <div>
                                <div class="payment-reminder-title">Monthly Payment Reminder</div>
                                <div class="payment-reminder-greeting">Assalaamu alaykum wa rahmatullahi wa barakatuh.</div>
                            </div>
                        </div>
                        <p class="payment-reminder-body">
                            This is a gentle reminder that the monthly payment for Al Munawwara Islamic School is <strong style="color:#dc2626;">DUE SOON</strong>.
                        </p>
                        <p class="payment-reminder-body payment-reminder-small">
                            As a non-profit and non-governmental institution, your timely payment greatly helps us meet essential needs such as staff salaries, school maintenance, and utility expenses like electricity, water, and internet. Your continued support allows us to sustain a quality educational environment for your child and the rest of our learners.
                        </p>
                        <p class="payment-reminder-disregard">*****Please disregard this reminder if payment has already been made.*****</p>
                    </div>

                    <div class="payment-channels-header">
                        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="payment-channels-logo">
                        <div>
                            <div class="payment-channels-title">Official Payment Channels</div>
                            <div class="payment-channels-subtitle">Only BDO, GCash, and Maya are recognized as official modes of payment.</div>
                        </div>
                    </div>

                    <div class="payment-channels-grid">
                        {{-- BDO --}}
                        <div class="payment-channel-card">
                            <div class="payment-channel-head">
                                <img src="{{ asset('images/mode_of_payments/BDO.png') }}" alt="BDO" class="payment-channel-img">
                                <strong>BDO Bank Transfer / Deposit</strong>
                            </div>
                            @php
                                $bdoAccounts = [
                                    ['BDO Savings Account', '010478011996', 'AL MUNAWWARA ISLAMIC SCHOOL Inc.'],
                                    ['BDO Current Account', '010478008782', 'CABEL B. NURHASAN'],
                                    ['BDO Savings Account', '010470022817', 'CABEL NURHASAN'],
                                    ['BDO Savings Account', '010470099925', 'WARDAH D. PINDATON or JAMELLA P. MOHAMAD'],
                                    ['BDO Savings Account', '010470105712', 'JAMELLA P. MOHAMAD or WARDAH P. PINDATON'],
                                ];
                            @endphp
                            <div class="payment-account-list">
                                @foreach ($bdoAccounts as [$type, $number, $name])
                                    <div class="payment-account-item">
                                        <div class="payment-account-type">{{ $type }}</div>
                                        <div class="payment-account-number">{{ $number }}</div>
                                        <div class="payment-account-name">{{ $name }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="payment-channel-meta">
                                <strong>Swift code:</strong> BNORPHMM<br>
                                <strong>Branch:</strong> WOODLANE DIVERSION ROAD - DAVAO CITY
                            </div>
                        </div>

                        {{-- GCash / Maya --}}
                        <div class="payment-channel-card">
                            <div class="payment-channel-head">
                                <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash" class="payment-channel-img">
                                <img src="{{ asset('images/mode_of_payments/MAYA.png') }}" alt="Maya" class="payment-channel-img">
                                <strong>GCash / Maya Payment Center</strong>
                            </div>
                            <div class="payment-account-list">
                                <div class="payment-account-item">
                                    <div class="payment-account-type">GCash / Maya Number</div>
                                    <div class="payment-account-number">(+63) 927 299 1833</div>
                                </div>
                                <div class="payment-account-item">
                                    <div class="payment-account-type">GCash / Maya Number</div>
                                    <div class="payment-account-number">(+63) 995 233 9423</div>
                                </div>
                                <div class="payment-account-item">
                                    <div class="payment-account-type">Account Name</div>
                                    <div class="payment-account-name">CABEL B. NURHASAN</div>
                                </div>
                            </div>
                            <div class="payment-channel-reminder">
                                <strong>Important:</strong> Only BDO, GCash, and Maya are recognized as official modes of payment. Use of MoneyGram or cash pick-up is strongly discouraged and will not be accepted or deducted from school fees.
                            </div>
                        </div>
                    </div>

                    <div class="payment-important-notice">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Use of MoneyGram or cash pick-up is strongly discouraged and will not be accepted or deducted from school fees.</span>
                    </div>
                </div>

                {{-- Payment method selector --}}
                <div class="payment-section">
                    <div class="payment-section-label">SELECT PAYMENT METHOD</div>
                    <div class="payment-method-selector">
                        <button type="button" @click="method = 'gcash_maya'"
                            :class="method === 'gcash_maya' ? 'is-active' : ''"
                            class="payment-method-btn">
                            <div class="payment-method-img-wrap" style="gap:0.9rem;">
                                <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash">
                                <img src="{{ asset('images/mode_of_payments/MAYA.png') }}" alt="Maya" style="max-width:45%;">
                            </div>
                            <span>GCash/Maya</span>
                        </button>
                        <button type="button" @click="method = 'bdo'"
                            :class="method === 'bdo' ? 'is-active' : ''"
                            class="payment-method-btn">
                            <div class="payment-method-img-wrap">
                                <img src="{{ asset('images/mode_of_payments/BDO.png') }}" alt="BDO">
                            </div>
                            <span>BDO Bank</span>
                        </button>
                    </div>
                </div>

                {{-- Invoice summary near payment controls --}}
                <div class="payment-section">
                    <div class="payment-section-label">INVOICE</div>
                    <div class="payment-invoice-card">
                        <div class="payment-invoice-head">
                            <div>
                                <span>Invoice #</span>
                                <strong>{{ $invoiceNumber }}</strong>
                            </div>
                            <div>
                                <span>Status</span>
                                <strong>{{ $isPaid ? 'PAID' : 'PENDING PAYMENT' }}</strong>
                            </div>
                            <div>
                                <span>Applications Covered</span>
                                <strong>{{ $invoiceApplicants->count() }} {{ \Illuminate\Support\Str::plural('Application', $invoiceApplicants->count()) }}</strong>
                            </div>
                        </div>
                        <div class="payment-invoice-table">
                            <div class="payment-invoice-row payment-invoice-row-head">
                                <span>Application ID</span>
                                <span>Child</span>
                                <span>Grade</span>
                                <span>Learning Mode</span>
                                <span>Amount</span>
                            </div>
                            @foreach ($invoiceApplicants as $invoiceChild)
                                <div class="payment-invoice-row">
                                    <span>APP-{{ str_pad((string) $invoiceChild->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span>{{ strtoupper($invoiceChild->full_name ?: trim(($invoiceChild->first_name ?? '') . ' ' . ($invoiceChild->middle_name ?? '') . ' ' . ($invoiceChild->last_name ?? ''))) }}</span>
                                    <span>{{ strtoupper($invoiceChild->grade_level ?? 'Grade pending') }}</span>
                                    <span>{{ $learningModeLabel($invoiceChild->learning_mode ?? null) }}</span>
                                    <span>PHP {{ number_format($perChildAmount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="payment-invoice-total">
                            <span>Total Amount To Pay</span>
                            <strong>PHP {{ number_format($invoiceTotal, 2) }}</strong>
                        </div>
                        @if ($payment && filled($payment->receipt_url))
                            <div class="payment-invoice-payment-line">
                                <span>{{ $invoiceNumber }}</span>
                                <span>Amount PHP {{ number_format((float) ($payment->amount ?? 0), 2) }}</span>
                                <span>{{ $paymentMethodLabel }}</span>
                                <strong>{{ $isPaid ? 'PAID' : strtoupper($paymentStatus ?: 'PENDING') }}</strong>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Amount --}}
                <div class="payment-section">
                    <label class="payment-field-label">Amount Paid <span class="required">*</span></label>
                    <input type="number" name="amount" value="{{ old('amount', $payment->amount ?? $invoiceTotal) }}"
                        min="1" step="0.01" required
                        placeholder="Enter the amount paid"
                        class="plain-input">
                </div>

                {{-- Reference number --}}
                <div class="payment-section">
                    <label class="payment-field-label">Reference Number / Transaction ID</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no', $payment->reference_no ?? '') }}"
                        placeholder="Enter the transaction reference number if available"
                        class="plain-input">
                </div>

                {{-- Receipt upload --}}
                <div x-data="{ preview: null, fileName: '', fileSize: '' }" class="payment-section">
                    <label class="payment-field-label">Upload Payment Receipt <span class="required">*</span></label>

                    <div x-show="!preview">
                        <label class="payment-upload-area">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                                <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                                <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
                            </svg>
                            <span class="payment-upload-text">Click to upload</span>
                            <span class="payment-upload-hint">JPG, PNG or PDF — max 5MB</span>
                            <input type="file" name="receipt" accept="image/*,.pdf" style="display:none;" required
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
                    <div x-show="preview" class="payment-preview-card">
                        <template x-if="preview && preview !== 'pdf'">
                            <img :src="preview" alt="Receipt preview" class="payment-preview-img">
                        </template>
                        <template x-if="preview === 'pdf'">
                            <div class="payment-preview-pdf">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                        </template>
                        <div class="payment-preview-info">
                            <div>
                                <div class="payment-preview-name" x-text="fileName"></div>
                                <div class="payment-preview-size" x-text="fileSize"></div>
                            </div>
                            <button type="button" @click="preview = null; fileName = ''; fileSize = ''; $el.closest('[x-data]').querySelector('input[type=file]').value = '';"
                                class="payment-preview-remove">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-primary payment-submit-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Confirm Payment Submission
                </button>
                <p class="payment-submit-note">Payment will be verified by the Finance Office within 1–2 business days.</p>

                </form>
            </div>
        </div>

        <div class="enrollment-footer">
            &copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.
        </div>
    </div>
</div>
</x-guest-layout>

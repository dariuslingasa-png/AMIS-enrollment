<x-guest-layout>
<div x-data="{ loaded: false, method: 'gcash' }" x-init="setTimeout(() => loaded = true, 800)" class="enrollment-page">

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

                {{-- Fee breakdown --}}
                @php $total = 4000.00; @endphp
                <div class="payment-section">
                    <div class="payment-section-label">FEE BREAKDOWN</div>
                    <div class="payment-fee-card">
                        <div class="payment-fee-main">
                            <div>
                                <div class="payment-fee-title">Enrollment Fee</div>
                                <div class="payment-fee-subtitle">Non-refundable</div>
                            </div>
                            <span class="payment-fee-amount">₱{{ number_format($total, 2) }}</span>
                        </div>
                        <div class="payment-fee-notice">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>This fee is non-refundable once paid.</span>
                        </div>
                    </div>
                </div>

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
                            <div class="payment-channels-subtitle">Only BDO and GCash are recognized as official modes of payment.</div>
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

                        {{-- GCash --}}
                        <div class="payment-channel-card">
                            <div class="payment-channel-head">
                                <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash" class="payment-channel-img">
                                <strong>GCash Payment Center</strong>
                            </div>
                            <div class="payment-account-list">
                                <div class="payment-account-item">
                                    <div class="payment-account-type">GCash Number</div>
                                    <div class="payment-account-number">(+63) 927 299 1833</div>
                                </div>
                                <div class="payment-account-item">
                                    <div class="payment-account-type">GCash Number</div>
                                    <div class="payment-account-number">(+63) 995 233 9423</div>
                                </div>
                                <div class="payment-account-item">
                                    <div class="payment-account-type">Account Name</div>
                                    <div class="payment-account-name">CABEL B. NURHASAN</div>
                                </div>
                            </div>
                            <div class="payment-channel-reminder">
                                <strong>Important:</strong> Only BDO and GCash are recognized as official modes of payment. Use of MoneyGram or cash pick-up is strongly discouraged and will not be accepted or deducted from school fees.
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
                        <button type="button" @click="method = 'gcash'"
                            :class="method === 'gcash' ? 'is-active' : ''"
                            class="payment-method-btn">
                            <div class="payment-method-img-wrap">
                                <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash">
                            </div>
                            <span>GCash</span>
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

                {{-- GCash Steps --}}
                <div x-show="method === 'gcash'" x-transition class="payment-section">
                    <div class="payment-section-label">HOW TO PAY VIA GCASH</div>
                    <div class="payment-info-banner">
                        <div>
                            <div class="payment-info-label">Send to GCash Number</div>
                            <div class="payment-info-number">(+63) 927 299 1833</div>
                            <div class="payment-info-number payment-info-number-alt">(+63) 995 233 9423</div>
                            <div class="payment-info-name">CABEL B. NURHASAN</div>
                        </div>
                        <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash" class="payment-info-logo">
                    </div>

                    @php $gcashSteps = [
                        ['Open GCash App', 'Launch the GCash app on your mobile phone and log in to your account.'],
                        ['Tap "Send Money"', 'On the home screen, tap the "Send Money" button.'],
                        ['Enter the number', 'Type in (+63) 927 299 1833 or (+63) 995 233 9423 as the recipient number.'],
                        ['Enter the amount', 'Input ₱4,000.00 as the amount to send.'],
                        ['Add a note', 'In the message/note field, type your full name and grade level (e.g., Juan Dela Cruz – Grade 7).'],
                        ['Confirm & send', 'Review the details and tap "Send". Take a screenshot of the confirmation screen.'],
                        ['Upload receipt below', 'Upload your GCash confirmation screenshot in the receipt field below.'],
                    ]; @endphp
                    <div class="payment-steps">
                        @foreach ($gcashSteps as $i => $s)
                        <div class="payment-step">
                            <div class="payment-step-num">{{ $i + 1 }}</div>
                            <div>
                                <div class="payment-step-title">{{ $s[0] }}</div>
                                <div class="payment-step-desc">{{ $s[1] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- BDO Steps --}}
                <div x-show="method === 'bdo'" x-transition class="payment-section">
                    <div class="payment-section-label">HOW TO PAY VIA BDO BANK TRANSFER</div>
                    <div class="payment-info-banner">
                        <div>
                            <div class="payment-info-label">BDO Account Details</div>
                            @php
                                $bdoPanelAccounts = [
                                    ['BDO Savings Account', '010478011996', 'AL MUNAWWARA ISLAMIC SCHOOL Inc.'],
                                    ['BDO Current Account', '010478008782', 'CABEL B. NURHASAN'],
                                    ['BDO Savings Account', '010470022817', 'CABEL NURHASAN'],
                                    ['BDO Savings Account', '010470099925', 'WARDAH D. PINDATON or JAMELLA P. MOHAMAD'],
                                    ['BDO Savings Account', '010470105712', 'JAMELLA P. MOHAMAD or WARDAH P. PINDATON'],
                                ];
                            @endphp
                            <div class="payment-bdo-accounts">
                                @foreach ($bdoPanelAccounts as [$type, $number, $name])
                                    <div class="payment-bdo-item">
                                        <div class="payment-account-type">{{ $type }}</div>
                                        <div class="payment-account-number">{{ $number }}</div>
                                        <div class="payment-account-name">{{ $name }}</div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="payment-channel-meta">
                                <strong>Swift code:</strong> BNORPHMM · <strong>Branch:</strong> WOODLANE DIVERSION ROAD - DAVAO CITY · <strong>Amount:</strong> ₱{{ number_format($total, 2) }}
                            </div>
                        </div>
                        <img src="{{ asset('images/mode_of_payments/BDO.png') }}" alt="BDO" class="payment-info-logo">
                    </div>

                    @php $bdoSteps = [
                        ['Log in to BDO Online / App', 'Open BDO Online Banking or the BDO app and log in to your account.'],
                        ['Go to "Fund Transfer"', 'Select "Fund Transfer" or "Transfer Money" from the main menu.'],
                        ['Select "Other BDO Account"', 'Choose to transfer to another BDO account.'],
                        ['Enter account number', 'Use any official BDO account listed above and verify the account name before confirming.'],
                        ['Enter the amount', 'Input ₱4,000.00 as the transfer amount.'],
                        ['Add remarks', 'In the remarks field, type your full name and grade level (e.g., Juan Dela Cruz – Grade 7).'],
                        ['Confirm transfer', 'Review all details carefully and confirm the transaction. Save or screenshot the confirmation.'],
                        ['Upload proof below', 'Upload your transfer confirmation screenshot or deposit slip in the receipt field below.'],
                    ]; @endphp
                    <div class="payment-steps">
                        @foreach ($bdoSteps as $i => $s)
                        <div class="payment-step">
                            <div class="payment-step-num">{{ $i + 1 }}</div>
                            <div>
                                <div class="payment-step-title">{{ $s[0] }}</div>
                                <div class="payment-step-desc">{{ $s[1] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
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

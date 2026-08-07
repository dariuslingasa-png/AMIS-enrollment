<x-guest-layout>
@php
    $invoiceApplicants = collect($invoiceApplicants ?? [$applicant])->values();
    $perChildAmount = 4000.00;
    $invoiceTotal = $invoiceApplicants->count() * $perChildAmount;
    $total = (float) old('amount', $payment->amount ?? $invoiceTotal);
    $invoiceRootId = $applicant->family_application_id ?: $applicant->id;
    $dbInvoice = \Illuminate\Support\Facades\Schema::hasTable('invoices')
        ? \Illuminate\Support\Facades\DB::table('invoices')
            ->where(function($query) use ($applicant) {
                if ($applicant->family_application_id) {
                    $query->where('family_application_id', $applicant->family_application_id);
                } else {
                    $query->where('user_id', $applicant->user_id)->whereNull('family_application_id');
                }
            })->first()
        : null;
    $invoiceNumber = $dbInvoice ? $dbInvoice->invoice_no : 'INV-ENR-' . str_pad((string) $invoiceRootId, 5, '0', STR_PAD_LEFT);
    $paymentStatus = strtolower((string) ($payment->status ?? 'pending'));
    $isPaid = $paymentStatus === 'verified';
    $paymentMethodLabel = strtoupper(str_replace('_', '/', (string) ($payment->method ?? 'gcash_maya')));
    $learningModeLabel = function ($mode) {
        $normalized = strtolower(trim((string) $mode));

        return match ($normalized) {
            'face_to_face', 'face-to-face', 'face to face', 'f2f' => 'F2F',
            'flexible_1st_shift', 'flexible learning - 1st shift', 'flexible 1st shift', '1st shift', 'flexible_learning_1st_shift', 'fol - 1st shift', 'flexible online learning - 1st shift', 'flexible online learning – 1st shift' => 'FOL - 1ST SHIFT',
            'flexible_2nd_shift', 'flexible learning - 2nd shift', 'flexible 2nd shift', '2nd shift', 'flexible_learning_2nd_shift', 'fol - 2nd shift', 'flexible online learning - 2nd shift', 'flexible online learning – 2nd shift' => 'FOL - 2ND SHIFT',
            'flexible_3rd_shift', 'flexible learning - 3rd shift', 'flexible 3rd shift', '3rd shift', 'flexible_learning_3rd_shift', 'fol - 3rd shift', 'flexible online learning - 3rd shift', 'flexible online learning – 3rd shift' => 'FOL - 3RD SHIFT',
            'flexible_4th_shift', 'flexible learning - 4th shift', 'flexible 4th shift', '4th shift', 'flexible_learning_4th_shift', 'fol - 4th shift', 'flexible online learning - 4th shift', 'flexible online learning – 4th shift' => 'FOL - 4TH SHIFT',
            default => $mode ? strtoupper(str_replace('flexible online learning', 'FOL', str_replace('flexible learning', 'FOL', (string) $mode))) : 'PENDING',
        };
    };
@endphp
@include('components.dashboard.family-group-styles')
<style>
    /* Remove HTML5 up/down spin buttons for number inputs */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
    }
    /* Spinner animation */
    @keyframes payment-spin {
        to { transform: rotate(360deg); }
    }
    .loading-spinner-icon {
        animation: payment-spin 0.8s linear infinite;
    }
    /* Visually uppercase text and email inputs on the payment page */
    .payment-container input[type="text"],
    .payment-container input[type="email"] {
        text-transform: uppercase !important;
    }
    .payment-container input[type="text"]::placeholder,
    .payment-container input[type="email"]::placeholder {
        text-transform: none !important;
    }
</style>
<div x-data="{ loaded: false, method: 'gcash_maya', showAgreementModal: false, agreedPrivacy: false, agreedTerms: false, agreedFee: false, amountPaid: {{ $total }}, invoiceTotal: {{ $invoiceTotal }}, submitting: false, childrenNames: @js($invoiceApplicants->map(fn($c) => strtoupper($c->full_name ?: trim(($c->first_name ?? '') . ' ' . ($c->middle_name ?? '') . ' ' . ($c->last_name ?? ''))))->values()->all()) }" x-init="setTimeout(() => loaded = true, 800)" class="enrollment-page">

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
                        <div class="arabic brand-arabic" lang="ar" dir="rtl" style="font-family: 'Noto Naskh Arabic', 'Amiri', 'Traditional Arabic', Tahoma, Arial, sans-serif !important; letter-spacing: 0 !important; word-spacing: normal !important; text-transform: none !important; direction: rtl; unicode-bidi: isolate;">المدرسة المنورة الإسلامية</div>
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


                <form method="POST" action="{{ route('enrollment.payment.submit') }}" enctype="multipart/form-data" @submit="submitting = true">
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



                {{-- Official payment channels --}}
                <div class="payment-section">
                    {{-- Payment reminder notice --}}
                    <div class="payment-reminder-notice">
                        <div class="payment-reminder-header">
                            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="payment-reminder-logo" style="width: 48px; height: 48px; object-fit: contain; flex-shrink: 0;">
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
                        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" class="payment-channels-logo" style="width: 42px; height: 42px; object-fit: contain; background: white; border-radius: 50%; padding: 3px; flex-shrink: 0;">
                        <div>
                            <div class="payment-channels-title">Official Payment Channels</div>
                            <div class="payment-channels-subtitle">Only BDO, GCash, and Maya are recognized as official modes of payment.</div>
                        </div>
                    </div>

                    <div class="payment-channels-grid">
                        {{-- BDO --}}
                        <div class="payment-channel-card">
                            <div class="payment-channel-head">
                                <img src="{{ asset('images/mode_of_payments/BDO.png') }}" alt="BDO" class="payment-channel-img" style="height: 28px; object-fit: contain;">
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
                                <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash" class="payment-channel-img" style="height: 28px; object-fit: contain;">
                                <img src="{{ asset('images/mode_of_payments/MAYA.png') }}" alt="Maya" class="payment-channel-img" style="height: 28px; object-fit: contain;">
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

                {{-- Select Payment Method Dropdown --}}
                <div class="payment-section">
                    <label class="payment-field-label">Select Payment Method <span class="required">*</span></label>
                    <select x-model="method" class="plain-input" style="width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 0.75rem 1rem; outline: none; background-color: white; font-family: inherit; font-weight: 500; cursor: pointer;">
                        <option value="gcash_maya">GCash / Maya</option>
                        <option value="bdo">BDO Bank</option>
                    </select>
                </div>

                {{-- Amount Paid and Reference Number adaptive grid --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1rem;">
                    {{-- Amount --}}
                    <div class="payment-section" style="margin-bottom: 0;">
                        <label class="payment-field-label">Amount Paid <span class="required">*</span></label>
                        <input type="number" name="amount" x-model.number="amountPaid"
                            min="1" step="0.01" required
                            placeholder="Enter the amount paid"
                            class="plain-input">
                    </div>

                    {{-- Reference number --}}
                    <div class="payment-section" style="margin-bottom: 0;">
                        <label class="payment-field-label">Reference Number / Transaction ID</label>
                        <input type="text" name="reference_no" value="{{ old('reference_no', $payment->reference_no ?? '') }}"
                            placeholder="Enter the transaction reference number if available"
                            class="plain-input">
                    </div>
                </div>

                {{-- Reactive payment alerts (Full Width) --}}
                <div class="payment-section" style="width: 100%; margin-bottom: 1.5rem; font-family: inherit;">
                    {{-- Case 1: Underpayment --}}
                    <div x-show="amountPaid && amountPaid > 0 && amountPaid < invoiceTotal" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-2"
                         class="payment-alert payment-alert-warning">
                        <svg class="payment-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <div class="payment-alert-content">
                            <strong class="payment-alert-title">Underpayment Notice</strong>
                            <span class="payment-alert-description">You are paying less than the total invoice amount. The Finance Office will review your payment and contact you to settle the remaining balance of <strong style="color: #b45309;">PHP <span x-text="(invoiceTotal - amountPaid).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></strong>.</span>
                        </div>
                    </div>

                    {{-- Case 2: Overpayment --}}
                    <div x-show="amountPaid && amountPaid > invoiceTotal" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-2"
                         class="payment-alert payment-alert-info">
                        <svg class="payment-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <div class="payment-alert-content">
                            <strong class="payment-alert-title">Excess Payment Notice</strong>
                            <span class="payment-alert-description">You are paying more than the total invoice amount. The excess amount of <strong style="color: #1e40af;">PHP <span x-text="(amountPaid - invoiceTotal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></strong> will be automatically credited to miscellaneous or future school fees.</span>
                        </div>
                    </div>

                    {{-- Case 3: Exact Match --}}
                    <div x-show="amountPaid && amountPaid === invoiceTotal" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-2"
                         class="payment-alert payment-alert-success">
                        <svg class="payment-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <div class="payment-alert-content">
                            <strong class="payment-alert-title">Exact Amount Entered</strong>
                            <span class="payment-alert-description">Perfect! Your payment matches the total invoice amount of <strong style="color: #065f46;">PHP {{ number_format($invoiceTotal, 2) }}</strong>.</span>
                        </div>
                    </div>
                </div>

                {{-- Receipt upload --}}
                {{-- Receipt upload --}}
                <div x-data="paymentReceiptUpload()" class="payment-section">
                    <script>
                    if (!window.AMIS_UploadUtils) {
                        window.AMIS_UploadUtils = {
                            validateFile(file, acceptStr) {
                                if (!acceptStr) return { valid: true };
                                const allowed = acceptStr.split(',').map(s => s.trim().toLowerCase());
                                const fileName = file.name.toLowerCase();
                                const fileType = file.type.toLowerCase();
                                
                                let match = false;
                                for (const item of allowed) {
                                    if (item.startsWith('.')) {
                                        if (fileName.endsWith(item)) { match = true; break; }
                                    } else if (item.endsWith('/*')) {
                                        const prefix = item.slice(0, -1);
                                        if (fileType.startsWith(prefix)) { match = true; break; }
                                    } else {
                                        if (fileType === item) { match = true; break; }
                                    }
                                }
                                
                                if (!match) {
                                    const readableTypes = allowed.map(item => {
                                        if (item.startsWith('.')) return item.substring(1).toUpperCase();
                                        if (item === 'application/pdf') return 'PDF';
                                        if (item.startsWith('image/')) return item.substring(6).toUpperCase();
                                        return item;
                                    });
                                    return {
                                        valid: false,
                                        error: `Unsupported file format. Supported: ${[...new Set(readableTypes)].join(', ')}.`
                                    };
                                }
                                return { valid: true };
                            },
                            compressImage(file, quality = 0.8) {
                                return new Promise((resolve, reject) => {
                                    const reader = new FileReader();
                                    reader.readAsDataURL(file);
                                    reader.onload = (event) => {
                                        const img = new Image();
                                        img.src = event.target.result;
                                        img.onload = () => {
                                            try {
                                                const canvas = document.createElement('canvas');
                                                let width = img.width;
                                                let height = img.height;
                                                const maxDim = 2048;
                                                if (width > maxDim || height > maxDim) {
                                                    if (width > height) {
                                                        height = Math.round((height * maxDim) / width);
                                                        width = maxDim;
                                                    } else {
                                                        width = Math.round((width * maxDim) / height);
                                                        height = maxDim;
                                                    }
                                                }
                                                canvas.width = width;
                                                canvas.height = height;
                                                const ctx = canvas.getContext('2d');
                                                ctx.fillStyle = '#FFFFFF';
                                                ctx.fillRect(0, 0, width, height);
                                                ctx.drawImage(img, 0, 0, width, height);
                                                canvas.toBlob((blob) => {
                                                    if (!blob) {
                                                        reject(new Error('Optimizing image canvas conversion failed.'));
                                                        return;
                                                    }
                                                    const optimizedFile = new File([blob], file.name, {
                                                        type: 'image/jpeg',
                                                        lastModified: Date.now()
                                                    });
                                                    resolve(optimizedFile);
                                                }, 'image/jpeg', quality);
                                            } catch (e) {
                                                reject(e);
                                            }
                                        };
                                        img.onerror = () => reject(new Error('Selected file could not be read as an image.'));
                                    };
                                    reader.onerror = () => reject(new Error('Selected file reader error.'));
                                });
                            }
                        };
                    }

                    function registerReceiptComponent(Alpine) {
                        // Define globally on window so Alpine can find it even if initialization races with asset loading
                        window.paymentReceiptUpload = () => ({
                            files: [], // Array of { name: '', size: '', preview: null, rawFile: File }
                            isProcessing: false,
                            errorMsg: '',
                            removeFile(index) {
                                this.files.splice(index, 1);
                                this.syncInputFiles();
                            },
                            syncInputFiles() {
                                const dt = new DataTransfer();
                                this.files.forEach(f => {
                                    if (f.rawFile) {
                                        dt.items.add(f.rawFile);
                                    }
                                });
                                this.$refs.receiptInput.files = dt.files;
                                
                                // update validation state
                                if (this.files.length === 0) {
                                    this.$refs.receiptInput.required = {{ !$payment?->receipt_url ? 'true' : 'false' }};
                                } else {
                                    this.$refs.receiptInput.removeAttribute('required');
                                }
                            },
                            async handleReceiptChange(event) {
                                const selectedFiles = Array.from(event.target.files);
                                if (selectedFiles.length === 0) return;
                                
                                this.errorMsg = '';
                                const maxSizeMB = 5;
                                
                                for (let rawFile of selectedFiles) {
                                    const validation = window.AMIS_UploadUtils.validateFile(rawFile, 'image/jpeg,image/jpg,image/png,application/pdf');
                                    if (!validation.valid) {
                                        this.errorMsg = validation.error;
                                        continue;
                                    }
                                    
                                    const fileSizeMB = rawFile.size / (1024 * 1024);
                                    const isImage = rawFile.type.startsWith('image/');
                                    
                                    if (!isImage) {
                                        if (fileSizeMB > maxSizeMB) {
                                            this.errorMsg = `File "${rawFile.name}" exceeds the maximum limit of ${maxSizeMB}MB.`;
                                            continue;
                                        }
                                    } else {
                                        if (fileSizeMB > 2) {
                                            this.isProcessing = true;
                                            try {
                                                const originalSize = rawFile.size;
                                                const optimizedFile = await window.AMIS_UploadUtils.compressImage(rawFile, 0.88);
                                                if (optimizedFile.size < originalSize) {
                                                    rawFile = optimizedFile;
                                                }
                                            } catch(e) {
                                                console.error('Receipt compression error:', e);
                                                if (fileSizeMB > maxSizeMB) {
                                                    this.errorMsg = `Image optimization failed for "${rawFile.name}" and it exceeds the maximum limit of ${maxSizeMB}MB.`;
                                                    this.isProcessing = false;
                                                    continue;
                                                }
                                            } finally {
                                                this.isProcessing = false;
                                            }
                                        }
                                    }
                                    
                                    const finalSizeMB = rawFile.size / (1024 * 1024);
                                    if (finalSizeMB > maxSizeMB) {
                                        this.errorMsg = `The file "${rawFile.name}" exceeds the maximum allowed limit of ${maxSizeMB}MB.`;
                                        continue;
                                    }
                                    
                                    // Generate preview
                                    let preview = null;
                                    if (rawFile.type.startsWith('image/')) {
                                        preview = await new Promise(resolve => {
                                            const reader = new FileReader();
                                            reader.onload = e => resolve(e.target.result);
                                            reader.readAsDataURL(rawFile);
                                        });
                                    } else {
                                        preview = 'pdf';
                                    }
                                    
                                    this.files.push({
                                        name: rawFile.name,
                                        size: finalSizeMB.toFixed(2) + ' MB',
                                        preview: preview,
                                        rawFile: rawFile
                                    });
                                }
                                
                                this.syncInputFiles();
                            }
                        });

                        if (Alpine && Alpine.data) {
                            try {
                                Alpine.data('paymentReceiptUpload', window.paymentReceiptUpload);
                            } catch(e) {
                                console.error('Failed to register payment upload component', e);
                            }
                        }
                    }

                    if (!window.AMIS_ReceiptComponentRegistered) {
                        window.AMIS_ReceiptComponentRegistered = true;
                        if (window.Alpine) {
                            registerReceiptComponent(window.Alpine);
                        } else {
                            document.addEventListener('alpine:init', () => {
                                registerReceiptComponent(window.Alpine);
                            });
                        }
                    }
                    </script>

                    <label class="payment-field-label">Upload Payment Receipt/s @if(!$payment?->receipt_url)<span class="required">*</span>@endif</label>

                    @if ($payment && count($payment->receipt_urls) > 0)
                        <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:0.75rem 1rem;border-radius:10px;font-size:0.82rem;margin-bottom:0.75rem;display:flex;align-items:center;gap:0.5rem;font-weight:700;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>You have already uploaded proof of payment. Uploading a new file will replace all previous ones.</span>
                        </div>

                        <div class="space-y-2 mb-4">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Previously Uploaded Receipts:</span>
                            @foreach ($payment->receipt_urls as $index => $receiptPath)
                                @php
                                    $url = asset('storage/' . $receiptPath);
                                    $isPdf = $receiptPath && strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION)) === 'pdf';
                                @endphp
                                <div class="payment-preview-card" style="margin-bottom: 0.5rem;">
                                    @if ($isPdf)
                                        <div class="payment-preview-pdf">
                                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.5">
                                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                            </svg>
                                        </div>
                                    @else
                                        <img src="{{ $url }}" alt="Receipt preview" class="payment-preview-img">
                                    @endif
                                    <div class="payment-preview-info">
                                        <div>
                                            <div class="payment-preview-name">{{ basename($receiptPath) }}</div>
                                            @if ($invoiceApplicants->has($index))
                                                @php $matchedChild = $invoiceApplicants->get($index); @endphp
                                                <div style="font-size: 0.72rem; color: #047857; font-weight: 700; margin-top: 0.25rem; text-transform: uppercase;">
                                                    FOR: {{ $matchedChild->full_name ?: trim(($matchedChild->first_name ?? '') . ' ' . ($matchedChild->middle_name ?? '') . ' ' . ($matchedChild->last_name ?? '')) }}
                                                </div>
                                            @endif
                                        </div>
                                        <a href="{{ $url }}" target="_blank" class="payment-preview-remove" style="background:#f0fdf4; border:1px solid #bbf7d0; color:#065f46; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:0.25rem;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                            View
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Client-side Validation Error -->
                    <div
                        x-show="errorMsg"
                        x-cloak
                        style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:0.75rem 1rem;border-radius:10px;font-size:0.82rem;margin-bottom:0.75rem;display:flex;align-items:start;gap:0.5rem;font-weight:600;"
                    >
                        <svg class="!mt-0.5" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div style="flex:1; text-align: left;">
                            <strong>Upload Error:</strong> <span x-text="errorMsg"></span>
                        </div>
                        <button type="button" @click="errorMsg = ''" style="background:none;border:none;color:#fca5a5;cursor:pointer;padding:0;outline:none;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    <div>
                        <label class="payment-upload-area !relative !overflow-hidden">
                            <!-- Loading/Processing Overlay -->
                            <div
                                x-show="isProcessing"
                                x-cloak
                                style="position: absolute !important; inset: 0 !important; z-index: 50 !important; display: flex !important; align-items: center !important; justify-content: center !important; background: rgba(248, 250, 252, 0.75) !important; backdrop-filter: blur(4px) !important; border-radius: 10px !important; box-sizing: border-box !important;"
                            >
                                <style>
                                    @keyframes receipt-upload-spin {
                                        to { transform: rotate(360deg); }
                                    }
                                    .receipt-upload-spinner {
                                        animation: receipt-upload-spin 1s linear infinite !important;
                                    }
                                </style>
                                <div style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 12px !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.05) !important; padding: 1.25rem 1.5rem !important; width: 85% !important; max-width: 200px !important; display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; text-align: center !important; box-sizing: border-box !important;">
                                    <div style="display: flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 0.5rem !important; width: 100% !important;">
                                        <svg class="receipt-upload-spinner" style="width: 32px !important; height: 32px !important; display: block !important; margin: 0 auto !important; flex-shrink: 0 !important;" viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="#e2e8f0" stroke-width="3"></circle>
                                            <path d="M12 2a10 10 0 0 1 10 10" stroke="#10b981" stroke-width="3" stroke-linecap="round"></path>
                                        </svg>
                                    </div>
                                    <span style="font-size: 0.85rem !important; font-weight: 700 !important; color: #0f172a !important; display: block !important; margin-bottom: 0.15rem !important; width: 100% !important; line-height: 1.25 !important;">Compressing Image...</span>
                                    <span style="font-size: 0.72rem !important; font-weight: 500 !important; color: #64748b !important; display: block !important; width: 100% !important; line-height: 1.2 !important;">Optimizing for upload</span>
                                </div>
                            </div>

                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                                <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                                <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
                            </svg>
                            <span class="payment-upload-text">Click to upload files</span>
                            <div style="margin-top:0.35rem; display:flex; flex-direction:column; align-items:center; gap:0.125rem; font-size:11px; font-weight:500; color:#6b7280;">
                                <span>Accepted Formats: <strong style="color:#374151;">JPG, JPEG, PNG</strong></span>
                                <span style="color:#059669; font-weight:600; margin-top:0.25rem;">(Multiple files supported, large images compressed)</span>
                            </div>
                            <input x-ref="receiptInput" type="file" name="receipts[]" multiple accept=".png,.jpg,.jpeg,image/png,image/jpeg" style="display:none;" {{ !$payment?->receipt_url ? 'required' : '' }}
                                @change="handleReceiptChange($event)">
                        </label>
                    </div>

                    {{-- Previews list --}}
                    <div x-show="files.length > 0" class="space-y-3" style="margin-top: 0.75rem;">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Files selected for upload:</span>
                        <template x-for="(file, index) in files" :key="index">
                            <div class="payment-preview-card" style="margin-bottom: 0;">
                                <template x-if="file.preview && file.preview !== 'pdf'">
                                    <img :src="file.preview" alt="Receipt preview" class="payment-preview-img">
                                </template>
                                <template x-if="file.preview === 'pdf'">
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
                                        <div class="payment-preview-name" x-text="file.name"></div>
                                        <template x-if="childrenNames && childrenNames[index]">
                                            <div style="font-size: 0.72rem; color: #047857; font-weight: 700; margin-top: 0.15rem; text-transform: uppercase; text-align: left;">
                                                FOR: <span x-text="childrenNames[index]"></span>
                                            </div>
                                        </template>
                                        <div class="payment-preview-size" x-text="file.size"></div>
                                    </div>
                                    <button type="button" @click="removeFile(index)"
                                        class="payment-preview-remove">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Submit --}}
                @if ($applicant->status === 'ready_for_submission')
                    <button type="button" @click="showAgreementModal = true" class="btn-primary payment-submit-btn" style="cursor:pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;" :disabled="submitting">
                        <svg x-show="!submitting" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <svg x-show="submitting" x-cloak class="loading-spinner-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                        <span x-text="submitting ? 'SUBMITTING...' : 'Finalize & Submit'"></span>
                    </button>
                @else
                    <button type="submit" class="btn-primary payment-submit-btn" style="cursor:pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;" :disabled="submitting">
                        <svg x-show="!submitting" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <svg x-show="submitting" x-cloak class="loading-spinner-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                        </svg>
                        <span x-text="submitting ? 'SUBMITTING...' : 'Finalize & Submit'"></span>
                    </button>
                @endif

                {{-- Cancel --}}
                <a href="{{ route('enrollment.dashboard') }}" class="btn-secondary" style="width: 100%; justify-content: center; padding: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; box-sizing: border-box; margin-top: 0.75rem; font-weight: 600; border-radius: 10px;">
                    Cancel
                </a>

                <p class="payment-submit-note">Payment will be verified by the Finance Office within 1–2 business days.</p>

                @if ($applicant->status === 'ready_for_submission')
                <!-- Final Agreements Modal Overlay -->
                <div x-show="showAgreementModal" x-cloak class="duplicate-modal-overlay" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="duplicate-modal-container" style="max-width: 580px; padding: 1.5rem 1.75rem;" @click.away="showAgreementModal = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                        <div class="duplicate-modal-header" style="margin-bottom: 0.5rem;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <h3 style="font-size:1.25rem; font-family:inherit; font-weight:900; margin:0; color:#0f172a;">Final Enrollment Agreement</h3>
                        </div>

                        <div style="font-size:0.9rem;color:#475569;margin-bottom:1rem;padding-bottom:0.75rem;border-bottom:1px solid #e2e8f0;line-height:1.4;text-align:left;">
                            Review terms and confirm submission for:
                            <div style="margin-top:0.35rem;font-weight:800;color:#0f172a;text-transform:uppercase;">
                                {{ $invoiceApplicants->map(fn($c) => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')))->join(' & ') }}
                            </div>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:0.95rem;margin-bottom:1.5rem;max-height: 250px; overflow-y: auto; padding-right: 0.5rem;">
                            <!-- 1. Data Privacy -->
                            <label style="display:flex;gap:0.65rem;cursor:pointer;user-select:none;font-size:0.82rem;color:#4b5563;line-height:1.4;text-align:left;">
                                <input type="checkbox" x-model="agreedPrivacy" style="width:16px;height:16px;margin-top:0.15rem;accent-color:#059669;cursor:pointer;">
                                <span>I agree to the <strong>Data Privacy Policy</strong>. I consent to the collection and processing of my personal information for enrollment purposes.</span>
                            </label>

                            <!-- 2. Final Agreement -->
                            <label style="display:flex;gap:0.65rem;cursor:pointer;user-select:none;font-size:0.82rem;color:#4b5563;line-height:1.4;text-align:left;">
                                <input type="checkbox" x-model="agreedTerms" style="width:16px;height:16px;margin-top:0.15rem;accent-color:#059669;cursor:pointer;">
                                <span>By submitting this enrollment form, I certify that all information provided is true and correct. I understand that any false information may result in the denial or cancellation of enrollment. I agree to the <strong>terms and conditions</strong> of enrollment at Al Munawwara Islamic School.</span>
                            </label>

                            <!-- 3. Fee Policy -->
                            <label style="display:flex;gap:0.65rem;cursor:pointer;user-select:none;font-size:0.82rem;color:#4b5563;line-height:1.4;text-align:left;">
                                <input type="checkbox" x-model="agreedFee" style="width:16px;height:16px;margin-top:0.15rem;accent-color:#059669;cursor:pointer;">
                                <span>I understand that the <strong>enrollment fee is non-refundable once paid</strong>, even if the application is later rejected due to incomplete, invalid, or unqualified documents.</span>
                            </label>
                        </div>

                        <div class="duplicate-modal-actions">
                            <button type="button" class="duplicate-btn-cancel" @click="showAgreementModal = false" :disabled="submitting">GO BACK</button>
                            <button type="submit" class="duplicate-btn-confirm" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;" :disabled="!agreedPrivacy || !agreedTerms || !agreedFee || submitting" :class="{ 'is-disabled': !agreedPrivacy || !agreedTerms || !agreedFee || submitting }">
                                <svg x-show="submitting" x-cloak class="loading-spinner-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                                </svg>
                                <span x-text="submitting ? 'SUBMITTING...' : 'FINALIZE & SUBMIT'"></span>
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                </form>
            </div>
        </div>

        <div class="enrollment-footer">
            &copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.
        </div>
    </div>
</div>

<script>
    // Force payment text inputs to uppercase visually and programmatically
    document.addEventListener('input', function (e) {
        if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
            const type = e.target.type ? e.target.type.toLowerCase() : 'text';
            if (type === 'text' || type === 'email' || type === 'search' || type === 'url' || e.target.tagName === 'TEXTAREA') {
                const originalVal = e.target.value;
                const upperVal = originalVal.toUpperCase();
                if (originalVal !== upperVal) {
                    let start = e.target.selectionStart;
                    let end = e.target.selectionEnd;
                    
                    e.target.value = upperVal;
                    
                    if (start !== null && end !== null) {
                        try {
                            e.target.setSelectionRange(start, end);
                        } catch (err) {}
                    }
                    
                    if (!e.target._uppercasing) {
                        e.target._uppercasing = true;
                        e.target.dispatchEvent(new Event('input', { bubbles: true }));
                        e.target._uppercasing = false;
                    }
                }
            }
        }
    }, true);
</script>
</x-guest-layout>

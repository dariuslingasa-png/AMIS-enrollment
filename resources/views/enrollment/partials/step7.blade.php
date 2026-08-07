<section class="space-y-6">
    {{-- Step 7 Heading --}}
    <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50/80 via-blue-50/40 to-white p-5 shadow-3xs">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="14" x="2" y="5" rx="2"/>
                    <line x1="2" x2="22" y1="10" y2="10"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900 uppercase tracking-wider">Step 7: Mode of Payment & Proof of Payment</h3>
                <p class="text-xs font-semibold text-slate-500">Select your payment method and upload the official transaction receipt / proof of payment.</p>
            </div>
        </div>
    </div>

    {{-- Official Payment Channels --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold">₱</div>
                <div>
                    <span class="text-xs font-black text-emerald-950 block uppercase">Enrollment Fee Rate</span>
                    <span class="text-[11px] font-bold text-emerald-700">Official fee: ₱4,000.00 per child</span>
                </div>
            </div>
            <span class="inline-flex items-center rounded-full bg-emerald-200/80 px-3 py-1 text-xs font-black text-emerald-900">SY 2026–2027</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- BDO Card --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3.5">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/mode_of_payments/BDO.png') }}" alt="BDO Logo" class="h-9 w-auto object-contain rounded-md shadow-3xs">
                    <strong class="text-sm font-black text-slate-900">BDO Savings / Current Accounts</strong>
                </div>
                <div class="space-y-2.5 pl-1">
                    <div class="space-y-2.5">
                        <div class="border-b border-slate-200 pb-2">
                            <span class="text-xs font-bold text-slate-700 uppercase block tracking-wider">AL MUNAWWARA ISLAMIC SCHOOL Inc.</span>
                            <span class="text-base font-black text-slate-950 block mt-0.5">BDO Savings: 010478011996</span>
                        </div>
                        <div class="border-b border-slate-200 pb-2">
                            <span class="text-xs font-bold text-slate-700 uppercase block tracking-wider">CABEL B. NURHASAN</span>
                            <span class="text-base font-black text-slate-950 block mt-0.5">BDO Current: 010478008782</span>
                        </div>
                        <div class="border-b border-slate-200 pb-2">
                            <span class="text-xs font-bold text-slate-700 uppercase block tracking-wider">CABEL NURHASAN</span>
                            <span class="text-base font-black text-slate-950 block mt-0.5">BDO Savings: 010470022817</span>
                        </div>
                        <div class="border-b border-slate-200 pb-2">
                            <span class="text-xs font-bold text-slate-700 uppercase block tracking-wider">WARDAH D. PINDATON / JAMELLA P. MOHAMAD</span>
                            <span class="text-base font-black text-slate-950 block mt-0.5">BDO Savings: 010470099925</span>
                        </div>
                        <div class="pb-1">
                            <span class="text-xs font-bold text-slate-700 uppercase block tracking-wider">JAMELLA P. MOHAMAD / WARDAH D. PINDATON</span>
                            <span class="text-base font-black text-slate-950 block mt-0.5">BDO Savings: 010470105712</span>
                        </div>
                    </div>
                    <div class="pt-2.5 border-t border-slate-200 text-xs text-slate-500 space-y-1">
                        <p>Swift Code: <strong class="text-slate-800 font-extrabold uppercase">BNORPHMM</strong></p>
                        <p>Branch: <strong class="text-slate-800 font-extrabold uppercase text-xs">WOODLANE DIVERSION ROAD - DAVAO CITY</strong></p>
                    </div>
                </div>
            </div>

            {{-- GCash / Maya Card --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('images/mode_of_payments/GCASH.png') }}" alt="GCash Logo" class="h-9 w-auto object-contain rounded-md shadow-3xs">
                        <strong class="text-sm font-black text-slate-900">GCash Authorized Payment Center</strong>
                    </div>
                    <div class="space-y-3 pl-1 mt-4">
                        <div class="pb-2">
                            <span class="text-[11px] font-black text-slate-500 uppercase block tracking-wider">Account Name</span>
                            <strong class="text-slate-900 font-black text-base block mt-0.5">CABEL B. NURHASAN</strong>
                        </div>
                        <div class="pt-3 border-t border-slate-200">
                            <span class="text-[11px] font-black text-slate-500 uppercase block tracking-wider">GCash Number</span>
                            <strong class="text-indigo-900 font-black text-lg block mt-0.5">(+63) 927 299 1833</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Input Fields --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
            <div class="form-group">
                <x-form-field-label required>Payment Method</x-form-field-label>
                <select name="method" x-model="form.payment_method" class="select-input" :class="{ 'is-invalid-field': isFieldInvalid('payment_method') }">
                    <option value="gcash">GCash</option>
                    <option value="maya">Maya</option>
                    <option value="remittance">Remittance</option>
                    <option value="bdo">BDO Bank Transfer</option>
                    <option value="other">Other Payments</option>
                </select>
            </div>
            
            <x-form-input label="Amount Paid (PHP)" name="amount" type="number" required x-model="form.amount" min="1" step="0.01" placeholder="4000.00" />
            
            <x-form-input label="Reference No. / Ref ID" name="reference_no" placeholder="e.g. 100234589" x-model="form.reference_no" class="uppercase" />
        </div>

        {{-- Proof of Payment Upload Dropzone --}}
        <div class="form-group pt-2">
            <x-form-field-label required>Upload Proof of Payment / Receipt Photo</x-form-field-label>
            
            <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/50 p-5 text-center hover:border-indigo-400 transition" :class="{ 'is-invalid-field': isFieldInvalid('payment_receipt') }">
                <input type="file" name="payment_receipt" id="payment_receipt_input" accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                    @change="
                        const file = $event.target.files[0];
                        if (file) {
                            if (file.type.startsWith('image/')) {
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    paymentReceiptPreview = e.target.result;
                                };
                                reader.readAsDataURL(file);
                            } else {
                                paymentReceiptPreview = 'image';
                            }
                        }
                    "
                    class="hidden"
                >

                <template x-if="!paymentReceiptPreview">
                    <label for="payment_receipt_input" class="cursor-pointer block py-4 space-y-2">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </div>
                        <div>
                            <span class="text-xs font-extrabold text-indigo-700 hover:underline">Click to upload payment receipt</span>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">JPG or PNG receipt photo</p>
                        </div>
                    </label>
                </template>

                <template x-if="paymentReceiptPreview">
                    <div class="flex items-center justify-between rounded-xl border border-indigo-200 bg-indigo-50/80 p-3 text-left">
                        <div class="flex items-center gap-3">
                            <template x-if="paymentReceiptPreview !== 'pdf'">
                                <img :src="paymentReceiptPreview" class="h-14 w-14 rounded-lg object-cover ring-2 ring-indigo-300">
                            </template>
                            <template x-if="paymentReceiptPreview === 'pdf'">
                                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-rose-100 text-rose-600 font-black text-xs">PDF</div>
                            </template>
                            <div>
                                <span class="text-xs font-black text-indigo-950 block">Payment Receipt Uploaded</span>
                                <span class="text-[10px] font-bold text-indigo-700">Ready for final review on Step 8</span>
                            </div>
                        </div>
                        <label for="payment_receipt_input" class="text-xs font-extrabold text-indigo-700 hover:text-indigo-900 underline cursor-pointer">Change File</label>
                    </div>
                </template>
            </div>
        </div>

        {{-- Remarks / Payment Notes --}}
        <div class="form-group pt-2">
            <x-form-field-label optional>Remarks / Payment Notes (If no receipt, write explanation here)</x-form-field-label>
            <textarea name="remarks" x-model="form.remarks" placeholder="If you do not have a receipt/screenshot yet, please write an explanation here (e.g. over-the-counter deposit date, pending bank reference, etc.) to proceed." class="plain-input min-h-[80px] py-2.5 px-3.5 text-xs text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-xl border border-slate-200 normal-case" :class="{ 'is-invalid-field': isFieldInvalid('remarks') }"></textarea>
        </div>
    </div>
</section>

<section class="space-y-6">
    {{-- Form Section Heading --}}
    <div class="rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50/80 via-teal-50/40 to-white p-5 shadow-3xs">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900 uppercase tracking-wider">Step 7: Application Preview & Payment</h3>
                <p class="text-xs font-semibold text-slate-500">Please review all submitted student details, uploaded documents, and attach your proof of payment before final submission.</p>
            </div>
        </div>
    </div>

    {{-- Application & Student Profile Summary Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">1. Student & Application Summary</h4>
            </div>
            <button type="button" @click="goToStep(2)" class="text-xs font-extrabold text-emerald-700 hover:text-emerald-800 underline cursor-pointer">Edit Details</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="rounded-xl border border-slate-150 bg-slate-50/60 p-3.5 space-y-1">
                <span class="text-[10px] font-black uppercase text-slate-400 block">Student Name</span>
                <p class="font-extrabold text-slate-900 text-sm" x-text="`${form.first_name || ''} ${form.middle_name || ''} ${form.last_name || ''}`.trim() || 'Not specified'"></p>
                <p class="text-[11px] font-bold text-slate-500" x-text="`Student Type: ${form.student_type || 'N/A'}`"></p>
            </div>
            <div class="rounded-xl border border-slate-150 bg-slate-50/60 p-3.5 space-y-1">
                <span class="text-[10px] font-black uppercase text-slate-400 block">Grade & Modality</span>
                <p class="font-extrabold text-slate-900 text-sm" x-text="form.grade_level || 'Grade pending'"></p>
                <p class="text-[11px] font-bold text-emerald-700" x-text="form.learning_mode || 'Modality pending'"></p>
            </div>
            <div class="rounded-xl border border-slate-150 bg-slate-50/60 p-3.5 space-y-1">
                <span class="text-[10px] font-black uppercase text-slate-400 block">Parent / Guardian Contact</span>
                <p class="font-extrabold text-slate-900" x-text="`${form.father_last_name ? 'Father: ' + form.father_first_name + ' ' + form.father_last_name : 'Mother: ' + form.mother_first_name + ' ' + form.mother_last_name}`"></p>
                <p class="text-[11px] font-bold text-slate-600" x-text="`Mobile: ${form.parent_country_code || '+63'} ${form.parent_mobile || ''}`"></p>
            </div>
        </div>
    </div>

    {{-- Attached Student Documents Preview Box --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">2. Uploaded Student Requirements</h4>
            </div>
            <button type="button" @click="goToStep(6)" class="text-xs font-extrabold text-indigo-600 hover:text-indigo-700 underline cursor-pointer">Manage Documents</button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Photo 2x2 --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 font-bold flex-shrink-0">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">2x2 Student Photo</span>
                    <span class="text-xs font-bold text-emerald-700 flex items-center gap-1">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Photo Attached
                    </span>
                </div>
            </div>

            {{-- Birth Cert --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 font-bold flex-shrink-0">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Birth Certificate</span>
                    <span class="text-xs font-bold text-slate-700">Copy Attached</span>
                </div>
            </div>

            {{-- Report Card / Affidavit --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 font-bold flex-shrink-0">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Report Card / Affidavit</span>
                    <span class="text-xs font-bold text-slate-700">Document Attached</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Mode of Payment & Proof of Payment Upload Box --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">3. Mode of Payment & Proof of Payment Receipt</h4>
        </div>

        {{-- Official Payment Channels Cards --}}
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase text-slate-700">Official Payment Channels:</span>
                <span class="text-[11px] font-bold text-emerald-700">Enrollment Fee: ₱4,000.00 / Child</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-1">
                    <strong class="text-blue-900 block font-black">BDO Bank Transfer / Deposit</strong>
                    <p class="text-slate-600">Account Name: <strong>AL MUNAWWARA ISLAMIC SCHOOL Inc.</strong></p>
                    <p class="text-slate-600">Account No.: <strong class="text-slate-900">010478011996</strong></p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-1">
                    <strong class="text-blue-600 block font-black">GCash / Maya Payment</strong>
                    <p class="text-slate-600">Account Name: <strong>CABEL B. NURHASAN</strong></p>
                    <p class="text-slate-600">Mobile No.: <strong class="text-slate-900">(+63) 927 299 1833</strong> / <strong>(+63) 995 233 9423</strong></p>
                </div>
            </div>
        </div>

        {{-- Select Payment Method & Details --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Payment Method <span class="text-rose-500">*</span></label>
                <select name="method" x-model="form.payment_method" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                    <option value="gcash_maya">GCash / Maya</option>
                    <option value="bdo">BDO Bank Transfer</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Amount Paid (PHP) <span class="text-rose-500">*</span></label>
                <input type="number" name="amount" value="4000.00" min="1" step="0.01" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
            </div>
            <div>
                <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Reference No. / Ref ID</label>
                <input type="text" name="reference_no" placeholder="e.g. 100234589" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 uppercase">
            </div>
        </div>

        {{-- Proof of Payment Upload Dropzone --}}
        <div class="space-y-2">
            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600">Upload Proof of Payment / Receipt Photo <span class="text-rose-500">*</span></label>
            
            <div x-data="{ receiptPreview: null }" class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/50 p-4 text-center hover:border-emerald-400 transition">
                <input type="file" name="payment_receipt" id="payment_receipt_input" accept="image/jpeg,image/jpg,image/png,application/pdf"
                    @change="
                        const file = $event.target.files[0];
                        if (file) {
                            if (file.type.startsWith('image/')) {
                                const reader = new FileReader();
                                reader.onload = (e) => receiptPreview = e.target.result;
                                reader.readAsDataURL(file);
                            } else {
                                receiptPreview = 'pdf';
                            }
                        }
                    "
                    class="hidden"
                >

                <template x-if="!receiptPreview">
                    <label for="payment_receipt_input" class="cursor-pointer block py-3 space-y-2">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </div>
                        <div>
                            <span class="text-xs font-extrabold text-emerald-700 hover:underline">Click to upload payment receipt</span>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">JPG, PNG, or PDF up to 5MB</p>
                        </div>
                    </label>
                </template>

                <template x-if="receiptPreview">
                    <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/80 p-3 text-left">
                        <div class="flex items-center gap-3">
                            <template x-if="receiptPreview !== 'pdf'">
                                <img :src="receiptPreview" class="h-12 w-12 rounded-lg object-cover ring-1 ring-emerald-300">
                            </template>
                            <template x-if="receiptPreview === 'pdf'">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-rose-100 text-rose-600 font-black text-xs">PDF</div>
                            </template>
                            <div>
                                <span class="text-xs font-black text-emerald-950 block">Payment Receipt Attached</span>
                                <span class="text-[10px] font-bold text-emerald-700">Ready for final submission</span>
                            </div>
                        </div>
                        <label for="payment_receipt_input" class="text-xs font-extrabold text-emerald-700 hover:text-emerald-900 underline cursor-pointer">Change File</label>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Final Confirmation Checkbox --}}
    <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 space-y-3">
        <label class="flex items-start gap-3 cursor-pointer select-none">
            <input type="checkbox" name="agreed_final_confirmation" required class="mt-1 h-4 w-4 rounded border-amber-300 text-emerald-600 focus:ring-emerald-500">
            <span class="text-xs font-bold text-slate-800 leading-relaxed">
                I hereby certify that all information provided in this application, including attached student documents and proof of payment, are authentic, complete, and correct.
            </span>
        </label>
    </div>
</section>

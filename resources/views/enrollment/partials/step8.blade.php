<section class="space-y-6">
    {{-- Step 8 Heading --}}
    <div class="rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50/90 via-teal-50/50 to-white p-5 shadow-3xs">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900 uppercase tracking-wider">Step 8: Final Review & Double-Check</h3>
                <p class="text-xs font-semibold text-slate-500">Please review all submitted details, student documents, and your payment receipt before submitting.</p>
            </div>
        </div>
    </div>

    {{-- Summary Cards Grid --}}
    <div class="grid grid-cols-1 gap-5">
        {{-- Card 1: Student Profile & Modality --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">1. Student Details & Modality</h4>
                </div>
                <button type="button" @click="goToStep(2)" class="text-xs font-extrabold text-emerald-700 hover:underline cursor-pointer">Edit Student Info</button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Student Name</span>
                    <strong class="text-slate-900 block" x-text="`${form.first_name || ''} ${form.middle_name || ''} ${form.last_name || ''}`.trim() || 'N/A'"></strong>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Student Type & LRN</span>
                    <strong class="text-slate-900 block" x-text="`${form.student_type || 'N/A'} (LRN: ${form.lrn || 'N/A'})`"></strong>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Grade Level</span>
                    <strong class="text-emerald-800 block" x-text="form.grade_level || 'N/A'"></strong>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Learning Modality</span>
                    <strong class="text-emerald-700 block" x-text="form.learning_mode || 'N/A'"></strong>
                </div>
            </div>
        </div>

        {{-- Card 2: Contact & Parent Information --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">2. Parent & Contact Details</h4>
                </div>
                <button type="button" @click="goToStep(4)" class="text-xs font-extrabold text-blue-700 hover:underline cursor-pointer">Edit Parents Info</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Father's Name</span>
                    <strong class="text-slate-900 block" x-text="`${form.father_first_name || ''} ${form.father_last_name || ''}`.trim() || 'Not specified'"></strong>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Mother's Name</span>
                    <strong class="text-slate-900 block" x-text="`${form.mother_first_name || ''} ${form.mother_last_name || ''}`.trim() || 'Not specified'"></strong>
                </div>
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150">
                    <span class="text-[10px] font-black uppercase text-slate-400 block">Parent Contact Mobile</span>
                    <strong class="text-slate-900 block" x-text="`${form.parent_country_code || '+63'} ${form.parent_mobile || ''}`"></strong>
                </div>
            </div>
        </div>

        {{-- Card 3: Uploaded Student Documents --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">3. Uploaded Student Requirements</h4>
                </div>
                <button type="button" @click="goToStep(6)" class="text-xs font-extrabold text-indigo-700 hover:underline cursor-pointer">Manage Documents</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 flex items-center gap-2.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>
                        <strong class="text-xs font-bold text-slate-900 block">2x2 Student Photo</strong>
                        <span class="text-[10px] text-emerald-700 font-bold">Attached</span>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 flex items-center gap-2.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>
                        <strong class="text-xs font-bold text-slate-900 block">Birth Certificate</strong>
                        <span class="text-[10px] text-emerald-700 font-bold">Attached</span>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 flex items-center gap-2.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>
                        <strong class="text-xs font-bold text-slate-900 block">Report Card / Affidavit</strong>
                        <span class="text-[10px] text-emerald-700 font-bold">Attached</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4: Payment & Uploaded Receipt Image Preview --}}
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between border-b border-indigo-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-indigo-600"></span>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">4. Payment & Uploaded Receipt Summary</h4>
                </div>
                <button type="button" @click="goToStep(7)" class="text-xs font-extrabold text-indigo-700 hover:underline cursor-pointer">Change Receipt</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                <div class="space-y-2 text-xs">
                    <div class="bg-white p-3 rounded-xl border border-indigo-150 space-y-1">
                        <span class="text-[10px] font-black uppercase text-slate-400 block">Payment Method</span>
                        <strong class="text-indigo-900 text-sm block uppercase" x-text="form.payment_method === 'bdo' ? 'BDO Bank Deposit' : 'GCash / Maya'"></strong>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-indigo-150 space-y-1">
                        <span class="text-[10px] font-black uppercase text-slate-400 block">Amount Paid</span>
                        <strong class="text-emerald-700 text-sm block font-black" x-text="'PHP ' + (parseFloat(form.amount) || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></strong>
                    </div>
                </div>

                {{-- Uploaded Receipt Thumbnail Image Preview --}}
                <div class="bg-white p-3.5 rounded-xl border border-indigo-150 flex items-center gap-3">
                    <template x-if="paymentReceiptPreview && paymentReceiptPreview !== 'pdf'">
                        <img :src="paymentReceiptPreview" class="h-20 w-20 rounded-lg object-cover ring-2 ring-indigo-400 flex-shrink-0 shadow-sm">
                    </template>
                    <template x-if="paymentReceiptPreview === 'pdf'">
                        <div class="h-20 w-20 rounded-lg bg-rose-100 text-rose-600 font-black text-sm flex items-center justify-center flex-shrink-0">PDF</div>
                    </template>
                    <template x-if="!paymentReceiptPreview">
                        <div class="h-20 w-20 rounded-lg bg-amber-100 text-amber-700 font-bold text-xs flex items-center justify-center text-center p-2 flex-shrink-0">No receipt</div>
                    </template>

                    <div>
                        <strong class="text-xs font-black text-slate-900 block">Payment Receipt Attached</strong>
                        <span class="text-[11px] text-emerald-700 font-bold block mt-0.5">Verified & ready for submission</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Final Agreement Checkbox --}}
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5">
        <label class="flex items-start gap-3 cursor-pointer select-none">
            <input type="checkbox" name="agreed_final_confirmation" required class="mt-1 h-4 w-4 rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500">
            <span class="text-xs font-bold text-slate-800 leading-relaxed">
                I hereby certify that all information provided in this enrollment application, including attached student documents and proof of payment, are authentic, complete, and accurate.
            </span>
        </label>
    </div>
</section>

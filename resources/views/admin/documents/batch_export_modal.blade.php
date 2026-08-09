<div id="batchExportModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 relative">
        <button type="button" onclick="closeBatchExportModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 bg-slate-100 rounded-full w-8 h-8 flex items-center justify-center font-bold">✕</button>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-extrabold text-slate-800">Batch Document Export</h3>
                <p class="text-xs text-slate-500">Generate high-volume student PDFs or DOCXs into a single ZIP</p>
            </div>
        </div>

        <form id="batchExportForm" onsubmit="startBatchExport(event)">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Export Format</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-3 border rounded-xl cursor-pointer hover:border-emerald-500 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <input type="radio" name="format" value="pdf" checked class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-bold text-slate-700">PDF (mPDF A4)</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 border rounded-xl cursor-pointer hover:border-emerald-500 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <input type="radio" name="format" value="docx" class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-bold text-slate-700">DOCX (MS Word)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Filter by Grade Level</label>
                    <select name="grade_level" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All Grade Levels</option>
                        <option value="Kindergarten">Kindergarten</option>
                        <option value="Grade 1">Grade 1</option>
                        <option value="Grade 2">Grade 2</option>
                        <option value="Grade 3">Grade 3</option>
                        <option value="Grade 4">Grade 4</option>
                        <option value="Grade 5">Grade 5</option>
                        <option value="Grade 6">Grade 6</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">School Year</label>
                    <input type="text" name="school_year" value="2026-2027" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Progress Bar Container -->
            <div id="exportProgressContainer" class="hidden mt-5 p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between text-xs font-bold text-slate-700">
                    <span id="exportProgressMsg">Initializing generation...</span>
                    <span id="exportProgressPct">0%</span>
                </div>
                <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
                    <div id="exportProgressBar" class="bg-emerald-600 h-full w-0 transition-all duration-300"></div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeBatchExportModal()" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                <button type="submit" id="btnStartExport" class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-lg shadow-emerald-600/20">
                    Generate Batch ZIP
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openBatchExportModal() {
        document.getElementById('batchExportModal').classList.remove('hidden');
    }
    function closeBatchExportModal() {
        document.getElementById('batchExportModal').classList.add('hidden');
    }

    async function startBatchExport(e) {
        e.preventDefault();
        const form = document.getElementById('batchExportForm');
        const formData = new FormData(form);

        const btn = document.getElementById('btnStartExport');
        const progressContainer = document.getElementById('exportProgressContainer');
        const progressMsg = document.getElementById('exportProgressMsg');
        const progressPct = document.getElementById('exportProgressPct');
        const progressBar = document.getElementById('exportProgressBar');

        btn.disabled = true;
        btn.classList.add('opacity-50');
        progressContainer.classList.remove('hidden');

        try {
            const res = await fetch("{{ route('admin.documents.batch-export') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await res.json();

            if (!data.success) {
                alert(data.message || 'Export failed to queue');
                btn.disabled = false;
                btn.classList.remove('opacity-50');
                return;
            }

            // Poll progress
            const batchId = data.batch_id;
            const pollInterval = setInterval(async () => {
                const statusRes = await fetch(`/admin/documents/batch-status/${batchId}`);
                const statusData = await statusRes.json();

                progressPct.textContent = `${statusData.progress || 0}%`;
                progressBar.style.width = `${statusData.progress || 0}%`;
                progressMsg.textContent = statusData.message || 'Processing...';

                if (statusData.status === 'completed') {
                    clearInterval(pollInterval);
                    progressMsg.textContent = 'Done! Downloading ZIP file...';
                    window.location.href = statusData.download_url;

                    setTimeout(() => {
                        btn.disabled = false;
                        btn.classList.remove('opacity-50');
                        closeBatchExportModal();
                    }, 2000);
                } else if (statusData.status === 'failed') {
                    clearInterval(pollInterval);
                    alert(statusData.message || 'Batch export failed');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50');
                }
            }, 1200);

        } catch (err) {
            alert('An error occurred during batch export');
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }
</script>

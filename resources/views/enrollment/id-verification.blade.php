<x-guest-layout :showLoader="true">
    @push('styles')
        <style>
            /* Custom Scrollbar for modern feel */
            ::-webkit-scrollbar {
                width: 6px;
            }
            ::-webkit-scrollbar-track {
                background: rgba(2, 44, 34, 0.05);
            }
            ::-webkit-scrollbar-thumb {
                background: rgba(5, 150, 105, 0.3);
                border-radius: 10px;
            }
            ::-webkit-scrollbar-thumb:hover {
                background: rgba(5, 150, 105, 0.5);
            }

            /* Glassmorphism utility */
            .glass-panel {
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.4);
                box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05),
                            inset 0 1px 0 rgba(255, 255, 255, 0.6);
            }

            /* Glow accents */
            .glow-bg-1 {
                filter: blur(120px);
                background: radial-gradient(circle, rgba(5, 150, 105, 0.25) 0%, rgba(220, 252, 231, 0.1) 70%);
            }
            .glow-bg-2 {
                filter: blur(100px);
                background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, rgba(220, 252, 231, 0.05) 60%);
            }

            /* Holographic overlay shimmer */
            .holo-overlay {
                background: linear-gradient(135deg, 
                    rgba(255,255,255,0) 0%, 
                    rgba(255,255,255,0) 40%, 
                    rgba(255, 255, 255, 0.3) 50%, 
                    rgba(255,255,255,0) 60%, 
                    rgba(255,255,255,0) 100%
                );
                background-size: 250% 250%;
                background-position: 0% 0%;
                transition: background-position 0.6s ease;
            }
            .holo-card:hover .holo-overlay {
                background-position: 100% 100%;
            }

            /* 3D Card Flip styling */
            .perspective-1000 {
                perspective: 1200px;
            }
            .card-inner {
                transform-style: preserve-3d;
                transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            .card-front, .card-back {
                backface-visibility: hidden;
                -webkit-backface-visibility: hidden;
            }
            .card-back {
                transform: rotateY(180deg);
            }
            .is-flipped {
                transform: rotateY(180deg);
            }

            /* Custom checkbox & input glow focus */
            .input-focus-glow:focus {
                border-color: rgba(5, 150, 105, 0.8) !important;
                box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.15) !important;
            }

            /* Draw animation for SVG checkmark */
            .svg-success-path {
                stroke-dasharray: 100;
                stroke-dashoffset: 100;
                animation: drawCheck 0.6s ease-out forwards 0.2s;
            }
            .svg-success-circle {
                stroke-dasharray: 150;
                stroke-dashoffset: 150;
                animation: drawCircle 0.6s ease-out forwards;
            }

            @keyframes drawCheck {
                to { stroke-dashoffset: 0; }
            }
            @keyframes drawCircle {
                to { stroke-dashoffset: 0; }
            }
        </style>
    @endpush

    <div class="relative min-h-screen bg-slate-50 flex flex-col justify-between overflow-x-hidden font-sans"
         x-data="{
             student_id: '',
             full_name: '',
             grade_level: '',
             school_year: '2026-2027',
             loading: false,
             errorMsg: '',
             success: false,
             result: null,
             isFlipped: false,
             
             submitVerification() {
                 if (!this.student_id || !this.full_name || !this.grade_level || !this.school_year) {
                     this.errorMsg = 'All fields are required.';
                     return;
                 }
                 
                 this.loading = true;
                 this.errorMsg = '';
                 this.success = false;
                 this.result = null;
                 this.isFlipped = false;
                 
                 fetch('{{ route('id-verification.verify') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content')
                     },
                     body: JSON.stringify({
                         student_id: this.student_id,
                         full_name: this.full_name,
                         grade_level: this.grade_level,
                         school_year: this.school_year
                     })
                 })
                 .then(async response => {
                     const data = await response.json();
                     this.loading = false;
                     if (response.ok && data.success) {
                         this.result = data;
                         this.success = true;
                     } else {
                         this.errorMsg = data.message || 'An error occurred during verification.';
                     }
                 })
                 .catch(err => {
                     this.loading = false;
                     this.errorMsg = 'Unable to connect. Please check your internet connection.';
                 });
             }
         }">

        <!-- Radial glow spots in background -->
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] glow-bg-1 pointer-events-none rounded-full"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] glow-bg-2 pointer-events-none rounded-full"></div>

        <!-- Header -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between relative z-10">
            <a href="/" class="flex items-center gap-3">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="h-12 w-auto drop-shadow-md hover:scale-105 transition-transform duration-300">
                <div class="hidden sm:block">
                    <span class="font-bold text-slate-800 text-lg tracking-wide block leading-none">AL-MUNAWWARAH</span>
                    <span class="text-emerald-600 font-semibold text-xs tracking-wider uppercase">International School</span>
                </div>
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-slate-600 hover:text-emerald-600 font-semibold text-sm transition-colors">
                    Portal Login
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow flex items-center justify-center px-4 py-8 relative z-10">
            <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Form Card -->
                <div class="lg:col-span-6 w-full glass-panel rounded-3xl p-6 sm:p-8 transition-all duration-300 hover:shadow-2xl">
                    <div class="mb-6">
                        <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 font-bold text-xs rounded-full uppercase tracking-wider mb-2">
                            ID Verification Portal
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">Verify Student ID Card</h2>
                        <p class="text-slate-500 text-sm mt-1">
                            Ensure authenticity of AMIS IDs or temporary registration credentials.
                        </p>
                    </div>

                    <!-- Error Alert box -->
                    <div x-show="errorMsg" x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mb-6 px-4 py-3 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl text-sm flex items-start gap-3 shadow-sm">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span x-text="errorMsg" class="font-medium leading-relaxed"></span>
                    </div>

                    <!-- Verification Form -->
                    <form @submit.prevent="submitVerification()" class="space-y-5">
                        <div>
                            <label for="student_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Student ID or Temporary ID</label>
                            <div class="relative">
                                <input type="text" id="student_id" x-model="student_id" placeholder="AMIS-2026-000123 or TEMP-2026-000123" required
                                       class="w-full bg-slate-50/50 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-800 placeholder-slate-400 font-medium input-focus-glow transition-all duration-200">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="full_name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Student Full Name</label>
                            <div class="relative">
                                <input type="text" id="full_name" x-model="full_name" placeholder="First Name Last Name" required
                                       class="w-full bg-slate-50/50 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-800 placeholder-slate-400 font-medium input-focus-glow transition-all duration-200">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="grade_level" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Grade Level</label>
                                <select id="grade_level" x-model="grade_level" required
                                        class="w-full bg-slate-50/50 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-800 font-medium input-focus-glow transition-all duration-200 appearance-none">
                                    <option value="" disabled selected>Select Grade</option>
                                    @foreach($gradeLevels as $g)
                                        <option value="{{ $g }}">{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="school_year" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">School Year</label>
                                <select id="school_year" x-model="school_year" required
                                        class="w-full bg-slate-50/50 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-800 font-medium input-focus-glow transition-all duration-200">
                                    <option value="2026-2027" selected>2026-2027</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" :disabled="loading"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 px-6 rounded-2xl shadow-lg hover:shadow-emerald-600/20 active:scale-[0.98] transition-all duration-150 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75 disabled:cursor-wait">
                            <template x-if="loading">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="loading ? 'Verifying Details...' : 'Submit Verification'"></span>
                        </button>
                    </form>

                    <!-- Separate Registration CTA -->
                    <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col items-center justify-center text-center">
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-3">New Student?</span>
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center px-6 py-3 border border-emerald-200 text-emerald-700 font-bold text-sm rounded-xl hover:bg-emerald-50 active:scale-98 transition-all duration-200 w-full sm:w-auto">
                            Don't have AMIS ID yet? Proceed to Register
                        </a>
                    </div>
                </div>

                <!-- Right: ID Card Preview -->
                <div class="lg:col-span-6 flex flex-col items-center justify-center min-h-[500px]">
                    
                    <!-- Welcome Placeholder State -->
                    <div x-show="!success && !loading" class="text-center p-6 glass-panel rounded-3xl max-w-sm flex flex-col items-center justify-center shadow-lg"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        <div class="h-16 w-16 bg-emerald-50 rounded-full flex items-center justify-center mb-4 text-emerald-600">
                            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Verification Result</h3>
                        <p class="text-slate-400 text-xs mt-2 leading-relaxed">
                            Input the student's ID, Name, and Grade on the left form to view their digital identity card and verification status.
                        </p>
                    </div>

                    <!-- Spinner Loader State -->
                    <div x-show="loading" class="flex flex-col items-center justify-center">
                        <div class="relative w-16 h-16">
                            <div class="absolute inset-0 rounded-full border-4 border-emerald-100"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-emerald-600 border-t-transparent animate-spin"></div>
                        </div>
                        <span class="text-slate-400 font-bold text-xs mt-4 uppercase tracking-widest animate-pulse">Running Verification...</span>
                    </div>

                    <!-- Verified Success Panel -->
                    <div x-show="success && result" class="w-full flex flex-col items-center gap-6"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-8"
                         x-transition:enter-end="opacity-100 translate-y-0">
                        
                        <!-- Success Checkmark and status details -->
                        <div class="w-full max-w-sm glass-panel rounded-2xl p-4 flex items-center gap-4 shadow-md relative overflow-hidden">
                            <div class="h-12 w-12 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0 text-emerald-600">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline class="svg-success-path" points="20 6 9 17 4 12"></polyline>
                                    <circle class="svg-success-circle" cx="12" cy="12" r="10"></circle>
                                </svg>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-extrabold text-slate-800" x-text="result ? (result.is_temp ? 'Valid Temporary ID' : 'Valid AMIS ID') : ''"></h4>
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="h-2 w-2 rounded-full"
                                          :class="result?.status === 'Officially Enrolled' ? 'bg-emerald-500' : (result?.status === 'Pending' ? 'bg-amber-500' : 'bg-blue-400')"></span>
                                    <span class="text-xs font-bold uppercase tracking-wider" 
                                          :class="result?.status === 'Officially Enrolled' ? 'text-emerald-700' : (result?.status === 'Pending' ? 'text-amber-700' : 'text-blue-700')"
                                          x-text="result?.status"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 3D Flipping Card Container -->
                        <div class="perspective-1000 w-full max-w-[340px] h-[520px] cursor-pointer"
                             @click="isFlipped = !isFlipped">
                            
                            <div class="card-inner w-full h-full relative"
                                 :class="isFlipped ? 'is-flipped' : ''">
                                
                                <!-- FRONT OF THE ID CARD -->
                                <div class="card-front holo-card absolute inset-0 w-full h-full rounded-[24px] bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-900 shadow-2xl p-5 border border-emerald-600/30 text-white flex flex-col justify-between overflow-hidden">
                                    <div class="absolute inset-0 holo-overlay opacity-30 mix-blend-overlay"></div>
                                    
                                    <!-- Header -->
                                    <div class="flex items-center gap-3 border-b border-emerald-500/30 pb-3 relative z-10">
                                        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="h-10 w-auto bg-white/10 p-1 rounded-lg">
                                        <div>
                                            <span class="font-bold text-[10px] tracking-wider block uppercase opacity-85 leading-tight">AL-MUNAWWARAH</span>
                                            <span class="text-xs font-extrabold tracking-wider block text-emerald-300 uppercase leading-none">International School</span>
                                        </div>
                                    </div>

                                    <!-- Student Photo & ID Info -->
                                    <div class="flex flex-col items-center my-4 relative z-10">
                                        <div class="h-[140px] w-[140px] rounded-2xl overflow-hidden border-3 border-emerald-400/40 shadow-inner bg-slate-900/50 flex items-center justify-center">
                                            <template x-if="result && result.photo_url">
                                                <img :src="result.photo_url" alt="Student Photo" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="!result || !result.photo_url">
                                                <svg class="h-20 w-20 text-emerald-400/40" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                                                </svg>
                                            </template>
                                        </div>
                                        
                                        <!-- Holo Badge Seal -->
                                        <div class="absolute top-[100px] right-[40px] h-10 w-10 rounded-full bg-gradient-to-tr from-cyan-400 via-pink-400 to-yellow-300 opacity-60 border border-white/20 flex items-center justify-center shadow-lg mix-blend-screen animate-pulse">
                                            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="Seal" class="h-6 w-auto opacity-75">
                                        </div>
                                    </div>

                                    <!-- Student Details -->
                                    <div class="text-center relative z-10 flex-grow flex flex-col justify-center">
                                        <span class="text-[9px] uppercase tracking-widest text-emerald-300 font-bold block mb-0.5">Student Name</span>
                                        <div class="px-2">
                                            <h3 class="text-lg font-extrabold tracking-tight truncate leading-tight" x-text="result?.full_name"></h3>
                                        </div>
                                        
                                        <div class="mt-3 grid grid-cols-2 gap-2 border-t border-emerald-500/20 pt-2.5 max-w-[280px] mx-auto w-full">
                                            <div>
                                                <span class="text-[8px] uppercase tracking-wider text-emerald-300 font-bold block opacity-75">Grade Level</span>
                                                <span class="text-xs font-bold" x-text="result?.grade_level"></span>
                                            </div>
                                            <div>
                                                <span class="text-[8px] uppercase tracking-wider text-emerald-300 font-bold block opacity-75">School Year</span>
                                                <span class="text-xs font-bold" x-text="result?.school_year"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer & QR Code -->
                                    <div class="flex items-end justify-between border-t border-emerald-500/30 pt-3 relative z-10 mt-auto">
                                        <div class="text-left">
                                            <span class="text-[8px] uppercase tracking-widest text-emerald-300 font-bold block opacity-85">Student Number</span>
                                            <span class="text-sm font-extrabold tracking-wider text-white" x-text="result?.student_id"></span>
                                        </div>
                                        <div class="bg-white p-1 rounded-lg shadow-md border border-white/10 hover:scale-105 transition-transform duration-200 flex-shrink-0">
                                            <img :src="result?.qr_code" alt="QR Verification" class="h-10 w-10">
                                        </div>
                                    </div>
                                </div>

                                <!-- BACK OF THE ID CARD -->
                                <div class="card-back absolute inset-0 w-full h-full rounded-[24px] bg-gradient-to-br from-slate-900 to-slate-800 shadow-2xl p-6 border border-slate-700/50 text-white flex flex-col justify-between overflow-hidden">
                                    
                                    <!-- Header Info -->
                                    <div class="text-center border-b border-slate-700/60 pb-3">
                                        <h4 class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest">Student Information & Security</h4>
                                    </div>

                                    <!-- Back Card Details -->
                                    <div class="space-y-4 my-4 flex-grow flex flex-col justify-center">
                                        <div>
                                            <span class="text-[8px] uppercase tracking-widest text-slate-400 font-bold block mb-0.5">Parent / Guardian</span>
                                            <span class="text-xs font-semibold block text-slate-200" x-text="result?.parent_name"></span>
                                        </div>

                                        <div>
                                            <span class="text-[8px] uppercase tracking-widest text-slate-400 font-bold block mb-0.5">Home Address</span>
                                            <span class="text-xs font-semibold block text-slate-200 leading-relaxed max-w-[280px]" x-text="result?.address"></span>
                                        </div>

                                        <div class="bg-slate-900/40 border border-slate-800 p-2.5 rounded-xl text-[9px] text-slate-400 leading-relaxed text-center">
                                            This card is non-transferable and must be worn at all times while on school premises. Loss must be reported to the registrar office immediately.
                                        </div>
                                    </div>

                                    <!-- Barcode & Contacts -->
                                    <div class="border-t border-slate-700/60 pt-4 flex flex-col items-center gap-3">
                                        <!-- Simulated Barcode -->
                                        <div class="bg-white p-2 rounded-md w-full flex items-center justify-center">
                                            <div class="flex items-stretch justify-center h-10 w-full bg-slate-900 max-w-[200px]" style="background: repeating-linear-gradient(90deg, #0f172a 0px, #0f172a 2px, #ffffff 2px, #ffffff 5px, #0f172a 5px, #0f172a 7px);"></div>
                                        </div>
                                        <div class="text-center text-[8px] text-slate-400">
                                            <span>registrar@amis.edu.ph</span>
                                            <span class="mx-1.5">•</span>
                                            <span>+63 900 000 0000</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Manual Flip Action Helper -->
                        <button @click="isFlipped = !isFlipped"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 hover:border-slate-300 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 transition-all duration-200 cursor-pointer shadow-sm">
                            <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                            </svg>
                            <span>Click to Flip Card</span>
                        </button>
                    </div>

                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-6 border-t border-slate-200/50 flex flex-col sm:flex-row items-center justify-between gap-4 relative z-10 text-xs text-slate-400 font-medium">
            <span>© 2026 Al-Munawwarah International School. All rights reserved.</span>
            <div class="flex items-center gap-4">
                <a href="#" class="hover:text-emerald-600 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-emerald-600 transition-colors">Terms of Service</a>
                <a href="#" class="hover:text-emerald-600 transition-colors">Contact Support</a>
            </div>
        </footer>

    </div>
</x-guest-layout>

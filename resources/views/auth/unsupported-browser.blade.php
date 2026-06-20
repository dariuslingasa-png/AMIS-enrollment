<x-guest-layout :show-loader="false">
<div class="min-h-screen flex flex-col items-center justify-center bg-slate-50 px-4 py-8 md:py-16" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-3xl shadow-2xl p-6 md:p-8 space-y-6">
        
        <!-- School Logo & Branding -->
        <div class="flex flex-col items-center text-center">
            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="h-16 w-16 mb-3">
            <h2 class="auth-hero-arabic text-xl font-bold text-slate-800" lang="ar" dir="rtl" style="font-family: 'Amiri', serif;">المدرسة المنورة الإسلامية</h2>
            <p class="auth-hero-school text-[10px] font-black tracking-wider text-slate-500" style="font-family: 'Tajawal', sans-serif;">AL MUNAWWARA ISLAMIC SCHOOL</p>
        </div>

        <div class="border-t border-slate-100 my-4"></div>

        <!-- Warn Header & Message -->
        <div class="space-y-4">
            <div class="flex items-center justify-center w-12 h-12 bg-amber-50 rounded-2xl border border-amber-250 mx-auto">
                <svg class="text-amber-600 w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                </svg>
            </div>
            
            <div class="text-center space-y-2">
                <h3 class="text-base font-extrabold text-slate-900 uppercase tracking-wide">Google Sign-in Blocked</h3>
                <p class="text-xs leading-relaxed text-slate-600">
                    Google Sign-In is not supported inside Messenger or Facebook browser. Please open this page in Safari, Chrome, or your device's default browser.
                </p>
            </div>
        </div>

        <!-- Dynamic Action Button -->
        <div class="space-y-3">
            @if ($isAndroid)
                <a href="{{ $intentUrl }}" class="flex items-center justify-center gap-2 w-full bg-indigo-700 hover:bg-indigo-600 active:scale-[0.98] text-white font-bold text-sm py-3 px-4 rounded-xl transition-all duration-150 shadow-md shadow-indigo-200">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2500/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path>
                    </svg>
                    <span>Open in Browser</span>
                </a>
            @else
                <div x-data="{ 
                    copied: false,
                    copyLink() {
                        navigator.clipboard.writeText('{{ $portalUrl }}');
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    }
                }">
                    <button @click="copyLink()" class="flex items-center justify-center gap-2 w-full bg-indigo-700 hover:bg-indigo-600 active:scale-[0.98] text-white font-bold text-sm py-3 px-4 rounded-xl transition-all duration-150 shadow-md shadow-indigo-250 cursor-pointer">
                        <svg x-show="!copied" class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H5.25m10.5 9.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM10.5 7.875A3.375 3.375 0 0113.875 4.5h3.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-3.375A3.375 3.375 0 0110.5 11.25V7.875z"></path>
                        </svg>
                        <svg x-show="copied" class="w-4.5 h-4.5 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
                        </svg>
                        <span x-text="copied ? 'Link Copied!' : 'Copy Portal Link'"></span>
                    </button>
                </div>
            @endif
        </div>

        <!-- Instruction Tabs -->
        <div class="space-y-4" x-data="{ tab: '{{ $isAndroid ? 'android' : 'ios' }}' }">
            <div class="flex border-b border-slate-100">
                <button @click="tab = 'ios'" :class="tab === 'ios' ? 'border-indigo-600 text-indigo-600 font-extrabold' : 'border-transparent text-slate-400 font-medium'" class="flex-1 text-center py-2.5 text-xs border-b-2 transition-all cursor-pointer">
                    iPhone & iPad
                </button>
                <button @click="tab = 'android'" :class="tab === 'android' ? 'border-indigo-600 text-indigo-600 font-extrabold' : 'border-transparent text-slate-400 font-medium'" class="flex-1 text-center py-2.5 text-xs border-b-2 transition-all cursor-pointer">
                    Android Devices
                </button>
            </div>

            <!-- iOS Instructions -->
            <div x-show="tab === 'ios'" class="space-y-3" x-transition>
                <div class="bg-slate-50 border border-slate-150 rounded-2xl p-4 space-y-3.5 text-xs text-slate-700 leading-normal">
                    <div class="flex items-start gap-3">
                        <span class="flex items-center justify-center w-5.5 h-5.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-600 font-bold shrink-0">1</span>
                        <p class="mt-0.5">Tap the **`...`** (three dots) or **Share** icon at the top right or bottom right corner of the screen.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex items-center justify-center w-5.5 h-5.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-600 font-bold shrink-0">2</span>
                        <p class="mt-0.5">Select **"Open in Safari"** or **"Open in System Browser"** to launch the portal.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex items-center justify-center w-5.5 h-5.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-600 font-bold shrink-0">3</span>
                        <p class="mt-0.5">Alternatively, copy the portal link and paste it directly into your Safari app.</p>
                    </div>
                </div>
            </div>

            <!-- Android Instructions -->
            <div x-show="tab === 'android'" class="space-y-3" x-transition style="display:none;">
                <div class="bg-slate-50 border border-slate-150 rounded-2xl p-4 space-y-3.5 text-xs text-slate-700 leading-normal">
                    <div class="flex items-start gap-3">
                        <span class="flex items-center justify-center w-5.5 h-5.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-600 font-bold shrink-0">1</span>
                        <p class="mt-0.5">Tap the **Open in Browser** button above to automatically open Chrome or your default browser.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex items-center justify-center w-5.5 h-5.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-600 font-bold shrink-0">2</span>
                        <p class="mt-0.5">If it does not launch automatically, tap the **`...`** (three dots) menu in the top-right corner of the Messenger/Facebook app.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex items-center justify-center w-5.5 h-5.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-600 font-bold shrink-0">3</span>
                        <p class="mt-0.5">Select **"Open in Chrome"** or **"Open in Browser"** to complete your enrollment registration.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 my-4"></div>

        <!-- Back to Login option (standard login is allowed) -->
        <div class="text-center">
            <a href="{{ route('login') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-500 hover:underline">
                &larr; Back to Email login
            </a>
        </div>
    </div>
</div>
</x-guest-layout>

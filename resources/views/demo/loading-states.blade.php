<x-guest-layout>
    <div class="min-h-screen bg-gray-50 py-8" x-data="{ 
        showDashboard: false, 
        showTable: false, 
        showReports: false,
        formLoading: false
    }">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Loading States Demo</h1>
                <p class="text-gray-600">Demonstration of all loading states in the AMIS system</p>
            </div>

            <!-- Controls -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Controls</h2>
                <div class="flex flex-wrap gap-4">
                    <button @click="showDashboard = !showDashboard" class="btn-primary">
                        Toggle Dashboard Skeleton
                    </button>
                    <button @click="showTable = !showTable" class="btn-primary">
                        Toggle Student List Skeleton
                    </button>
                    <button @click="showReports = !showReports" class="btn-primary">
                        Toggle Reports Skeleton
                    </button>
                    <button @click="formLoading = !formLoading" class="btn-secondary">
                        Toggle Form Spinner
                    </button>
                </div>
            </div>

            <!-- Spinner Examples -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Spinner Components</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <h3 class="font-medium mb-3">Small Spinner</h3>
                        <x-spinner size="sm" />
                    </div>
                    <div class="text-center">
                        <h3 class="font-medium mb-3">Default Spinner</h3>
                        <x-spinner />
                    </div>
                    <div class="text-center">
                        <h3 class="font-medium mb-3">Large Spinner</h3>
                        <x-spinner size="lg" />
                    </div>
                </div>
                
                <!-- Button Examples -->
                <div class="mt-8">
                    <h3 class="font-medium mb-4">Button Loading States</h3>
                    <div class="flex gap-4">
                        <button class="btn-primary" :class="{ 'btn-loading': formLoading }" :disabled="formLoading">
                            <x-spinner x-show="formLoading" color="white" size="sm" />
                            <span x-show="!formLoading">Submit Application</span>
                            <span x-show="formLoading" x-cloak>Submitting...</span>
                        </button>
                        
                        <button class="btn-secondary">
                            Sign In (No Loading)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Skeleton -->
            <div class="mb-8" x-show="showDashboard" x-cloak>
                <h2 class="text-xl font-semibold mb-4">Dashboard Loading Skeleton</h2>
                <x-skeleton-dashboard />
            </div>

            <!-- Student List Skeleton -->
            <div class="mb-8" x-show="showTable" x-cloak>
                <h2 class="text-xl font-semibold mb-4">Student List Loading Skeleton</h2>
                <x-skeleton-table :rows="8" />
            </div>

            <!-- Reports Skeleton -->
            <div class="mb-8" x-show="showReports" x-cloak>
                <h2 class="text-xl font-semibold mb-4">Reports Loading Skeleton</h2>
                <x-skeleton-reports />
            </div>

            <!-- Back to Dashboard -->
            <div class="text-center">
                <a href="{{ route('dashboard') }}" class="btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
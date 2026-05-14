@props(['title', 'description' => null])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    @if ($title)
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $title }}</h3>
            @if ($description)
                <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $description }}</p>
            @endif
        </div>
    @endif
    
    <div class="px-4 py-5 sm:p-6">
        {{ $slot }}
    </div>
</div>

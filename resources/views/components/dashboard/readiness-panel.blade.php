@props([
    'title',
    'tone' => 'green',
    'items' => [],
])

<div class="guidance-panel">
    <div class="guidance-panel-title">
        <span class="guidance-icon guidance-icon-{{ $tone }}">
            @if ($tone === 'blue')
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                </svg>
            @else
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            @endif
        </span>
        {{ $title }}
    </div>

    <ul class="guidance-list">
        @foreach ($items as $item)
            <li>
                <span class="guidance-check {{ $item['done'] ? 'done' : '' }}">
                    @if ($item['done'])
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    @endif
                </span>
                <span>{{ $item['label'] }}</span>
            </li>
        @endforeach
    </ul>
</div>

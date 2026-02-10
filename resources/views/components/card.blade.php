@props([
    'title' => '',
    'icon' => '',
    'iconColor' => 'text-green-200',
    'headerColor' => 'text-green-200',
    'subtitle' => '',
    'padded' => true,
    'spacing' => true,
])

<div {{ $attributes->class(['figma-card']) }}>
    {{-- Header --}}
    @if ($title || $icon)
        <div class="figma-card-header {{ $headerColor }}">
            @if ($icon)
                <i class="bi bi-{{ $icon }} mr-2 {{ $iconColor }}"></i>
            @endif
            {{ $title }}
        </div>
    @endif

    {{-- Content --}}
    <div class="{{ $padded ? 'p-6' : '' }} {{ $spacing ? 'space-y-6' : '' }}">
        {{-- Subtitle (optional) --}}
        @if ($subtitle)
            <p class="text-slate-400 text-sm mb-4">{{ $subtitle }}</p>
        @endif

        {{-- Slot content --}}
        {{ $slot }}
    </div>
</div>
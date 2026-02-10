@props(['title', 'icon' => '', 'description' => ''])

<div class="figma-card">
    <h2 class="figma-card-header text-green-200">
        @if($icon) <i class="{{ $icon }} mr-2"></i> @endif
        {{ $title }}
    </h2>
    <div class="p-6 space-y-6">
        {{ $slot }}
    </div>
</div>
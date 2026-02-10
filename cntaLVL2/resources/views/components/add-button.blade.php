@props(['label', 'event', 'icon' => 'bi-plus-circle', 'canAdd' => true, 'maxItems' => null])

@if($canAdd)
    <button type="button" wire:click="{{ $event }}" 
        class="text-sm font-semibold text-blue-400 hover:text-blue-300 flex items-center gap-2">
        <i class="bi {{ $icon }}"></i> {{ $label }}
    </button>
@endif
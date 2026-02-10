@props(['index', 'canDelete' => true, 'canSetPrimary' => true, 'primaryName' => '', 'deleteEvent' => ''])

<div class="relative p-5 rounded-md bg-slate-700/30 border border-slate-600" wire:key="item-{{ $index }}">
    {{-- Controls --}}
    <div class="flex items-center justify-end gap-4 pb-2 absolute top-4 right-4">
        @if($canSetPrimary && $primaryName)
            <label for="primary_{{ $index }}" class="flex items-center cursor-pointer gap-2">
                <span class="text-sm font-medium text-white">Preferable</span>
                <input type="radio" id="primary_{{ $index }}" name="{{ $primaryName }}" 
                    wire:click="{{ $primaryName }}_set_primary({{ $index }})" class="form-radio-figma">
            </label>
        @endif
        @if($canDelete) 
            <button type="button" wire:click="{{ $deleteEvent }}({{ $index }})" class="text-slate-500 hover:text-red-500 transition-colors">
                <i class="bi bi-trash-fill"></i>
            </button>
        @endif
    </div>

    {{ $slot }}
</div>
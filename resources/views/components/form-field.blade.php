@props(['label' => '', 'name', 'type' => 'text', 'value' => '', 'placeholder' => '', 'required' => false, 'icon' => '', 'disabled' => false, 'options' => []])

<div {{ $attributes->merge(['class' => '']) }}>
    @if($label)
        <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
            {{ $label }}
            @if($required) <span class="text-red-400">*</span> @endif
        </label>
    @endif

    @if($type === 'select')
        <select @disabled($disabled) wire:model.blur="{{ $name }}" 
            class="form-select-figma w-full text-sm @error($name) border-red-500 ring-red-500 @enderror">
            <option value="">Select...</option>
            @foreach($options as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea @disabled($disabled) wire:model.blur="{{ $name }}" 
            placeholder="{{ $placeholder }}" rows="{{ $attributes->get('rows', 3) }}"
            class="form-input-figma w-full text-sm @error($name) border-red-500 ring-red-500 @enderror"></textarea>
    @elseif($type === 'date' || $type === 'email' || $type === 'tel' || $type === 'url' || $type === 'number')
        <input type="{{ $type }}" @disabled($disabled) wire:model.blur="{{ $name }}" 
            placeholder="{{ $placeholder }}" 
            @if($type === 'number') min="{{ $attributes->get('min', '') }}" max="{{ $attributes->get('max', '') }}" @endif
            class="form-input-figma w-full text-sm @error($name) border-red-500 ring-red-500 @enderror">
    @else
        <div class="relative">
            @if($icon) <i class="{{ $icon }} text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i> @endif
            <input type="{{ $type }}" @disabled($disabled) wire:model.blur="{{ $name }}" 
                placeholder="{{ $placeholder }}" 
                class="form-input-figma w-full text-sm @if($icon) pl-10 @endif @error($name) border-red-500 ring-red-500 @enderror">
        </div>
    @endif

    @error($name) <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
</div>
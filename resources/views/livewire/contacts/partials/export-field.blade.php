<div wire:key="col-{{ $key }}" class="flex flex-col gap-2">
    
    <!-- Checkbox Row - SIMPLIFIED -->
    <label class="flex items-center gap-3 p-2.5 rounded-lg border cursor-pointer transition-all duration-150
        {{ $col['checked']
            ? 'bg-slate-700/50 border-slate-600'
            : 'bg-slate-900/40 border-slate-700/50 hover:bg-slate-700/40' }}">

        <input type="checkbox"
            wire:model.live="columns.{{ $key }}.checked"
            class="w-4 h-4 rounded text-blue-500 bg-slate-700 border-slate-600 focus:ring-blue-500 focus:ring-offset-slate-800 cursor-pointer">

        <span class="text-xs font-medium text-slate-300">
            {{ $col['label'] }}
        </span>
    </label>

    <!-- Filter Dropdown (only for specific fields) -->
    @if($col['checked'] && isset($col['filter_value']))
        <div class="ml-7 pl-2 border-l-2 border-green-500/30">
            @if($key === 'gender')
                <select wire:model.live="columns.{{ $key }}.filter_value"
                    class="form-select-figma w-full text-xs">
                    <option value="all">All Genders</option>
                    @foreach($genderOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            @elseif($key === 'address_type')
                <select wire:model.live="columns.{{ $key }}.filter_value"
                    class="form-select-figma w-full text-xs">
                    <option value="all">All Address Types</option>
                    @foreach($addressTypeOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            @elseif($key === 'bank_name')
                <select wire:model.live="columns.{{ $key }}.filter_value"
                    class="form-select-figma w-full text-xs">
                    <option value="">All Banks</option>
                    @foreach($bankOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            @elseif($key === 'document_type')
                <select wire:model.live="columns.{{ $key }}.filter_value"
                    class="form-select-figma w-full text-xs">
                    <option value="">All Document Types</option>
                    @foreach($documentTypeOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    @endif

</div>
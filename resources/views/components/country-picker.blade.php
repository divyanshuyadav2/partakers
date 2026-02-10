@php
    $fieldType = $type ?? 'phones';
    $defaultCode = $phone['Cutr_Code'] ?? ($allCountries->first()->Phon_Code ?? '91');
@endphp

<div wire:ignore class="relative" x-data="countryPicker('{{ $defaultCode }}', {{ $index }}, '{{ $fieldType }}')" x-init="init()">
    <button type="button" @click="open = !open" class="form-select-figma text-sm w-full h-10 flex items-center justify-between px-3">
        <span class="flex items-center gap-2">
            <span x-show="selectedCountry" :class="`fi fi-${(selectedCountry?.Code || '').trim().toLowerCase()}`"></span>
            <span x-show="selectedCountry" x-text="selectedCountry?.Name + ' +' + (selectedCountry?.Phon_Code || '').trim()"></span>
        </span>
        <i class="bi bi-chevron-down text-gray-400"></i>
    </button>

    <div x-show="open" @click.outside="open = false" x-transition class="absolute z-20 mt-1 w-72 max-h-60 overflow-y-auto rounded-md bg-white shadow-lg border border-slate-200">
        <div class="p-2 sticky top-0 bg-white border-b">
            <input type="text" x-model="search" placeholder="Search country..."
                class="w-full rounded-md border border-gray-300 text-sm px-2 py-1">
        </div>
        <ul class="py-1">
            <template x-for="country in filteredCountries" :key="country.Admn_Cutr_Mast_UIN">
                <li @click="choose(country)" class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-slate-100 cursor-pointer">
                    <span :class="`fi fi-${(country?.Code || '').trim().toLowerCase()}`"></span>
                    <span class="font-medium flex-1 truncate" x-text="country.Name"></span>
                    <span class="text-gray-200" x-text="'+' + country.Phon_Code"></span>
                </li>
            </template>
            <li x-show="filteredCountries.length === 0" class="px-4 py-2 text-sm text-gray-500">
                No country found.
            </li>
        </ul>
    </div>
</div>

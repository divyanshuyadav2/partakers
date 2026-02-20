<div>
    @if ($showExportModal)
        <div class="fixed inset-0 z-30 flex items-center justify-center p-4"
             x-data="{ show: @entangle('showExportModal') }"
             @keydown.escape.window="show = false">

            <!-- Backdrop -->
            <div wire:click="closeModal"
                 x-show="show"
                 x-transition.opacity.duration.300ms
                 class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm"></div>

            <!-- Modal Window - INCREASED WIDTH -->
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative z-10 flex flex-col w-full max-w-5xl bg-slate-800 rounded-xl shadow-2xl border border-slate-700/60 overflow-hidden"
                 style="max-height: 85vh;">

                <!-- Header -->
                <div class="shrink-0 bg-slate-900/70 backdrop-blur-sm px-6 py-4 border-b border-slate-700/60 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-green-500/10 rounded-lg border border-green-500/20">
                            <i class="bi bi-box-arrow-up text-green-400 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white tracking-tight">Export Contacts</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Select columns to include in CSV</p>
                        </div>
                    </div>
                    <button wire:click="closeModal"
                        class="p-2 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <!-- Error Message -->
                @if (session()->has('export_error'))
                    <div class="mx-6 mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg flex items-center gap-3">
                        <i class="bi bi-exclamation-circle text-red-400"></i>
                        <span class="text-red-300 text-sm">{{ session('export_error') }}</span>
                    </div>
                @endif

                <!-- Select All / Deselect All Bar -->
                <div class="shrink-0 px-6 py-3 bg-slate-800/80 border-b border-slate-700/40 flex items-center justify-between">
                    <p class="text-xs text-slate-400">
                        <span class="font-semibold text-white">{{ $this->getSelectedCount() }}</span>
                        of
                        <span class="font-semibold text-white">{{ count($columns) }}</span>
                        columns selected
                    </p>
                    <div class="flex gap-2">
                        <button wire:click="selectAll"
                            class="text-xs px-3 py-1.5 bg-blue-600/20 hover:bg-blue-600/40 text-blue-300 border border-blue-600/30 rounded-md transition-colors">
                            <i class="bi bi-check-all"></i> Select All
                        </button>
                        <button wire:click="deselectAll"
                            class="text-xs px-3 py-1.5 bg-slate-700/50 hover:bg-slate-700 text-slate-300 border border-slate-600/50 rounded-md transition-colors">
                            <i class="bi bi-x"></i> Deselect All
                        </button>
                    </div>
                </div>

                <!-- Column Checkboxes - ORGANIZED BY SECTIONS -->
                <div class="flex-1 overflow-y-auto p-6 bg-slate-800/50">
                    
                    {{-- Personal Details Section --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-blue-300 mb-3 flex items-center gap-2">
                            <i class="bi bi-person-circle"></i>
                            Personal Details
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['name_prefix', 'gender', 'first_name', 'middle_name', 'last_name', 'birthday', 'self_employed'] as $key)
                                @if(isset($columns[$key]))
                                    @include('livewire.contacts.partials.export-field', ['key' => $key, 'col' => $columns[$key]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Company/Employment Section --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-purple-300 mb-3 flex items-center gap-2">
                            <i class="bi bi-building"></i>
                            Company & Employment
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['company_name', 'designation', 'employment_org', 'employment_desg'] as $key)
                                @if(isset($columns[$key]))
                                    @include('livewire.contacts.partials.export-field', ['key' => $key, 'col' => $columns[$key]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Contact Information Section --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-green-300 mb-3 flex items-center gap-2">
                            <i class="bi bi-telephone"></i>
                            Contact Information
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['phone1_label', 'phone1_code', 'phone1_number', 'phone2_label', 'phone2_code', 'phone2_number', 'phone3_label', 'phone3_code', 'phone3_number', 'email1', 'email2', 'email3'] as $key)
                                @if(isset($columns[$key]))
                                    @include('livewire.contacts.partials.export-field', ['key' => $key, 'col' => $columns[$key]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Address Section --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-orange-300 mb-3 flex items-center gap-2">
                            <i class="bi bi-geo-alt"></i>
                            Address
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['address_type', 'primary_address'] as $key)
                                @if(isset($columns[$key]))
                                    @include('livewire.contacts.partials.export-field', ['key' => $key, 'col' => $columns[$key]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Education & Skills Section --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-cyan-300 mb-3 flex items-center gap-2">
                            <i class="bi bi-mortarboard"></i>
                            Education & Skills
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['degree_name', 'degree_year', 'skill_name'] as $key)
                                @if(isset($columns[$key]))
                                    @include('livewire.contacts.partials.export-field', ['key' => $key, 'col' => $columns[$key]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Financial Information Section --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-yellow-300 mb-3 flex items-center gap-2">
                            <i class="bi bi-bank"></i>
                            Financial Information
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['bank_name', 'bank_account', 'bank_acc_type', 'bank_ifsc'] as $key)
                                @if(isset($columns[$key]))
                                    @include('livewire.contacts.partials.export-field', ['key' => $key, 'col' => $columns[$key]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Documents Section --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-pink-300 mb-3 flex items-center gap-2">
                            <i class="bi bi-file-earmark-text"></i>
                            Documents
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['document_type', 'document_number'] as $key)
                                @if(isset($columns[$key]))
                                    @include('livewire.contacts.partials.export-field', ['key' => $key, 'col' => $columns[$key]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Web Presence & Other Section --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-indigo-300 mb-3 flex items-center gap-2">
                            <i class="bi bi-globe"></i>
                            Web Presence & Other
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (['tags', 'groups', 'website', 'facebook', 'twitter', 'linkedin', 'instagram', 'notes'] as $key)
                                @if(isset($columns[$key]))
                                    @include('livewire.contacts.partials.export-field', ['key' => $key, 'col' => $columns[$key]])
                                @endif
                            @endforeach
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="shrink-0 bg-slate-900/50 px-6 py-4 border-t border-slate-700/60 flex items-center justify-between gap-3">
                    <p class="text-xs text-slate-500">
                        <i class="bi bi-info-circle"></i>
                        Only selected columns will appear in the exported CSV file.
                    </p>
                    <div class="flex gap-3">
                        <button wire:click="closeModal"
                            class="bg-slate-700/50 hover:bg-slate-700 text-slate-300 font-semibold py-2 px-5 rounded-lg transition-colors text-sm">
                            Cancel
                        </button>
                        <button wire:click="exportCsv"
                            wire:loading.attr="disabled"
                            @disabled($this->getSelectedCount() === 0)
                            class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-500 disabled:bg-slate-600 disabled:cursor-not-allowed text-white font-semibold py-2 px-5 rounded-lg transition-colors text-sm min-w-[130px]">
                            <span wire:loading.remove wire:target="exportCsv">
                                <i class="bi bi-box-arrow-up"></i>
                                Export CSV
                                @if($this->getSelectedCount() > 0)
                                    ({{ $this->getSelectedCount() }})
                                @endif
                            </span>
                            <span wire:loading wire:target="exportCsv" class="flex items-center gap-2">
                                <i class="bi bi-arrow-repeat animate-spin"></i> Exporting...
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>
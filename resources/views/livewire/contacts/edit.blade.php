<div>
    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
        <div x-data="{ show: false, message: '' }"
            @notify-error.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 5000)"
            x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2"
            class="fixed top-5 right-5 bg-red-500 text-white py-2 px-4 rounded-xl shadow-lg z-50"
            style="display: none;">
            <div class="flex items-center">
                <i class="bi bi-exclamation-circle-fill mr-2"></i>
                <p x-text="message"></p>
            </div>
        </div>
        {{-- Page Header --}}
        <!-- Notes Sidebar Trigger -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex gap-2 ">

                <a href="{{ route('contacts.index') }}" class="text-white hover:text-green-200">
                    <i class="bi bi-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-green-200">Edit Contact</h1>
                    @if ($MoOn)
                        <p class="text-slate-400 text-sm mt-1">
                            Last updated:
                            {{ \Carbon\Carbon::parse($MoOn)->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST
                        </p>
                    @endif
                </div>


            </div>

            <button type="button" wire:click="toggleNoteSidebar"
                class="flex items-center gap-2 px-3 py-1.5 bg-transparent border border-blue-500 text-blue-300 rounded-md hover:bg-blue-500/10 transition-colors duration-200">
                <i class="bi bi-journal-text text-sm"></i>
                <span class="text-sm">Notes</span>
                @if (count($existingNotes) > 0)
                    <span
                        class="ml-1 inline-flex items-center justify-center w-4 h-4 text-xs font-medium bg-blue-500/30 rounded-full">
                        {{ count($existingNotes) }}
                    </span>
                @endif
            </button>
        </div>




        <!-- Notes Sidebar -->
        @if ($showNoteSidebar)
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 z-40" wire:click="toggleNoteSidebar"></div>

            <div class="fixed inset-0 overflow-hidden z-50">
                <div class="absolute inset-0 overflow-hidden">
                    <!-- Sidebar Container -->
                    <div wire:click.away="toggleNoteSidebar"
                        class="fixed right-0 inset-y-0 flex flex-col bg-slate-900 w-full md:max-w-[420px] shadow-lg rounded-l-lg transform transition-all ease-out duration-300 border-l border-slate-700">

                        <!-- Header -->
                        <header
                            class="px-5 py-4 bg-slate-800 border-b border-slate-700 flex items-center justify-between">
                            <div class="flex items-center gap-3">

                            </div>
                            <button type="button" wire:click="toggleNoteSidebar"
                                class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-700 rounded-md transition-colors">
                                <i class="bi bi-x-lg text-lg"></i>
                            </button>
                        </header>

                        <!-- Main Content Area -->
                        <div class="flex flex-col flex-1 overflow-hidden min-h-0">
                            <!-- Notes List -->
                            <div class="flex-1 overflow-y-auto">
                                @if (count($existingNotes) > 0)
                                    <div class="p-3 space-y-2">
                                        @foreach ($existingNotes as $note)
                                            <div x-data="{ openMenu: false }" class="group">
                                                <!-- Note Card - Compact inline design -->
                                                <div
                                                    class="relative p-2.5 rounded-md border border-slate-700 bg-slate-800/50">
                                                    @php
                                                        $formatted = preg_replace('/\.\s+/', ".\n", $note['content']);
                                                        $lines = array_filter(
                                                            array_map('trim', explode("\n", $formatted)),
                                                        );
                                                        $firstLine = reset($lines);
                                                        $restLines = array_slice($lines, 1);
                                                    @endphp

                                                    <!-- First Line with Icons on Right -->
                                                    <div class="flex items-start justify-between gap-2 mb-1">
                                                        <div
                                                            class="flex-1 text-xs text-slate-300 leading-relaxed break-words">
                                                            {{ $firstLine }}
                                                        </div>
                                                        <!-- Icons Container -->
                                                        <div class="flex items-center gap-3 flex-shrink-0">
                                                            @if ($note['isPinned'])
                                                                <button type="button"
                                                                    wire:click="unpinNote({{ $note['id'] }})"
                                                                    class="text-yellow-400 hover:text-yellow-300 transition-colors">
                                                                    <i class="bi bi-pin-fill text-xs"></i>
                                                                </button>
                                                            @endif
                                                            <template x-if="openMenu">
                                                                <div class="flex items-center gap-3">
                                                                    @if (!$note['isPinned'])
                                                                        <button type="button"
                                                                            wire:click="pinNote({{ $note['id'] }})"
                                                                            class="text-blue-400 hover:text-blue-300 transition-colors">
                                                                            <i class="bi bi-pin text-xs"></i>
                                                                        </button>
                                                                        @if ($this->canDeleteNote($note))
                                                                            <button type="button"
                                                                                wire:click="deleteNote({{ $note['id'] }})"
                                                                                wire:confirm="Delete this note?"
                                                                                class="text-red-400 hover:text-red-300 transition-colors">
                                                                                <i class="bi bi-trash3 text-xs"></i>
                                                                            </button>
                                                                        @endif
                                                                    @else
                                                                        @if ($this->canDeleteNote($note))
                                                                            <button type="button"
                                                                                wire:click="deleteNote({{ $note['id'] }})"
                                                                                wire:confirm="Delete this note?"
                                                                                class="text-red-400 hover:text-red-300 transition-colors">
                                                                                <i class="bi bi-trash3 text-xs"></i>
                                                                            </button>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            </template>
                                                            <button @click="openMenu = !openMenu" type="button"
                                                                class="text-slate-400 hover:text-white transition-colors opacity-0 group-hover:opacity-100">
                                                                <i class="bi bi-three-dots-vertical text-xs"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Remaining Lines -->
                                                    @foreach ($restLines as $line)
                                                        <div
                                                            class="text-xs text-slate-300 leading-relaxed break-words mb-1">
                                                            {{ $line }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <!-- Empty State -->
                                    <div class="h-full flex items-center justify-center p-6">
                                        <div class="text-center space-y-2">
                                            <i class="bi bi-journal-richtext text-slate-600 text-2xl block"></i>
                                            <h3 class="text-sm font-semibold text-slate-400">No notes yet</h3>
                                            <p class="text-xs text-slate-500">Start adding notes to your contact</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Bottom Section -->
                        <div
                            class="flex-1 flex flex-col p-4 gap-3 overflow-hidden bg-slate-800 border-t border-slate-700">
                            <!-- Note Input -->
                            <div class="relative flex-[0.35] min-h-[120px]">
                                <textarea id="new-note-content" wire:model.live.debounce="newNoteContent" placeholder="Type your note..."
                                    class="w-full h-full resize-none p-3 pr-24 text-sm rounded-md border border-slate-700/60 focus:border-blue-500 bg-slate-900/70 text-white placeholder-slate-500 outline-none transition">
                                </textarea>

                                <!-- Save / Cancel -->
                                <div class="absolute bottom-2 right-2 flex gap-1.5">
                                    <button wire:click="closeNoteSidebar"
                                        class="px-2.5 py-1 bg-slate-700/60 hover:bg-slate-700/80 text-slate-300 hover:text-white text-xs rounded transition">
                                        Cancel
                                    </button>
                                    <button wire:click="saveNote" @if (empty($newNoteContent)) disabled @endif
                                        class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-700/50 disabled:text-slate-500 text-white text-xs rounded transition">
                                        Save
                                    </button>
                                </div>
                            </div>

                            <!-- Templates -->
                            <div class="flex-[0.65] flex flex-col min-h-0">
                                <p class="text-xs font-semibold text-slate-300 flex items-center gap-1.5 mb-2">
                                    <i class="bi bi-lightning-charge-fill text-yellow-400 text-xs"></i>
                                    Templates
                                </p>
                                <div class="flex flex-col flex-1 border border-slate-700/60 rounded-md bg-slate-800/40 overflow-hidden">
                                    
                                    <!-- Category Search Select -->
                                    <div class="p-2 bg-slate-800/60 border-b border-slate-700/50 shrink-0">
                                        <div class="relative" x-data="{ 
                                            open: false, 
                                            search: '', 
                                            categories: @js(array_keys($this->getQuickComments()->toArray())),
                                            get filteredCategories() {
                                                if (!this.search) return this.categories;
                                                return this.categories.filter(cat => 
                                                    cat.toLowerCase().includes(this.search.toLowerCase())
                                                );
                                            }
                                        }">
                                            <!-- Selected Value / Trigger -->
                                            <button 
                                                type="button"
                                                @click="open = !open"
                                                @click.away="open = false"
                                                class="w-full px-3 py-1.5 text-xs bg-slate-700/40 border border-slate-600 rounded-md text-slate-200 hover:bg-slate-700/60 transition-colors flex items-center justify-between">
                                                <span>{{ $activeCommentTab ?? 'PAN' }}</span>
                                                <i class="bi bi-chevron-down text-[10px]" :class="{ 'rotate-180': open }"></i>
                                            </button>

                                            <!-- Dropdown -->
                                            <div 
                                                x-show="open"
                                                x-transition
                                                class="absolute z-50 w-full mt-1 bg-slate-800 border border-slate-600 rounded-md shadow-lg max-h-64 overflow-hidden flex flex-col">
                                                
                                                <!-- Search Input -->
                                                <div class="p-2 border-b border-slate-700/50">
                                                    <input 
                                                        type="text"
                                                        x-model="search"
                                                        placeholder="Search categories..."
                                                        class="w-full px-2 py-1 text-xs bg-slate-900/70 border border-slate-600 rounded text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500"
                                                        @click.stop>
                                                </div>

                                                <!-- Category List -->
                                                <div class="overflow-y-auto max-h-48">
                                                    <template x-for="category in filteredCategories" :key="category">
                                                        <button
                                                            type="button"
                                                            @click="$wire.set('activeCommentTab', category); open = false; search = ''"
                                                            class="w-full px-3 py-1.5 text-xs text-left hover:bg-blue-500/15 transition-colors"
                                                            :class="@js($activeCommentTab ?? 'PAN') === category ? 'bg-blue-500/20 text-blue-300' : 'text-slate-300'"
                                                            x-text="category">
                                                        </button>
                                                    </template>

                                                    <!-- No Results -->
                                                    <div x-show="filteredCategories.length === 0" class="px-3 py-2 text-xs text-slate-500 text-center">
                                                        No categories found
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Comments List -->
                                    <div class="flex-1 overflow-y-auto p-1.5 space-y-1">
                                        @foreach ($this->getQuickComments()[$activeCommentTab ?? 'PAN'] ?? collect() as $comment)
                                            <button type="button"
                                                wire:click="addCommentToNote(@js($comment->Comnt_Text))"
                                                class="w-full px-2 py-1.5 text-xs bg-slate-700/40 hover:bg-blue-500/15 text-slate-300 hover:text-blue-300 rounded-sm transition-colors text-left truncate">
                                                {{ $comment->Comnt_Text }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Contact Type Selector - Inline Card Sticky -->
        <div
            class="sticky top-0 z-30 backdrop-blur-md bg-slate-950/90 border border-slate-800 shadow-lg rounded-lg mx-auto my-4">
            <div class="max-w-4xl mx-auto px-6 py-3 flex justify-center gap-4">
                <!-- Right: Buttons -->
                <div class="flex gap-3 w-auto">
                    @if ($isVerified)
                        {{-- Only the current party button is visible and disabled --}}
                        @if ($Prty === 'I')
                            <!-- Individual Button (only visible, disabled) -->
                            <label
                                class="px-5 py-2 rounded-md text-md font-semibold select-none border transition-colors duration-200
                                    flex items-center gap-2 whitespace-nowrap bg-blue-600 text-blue-50 border-blue-600 shadow-inner cursor-not-allowed">
                                <input type="radio" wire:model.live="Prty" value="I" class="sr-only"
                                    disabled />
                                <i class="bi bi-person-fill text-base"></i>
                                <span>For Individual</span>
                            </label>
                        @elseif ($Prty === 'B')
                            <!-- Business Button (only visible, disabled) -->
                            <label
                                class="px-5 py-2 rounded-md text-md font-semibold select-none border transition-colors duration-200
                                    flex items-center gap-2 whitespace-nowrap bg-emerald-600 text-emerald-50 border-emerald-600 shadow-inner cursor-not-allowed">
                                <input type="radio" wire:model.live="Prty" value="B" class="sr-only"
                                    disabled />
                                <i class="bi bi-building-fill text-base"></i>
                                <span>For Organization</span>
                            </label>
                        @endif
                    @else
                        {{-- Both buttons are visible and clickable --}}
                        <!-- Individual Button -->
                        <label
                            class="px-5 py-2 rounded-md text-md font-semibold cursor-pointer select-none border transition-colors duration-200
                                flex items-center gap-2 whitespace-nowrap"
                            :class="{
                                'bg-blue-600 text-blue-50 border-blue-600 shadow-inner': @js($Prty) === 'I',
                                'text-slate-400 border-slate-700 hover:text-slate-200 hover:border-slate-500': @js($Prty) !== 'I'
                            }">
                            <input type="radio" wire:model.live="Prty" value="I" class="sr-only" />
                            <i class="bi bi-person-fill text-base"></i>
                            <span>For Individual</span>
                        </label>

                        <!-- Business Button -->
                        <label
                            class="px-5 py-2 rounded-md text-md font-semibold cursor-pointer select-none border transition-colors duration-200
                                flex items-center gap-2 whitespace-nowrap"
                            :class="{
                                'bg-emerald-600 text-emerald-50 border-emerald-600 shadow-inner': @js($Prty) === 'B',
                                'text-slate-400 border-slate-700 hover:text-slate-200 hover:border-slate-500': @js($Prty) !== 'B'
                            }">
                            <input type="radio" wire:model.live="Prty" value="B" class="sr-only" />
                            <i class="bi bi-building-fill text-base"></i>
                            <span>For Organization</span>
                        </label>
                    @endif
                </div>

            </div>
        </div>

        {{-- The Form Wrapper --}}
        <form wire:submit="save" class="mt-8 grid grid-cols-1 gap-8">
            <!-- Personal Details Card -->
            <div class="figma-card">
                <h2 class="figma-card-header text-green-200"><i class="bi bi-person-vcard"></i>
                    @if ($Prty === 'B')
                        Organization Details
                    @elseif ($Prty === 'I')
                        Personal Details
                    @else
                        Personal Details
                    @endif
                </h2>
                <div class="p-6 space-y-6">
                    {{-- Profile Picture Section --}}
                    <div x-data="imageCropperComponent()" wire:key="alpine-image-cropper"
                        class="flex flex-col items-center space-y-4">
                        <input type="file" class="hidden" x-ref="fileInput" @change="handleFileSelect"
                            accept="image/png, image/jpeg, image/gif">
                        <div class="relative group w-36 h-36">
                            <div
                                class="w-36 h-36 rounded-full mx-auto bg-slate-800 border-2 border-dashed border-slate-600 flex items-center justify-center overflow-hidden">
                                @if ($Prfl_Pict)
                                    <img src="{{ $Prfl_Pict->temporaryUrl() }}" class="w-full h-full object-cover"
                                        alt="Profile Preview">
                                @elseif ($existing_avatar)
                                    <img src="{{ Storage::url($existing_avatar) }}"
                                        class="w-full h-full object-cover" alt="Existing Profile Picture">
                                @else
                                    <div @click="$refs.fileInput.click()"
                                        class="cursor-pointer w-full h-full flex flex-col items-center justify-center text-center p-2">
                                        <i class="bi bi-camera-fill text-3xl text-slate-500"></i>
                                        <span class="text-xs text-slate-500 mt-1">Upload Photo</span>
                                    </div>
                                @endif
                            </div>
                            @if ($Prfl_Pict || $existing_avatar)
                                <div
                                    class="absolute inset-0 rounded-full bg-black/60 flex flex-col items-center justify-center space-y-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <button type="button" @click.prevent="$refs.fileInput.click()"
                                        class="flex items-center gap-2 text-sm font-semibold text-green-200 hover:text-blue-300 transition-colors">
                                        <i class="bi bi-arrow-repeat"></i>
                                        <span>Change</span>
                                    </button>
                                    <button type="button" wire:click="removeProfilePicture"
                                        class="flex items-center gap-2 text-sm font-semibold text-green-200 hover:text-red-400 transition-colors">
                                        <i class="bi bi-trash"></i>
                                        <span>Clear</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                        <span class="text-gray-400 text-xs -mt-2 block">upload file type : jpg/png </span>
                        @error('Prfl_Pict')
                            <p class="mt-2 text-xs text-red-400 flex items-center gap-1">
                                <i class="bi bi-exclamation-triangle"></i>
                                {{ $message }}
                            </p>
                        @enderror

                        <!-- Cropper Modal -->
                        <div x-show="showCropper" x-transition
                            class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50"
                            style="display: none;">
                            <div @click.away="closeCropper()"
                                class="bg-slate-800 rounded-md shadow-xl w-full max-w-lg">
                                <div class="p-6">
                                    <h3 class="text-lg font-medium text-green-200 mb-4">Crop Your Image</h3>
                                    <div class="w-full h-80 bg-slate-900">
                                        <img x-ref="imageToCropEl" :src="imageToCrop"
                                            class="max-w-full max-h-full block">
                                    </div>
                                </div>
                                <div
                                    class="px-6 py-4 bg-slate-900/50 flex justify-end items-center gap-4 rounded-b-lg">
                                    <button type="button" @click="closeCropper()"
                                        class="text-sm font-semibold text-white hover:text-green-200 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="button" @click="cropImage()"
                                        class="figma-button-primary flex gap-2">
                                        <i class="bi bi-crop"></i> Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @if ($Prty === 'I')
                            <!-- Prefix -->
                            <div>
                                <label for="Prfx_UIN" class="text-sm font-medium text-white">Prefix</label>
                                <div class="relative mt-1">
                                    <select id="Prfx_UIN" wire:model.live="Prfx_UIN"
                                        class="form-select-figma @error('Prfx_UIN') border-red-500 @enderror">
                                        <option value="">Select...</option>
                                        @foreach ($allPrefixes as $prefix)
                                            <option value="{{ $prefix->Prfx_Name_UIN }}"
                                                title="{{ $prefix->Prfx_Name_Desp ?? '' }}">
                                                {{ $prefix->Prfx_Name ?? '' }}
                                                {{ '[' . $prefix->Prfx_Name_Desp . ']' ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('Prfx_UIN')
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('Prfx_UIN')
                                    <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <!-- First Name -->

                        <div>
                            @if ($Prty === 'B')
                                <label for="FaNm" class="text-sm font-medium text-white">Organization Name<span
                                        class="text-red-400">*</span>
                                </label>
                                <div class="relative mt-1">
                                    <input type="text" id="FaNm" wire:model.live="FaNm"
                                        class="form-input-figma @error('FaNm') border-red-500 @enderror">
                                    @error('FaNm')
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                        </div>
                                    @enderror
                                </div>
                            @else
                                <label for="FaNm" class="text-sm font-medium text-white">First Name<span
                                        class="text-red-400">*</span>
                                </label>
                                <div class="relative mt-1">
                                    <input type="text" id="FaNm" wire:model.live="FaNm"
                                        x-on:input="$event.target.value = $event.target.value.replace(/[^a-zA-Z ]/g, '')"
                                        class="form-input-figma @error('FaNm') border-red-500 @enderror">
                                </div>
                            @endif
                            @error('FaNm')
                                <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                            @enderror

                        </div>




                        <!-- Middle Name -->
                        @if ($Prty === 'I')
                            <div>
                                <label for="MiNm" class="text-sm font-medium text-white">Middle Name</label>
                                <div class="relative mt-1">
                                    <input type="text" id="MiNm"
                                        x-on:input="$event.target.value = $event.target.value.replace(/[^a-zA-Z ]/g, '')"
                                        wire:model.live="MiNm"
                                        class="form-input-figma @error('MiNm') border-red-500 @enderror">
                                    @error('MiNm')
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('MiNm')
                                    <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="LaNm" class="text-sm font-medium text-white">Last Name</label>
                                <div class="relative mt-1">
                                    <input type="text" id="LaNm"
                                        x-on:input="$event.target.value = $event.target.value.replace(/[^a-zA-Z ]/g, '')"
                                        wire:model.live="LaNm"
                                        class="form-input-figma @error('LaNm') border-red-500 @enderror">
                                    @error('LaNm')
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('LaNm')
                                    <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <!-- Gender -->
                        <div>
                            <label for="Gend" class="text-sm font-medium text-white">
                                @if ($Prty === 'B')
                                    Organization Type
                                @else
                                    Gender
                                @endif
                                <span class="text-red-400">*</span>
                            </label>
                            <div class="relative mt-1">
                                <select id="Gend" wire:model.live="Gend"
                                    class="form-select-figma @error('Gend') border-red-500 @enderror">
                                    <option value="">Select...</option>
                                    @if ($Prty === 'B')
                                        <option value="Private Limited">Private Limited</option>
                                        <option value="Public Limited">Public Limited</option>
                                        <option value="Partnership">Partnership</option>
                                        <option value="Sole Proprietorship">Sole Proprietorship</option>
                                        <option value="LLP">LLP</option>
                                        <option value="NGO">NGO</option>
                                    @elseif($Prty === 'I')
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="transgender">Transgender</option>
                                    @endif
                                </select>
                                @error('Gend')
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                    </div>
                                @enderror
                            </div>
                            @error('Gend')
                                <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>



                        @if ($Prty === 'I')
                            <!-- Blood Group -->

                            <div>
                                <label for="Blood_Grp" class="text-sm font-medium text-white">Blood Group</label>
                                <div class="relative mt-1">
                                    <select id="Blood_Grp" wire:model.live="Blood_Grp"
                                        class="form-select-figma @error('Blood_Grp') border-red-500 @enderror">
                                        <option value="">Select...</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                    @error('Blood_Grp')
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('Blood_Grp')
                                    <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                        <!-- Date of Birth & Incorporation -->
                        <div>
                            <label for="Brth_Dt" class="text-sm font-medium text-white">Date of
                                @if ($Prty === 'I')
                                    Birth
                                @elseif ($Prty === 'B')
                                    Incorporation
                                @endif

                            </label>
                            <div class="relative mt-1">
                                <input type="date" id="Brth_Dt" wire:model.live="Brth_Dt"
                                    class="form-input-figma @error('Brth_Dt') border-red-500 @enderror">
                                @error('Brth_Dt')
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                    </div>
                                @enderror
                            </div>
                            @error('Brth_Dt')
                                <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        @if ($Prty === 'I')
                            <!-- Anniversary Date -->
                            <div>
                                <label for="Anvy_Dt" class="text-sm font-medium text-white">Anniversary Date</label>
                                <div class="relative mt-1">
                                    <input type="date" id="Anvy_Dt" wire:model.live="Anvy_Dt"
                                        class="form-input-figma @error('Anvy_Dt') border-red-500 @enderror">
                                    @error('Anvy_Dt')
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('Anvy_Dt')
                                    <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Date of Death -->
                            <div>
                                <label for="Deth_Dt" class="text-sm font-medium text-white">Date of Death</label>
                                <div class="relative mt-1">
                                    <input type="date" id="Deth_Dt" wire:model.live="Deth_Dt"
                                        class="form-input-figma @error('Deth_Dt') border-red-500 @enderror">
                                    @error('Deth_Dt')
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                        </div>
                                    @enderror
                                </div>
                                @error('Deth_Dt')
                                    <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>


            </div>

            <!-- Contact Information Card -->
            <div class="figma-card rounded-2xl shadow-sm bg-slate-800/40 border border-slate-700">
                <h2 class="figma-card-header text-lg font-semibold text-green-200 border-b border-slate-700 px-6 py-4">
                    <i class="bi bi-person-lines-fill mr-2"></i>Contact Information
                </h2>
                <div class="p-6 space-y-8">
                    <!-- Mobile Numbers -->
                    <div>
                        <h3
                            class="font-medium text-white mb-4 flex items-center gap-2 text-sm uppercase tracking-wide">
                            <i class="bi bi-telephone-fill text-blue-400"></i> Mobile Numbers
                        </h3>
                        <div class="space-y-4">
                            @forelse ($phones as $index => $phone)
                                <div class="relative p-4 rounded-md bg-slate-700/30 border border-slate-600"
                                    wire:key="phone-{{ $index }}">
                                    <!-- Controls -->
                                    <div class="relative right-0 top-0 flex items-center justify-end gap-4 pb-2">
                                        <label for="primary_phone_{{ $index }}"
                                            class="flex items-center cursor-pointer gap-2">
                                            <span class="text-sm font-medium text-white">Preferable</span>
                                            <input type="radio" id="primary_phone_{{ $index }}"
                                                name="primary_phone"
                                                wire:click="setPrimaryPhone({{ $index }})"
                                                @if ($phone['Is_Prmy'] ?? false) checked @endif
                                                class="form-radio-figma">
                                        </label>
                                        @if (count($phones) > 1)
                                            <button type="button" wire:click="removePhone({{ $index }})"
                                                wire:confirm="Are you sure you want to remove this mobile number?"
                                                class="text-slate-500 hover:text-red-500 transition-colors">
                                                <i class="bi bi-trash-fill text-lg"></i>
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Phone Fields -->
                                    <div class="flex-grow grid grid-cols-1 sm:grid-cols-5 gap-3">
                                        <!-- Country Picker -->
                                        <div class="sm:col-span-2">
                                            <div wire:ignore>
                                                <div x-data="countryPicker('{{ $phone['Cutr_Code'] ?? ($allCountries[0]['Phon_Code'] ?? '91') }}', {{ $index }})" x-init="init()"
                                                    @primary-country-changed.window="updateFromPrimary($event.detail)"
                                                    class="relative">

                                                    <button type="button" @click="open = !open"
                                                        :aria-expanded="open"
                                                        class="form-select-figma text-sm w-full h-10 flex items-center justify-between px-3">
                                                        <span class="flex items-center gap-2 min-w-0">
                                                            <span x-show="selectedCountry"
                                                                :class="`fi fi-${(selectedCountry?.Code || '').trim().toLowerCase()}`"></span>
                                                            <span x-show="selectedCountry"
                                                                class="truncate max-w-[120px]"
                                                                x-text="selectedCountry?.Name + ' +' +(selectedCountry?.Phon_Code || '').trim()"></span>
                                                        </span>
                                                        <i :class="{ 'rotate-180': open }"
                                                            class="bi bi-chevron-down text-gray-400 transition-transform"></i>
                                                    </button>
                                                    <div x-show="open" @click.outside="open = false"
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-75"
                                                        x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-95"
                                                        class="absolute z-30 mt-1 w-full max-w-xs min-w-[220px] rounded-md bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 focus:outline-none">
                                                        <div
                                                            class="sticky top-0 z-10 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-2 pt-2 pb-1">
                                                            <input type="text" x-model="search" autofocus
                                                                placeholder="Search country..."
                                                                class="form-select-figma text-sm w-full px-2 py-1 mb-1 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 border border-slate-300 dark:border-slate-600 rounded-md focus:ring-2 focus:ring-blue-400 focus:border-blue-400 outline-none">
                                                        </div>
                                                        <ul class="max-h-52 overflow-y-auto py-1">
                                                            <template x-for="(country, idx) in filteredCountries"
                                                                :key="country.Code + '_' + idx">
                                                                <li @click="choose(country); open = false;"
                                                                    :class="{
                                                                        'bg-blue-50 dark:bg-slate-700/60': selectedCountry &&
                                                                            selectedCountry.Code === country
                                                                            .Code,
                                                                        'hover:bg-slate-100 dark:hover:bg-slate-700': true
                                                                    }"
                                                                    class="flex items-center gap-x-3 px-3 py-2 text-sm cursor-pointer transition-colors select-none form-select-figma">
                                                                    <span
                                                                        :class="`fi fi-${(country?.Code || '').trim().toLowerCase()}`"></span>
                                                                    <span class="font-medium flex-1 truncate"
                                                                        x-text="country.Name"></span>
                                                                    <span class="text-gray-200"
                                                                        x-text="'+' + country.Phon_Code"></span>
                                                                    <span
                                                                        x-show="selectedCountry && selectedCountry.Code === country.Code"
                                                                        class="ml-2 text-blue-500"><i
                                                                            class="bi bi-check-circle-fill"></i></span>
                                                                </li>
                                                            </template>
                                                            <li x-show="filteredCountries.length === 0"
                                                                class="px-4 py-2 text-sm text-gray-500">No country
                                                                found.</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Mobile Number -->
                                        <div class="sm:col-span-2">
                                            <input type="tel"
                                                wire:model.live="phones.{{ $index }}.Phon_Numb"
                                                placeholder="Mobile Number" class="form-input-figma text-sm w-full"
                                                :class="{ 'border-red-500 ring-red-500': @error('phones.' . $index . '.Phon_Numb') true @else false @enderror }"
                                                x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')">
                                            @error('phones.' . $index . '.Phon_Numb')
                                                <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <!-- Type -->
                                        <div class="sm:col-span-1 flex gap-3">
                                            <select wire:model.live="phones.{{ $index }}.Phon_Type"
                                                class="form-select-figma text-sm w-full"
                                                :class="{ 'border-red-500 ring-red-500': @error('phones.' . $index . '.Phon_Type') true @else false @enderror }">
                                                <option value="self">Self</option>
                                                <option value="office">Office</option>
                                                <option value="home">Home</option>
                                            </select>
                                            @error('phones.' . $index . '.Phon_Type')
                                                <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                            @enderror
                                            <!-- Messaging -->
                                            <div class="hidden md:flex md:flex-col gap-8 md:gap-2">
                                                <label class="flex items-center gap-2 text-sm text-gray-200">
                                                    <input type="checkbox"
                                                        wire:model.live="phones.{{ $index }}.Has_WtAp"
                                                        class="form-checkbox-figma">
                                                    <i class="bi bi-whatsapp text-green-400 text-xs"
                                                        title="WhatsApp"></i>
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-gray-200">
                                                    <input type="checkbox"
                                                        wire:model.live="phones.{{ $index }}.Has_Telg"
                                                        class="form-checkbox-figma">
                                                    <i class="bi bi-telegram text-sky-400 text-xs"
                                                        title="Telegram"></i>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="md:hidden flex gap-8 md:gap-2">
                                            <label class="flex items-center gap-2 text-sm text-gray-200">
                                                <input type="checkbox"
                                                    wire:model.live="phones.{{ $index }}.Has_WtAp"
                                                    class="form-checkbox-figma">
                                                <i class="bi bi-whatsapp text-green-400 text-xs" title="WhatsApp"></i>
                                            </label>
                                            <label class="flex items-center gap-2 text-sm text-gray-200">
                                                <input type="checkbox"
                                                    wire:model.live="phones.{{ $index }}.Has_Telg"
                                                    class="form-checkbox-figma">
                                                <i class="bi bi-telegram text-sky-400 text-xs" title="Telegram"></i>
                                            </label>
                                        </div>


                                    </div>
                                </div>
                            @empty
                                <p class="text-slate-500 text-sm pl-1">No mobile numbers added.</p>
                            @endforelse
                        </div>
                        @if ($this->canAdd('phones'))
                            <button type="button" wire:click="addPhone" wire:loading.attr="disabled"
                                class="mt-4 text-sm font-semibold capitalize text-blue-400 hover:text-blue-300 flex items-center gap-2">
                                <i class="bi bi-plus-circle"></i> Add Mobile number
                            </button>
                        @endif
                    </div>

                    <!-- Landline Numbers -->
                    <div>
                        <h3
                            class="font-medium text-white mb-4 flex items-center gap-2 text-sm uppercase tracking-wide">
                            <i class="bi bi-telephone text-purple-400"></i> <span>Landline Numbers</span>
                        </h3>
                        <div class="space-y-4">
                            @forelse ($landlines as $index => $landline)
                                <div wire:key="landline-{{ $index }}"
                                    class="relative p-4 rounded-md bg-slate-700/30 border border-slate-600">

                                    <div class="relative right-0 top-0 flex items-center justify-end gap-4 pb-2">
                                        <label for="primary_landline_{{ $index }}"
                                            class="flex items-center cursor-pointer gap-2">
                                            <span class="text-sm font-medium text-white">Preferable</span>
                                            <input type="radio" id="primary_landline_{{ $index }}"
                                                name="primary_landline"
                                                wire:click="setPrimaryLandline({{ $index }})"
                                                {{ $landline['Is_Prmy'] ?? false ? 'checked' : '' }}
                                                class="form-radio-figma" />
                                        </label>
                                        @if (count($landlines) > 1)
                                            <button type="button" wire:click="removeLandline({{ $index }})"
                                                class="text-slate-500 hover:text-red-500 transition-colors"
                                                wire:confirm="Are you sure you want to remove this landline number?"
                                                aria-label="Remove landline number">
                                                <i class="bi bi-trash-fill text-lg"></i>
                                            </button>
                                        @endif
                                    </div>

                                    <div class="flex-grow grid grid-cols-1 sm:grid-cols-5 gap-3">

                                        <div class="sm:col-span-2">
                                            <div wire:ignore>
                                                <div x-data="countryPicker('{{ $landline['Cutr_Code'] ?? ($allCountries[0]['Phon_Code'] ?? '91') }}', {{ $index }}, 'landlines')" x-init="init()"
                                                    @primary-country-changed.window="updateFromPrimary($event.detail)"
                                                    class="relative">

                                                    <button type="button" @click="open = !open"
                                                        :aria-expanded="open"
                                                        class="form-select-figma text-sm w-full h-10 flex items-center justify-between px-3">
                                                        <span class="flex items-center gap-2 min-w-0">
                                                            <span x-show="selectedCountry"
                                                                :class="`fi fi-${(selectedCountry?.Code || '').trim().toLowerCase()}`"></span>
                                                            <span x-show="selectedCountry"
                                                                class="truncate max-w-[120px]"
                                                                x-text="selectedCountry?.Name + ' +' +(selectedCountry?.Phon_Code || '').trim()"></span>
                                                        </span>
                                                        <i :class="{ 'rotate-180': open }"
                                                            class="bi bi-chevron-down text-gray-400 transition-transform"></i>
                                                    </button>

                                                    <div x-show="open" @click.outside="open = false"
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-75"
                                                        x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-95"
                                                        class="absolute z-30 mt-1 w-full max-w-xs min-w-[220px] rounded-md bg-white dark:bg-slate-800 shadow-xl border border-slate-200 dark:border-slate-700 focus:outline-none">
                                                        <div
                                                            class="sticky top-0 z-10 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-2 pt-2 pb-1">
                                                            <input type="text" x-model="search" autofocus
                                                                placeholder="Search country..."
                                                                class="form-select-figma text-sm w-full px-2 py-1 mb-1 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 border border-slate-300 dark:border-slate-600 rounded-md focus:ring-2 focus:ring-blue-400 focus:border-blue-400 outline-none">
                                                        </div>
                                                        <ul class="max-h-52 overflow-y-auto py-1">
                                                            <template x-for="(country, idx) in filteredCountries"
                                                                :key="country.Code + '_' + idx">
                                                                <li @click="choose(country); open = false;"
                                                                    :class="{
                                                                        'bg-blue-50 dark:bg-slate-700/60': selectedCountry &&
                                                                            selectedCountry.Code === country
                                                                            .Code,
                                                                        'hover:bg-slate-100 dark:hover:bg-slate-700': true
                                                                    }"
                                                                    class="flex items-center gap-x-3 px-3 py-2 text-sm cursor-pointer transition-colors select-none form-select-figma">
                                                                    <span
                                                                        :class="`fi fi-${(country?.Code || '').trim().toLowerCase()}`"></span>
                                                                    <span class="font-medium flex-1 truncate"
                                                                        x-text="country.Name"></span>
                                                                    <span class="text-gray-200"
                                                                        x-text="'+' + country.Phon_Code"></span>
                                                                    <span
                                                                        x-show="selectedCountry && selectedCountry.Code === country.Code"
                                                                        class="ml-2 text-blue-500"><i
                                                                            class="bi bi-check-circle-fill"></i></span>
                                                                </li>
                                                            </template>
                                                            <li x-show="filteredCountries.length === 0"
                                                                class="px-4 py-2 text-sm text-gray-500">No country
                                                                found.</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="sm:col-span-2">
                                            <input type="tel" id="landline_number_{{ $index }}"
                                                wire:model.live="landlines.{{ $index }}.Land_Numb"
                                                placeholder="Landline Number" maxlength="15"
                                                class="form-input-figma text-sm w-full"
                                                :class="{ 'border-red-500 ring-red-500': @error('landlines.' . $index . '.Land_Numb') true @else false @enderror }"
                                                x-on:input="$event.target.value = $event.target.value.replace(/[^0-9+]/g, '').replace(/(\+[0-9]{1,3}|[0-9]{1,15})/, '$1')"
                                                autocomplete="tel" />
                                            @error('landlines.' . $index . '.Land_Numb')
                                                <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="sm:col-span-1">
                                            <select id="landline_type_{{ $index }}"
                                                wire:model.live="landlines.{{ $index }}.Land_Type"
                                                class="form-select-figma text-sm w-full"
                                                :class="{ 'border-red-500 ring-red-500': @error('landlines.' . $index . '.Land_Type') true @else false @enderror }">
                                                <option value="home">Home</option>
                                                <option value="office">Office</option>
                                            </select>
                                            @error('landlines.' . $index . '.Land_Type')
                                                <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                    </div>
                                </div>
                            @empty
                                <p class="text-slate-500 text-sm pl-1 italic">No landline numbers added yet.</p>
                            @endforelse
                        </div>

                        @if ($this->canAdd('landlines'))
                            <button type="button" wire:click="addLandline" wire:loading.attr="disabled"
                                class="mt-4 text-sm font-semibold capitalize text-blue-400 hover:text-blue-300 flex items-center gap-2">
                                <i class="bi bi-plus-circle"></i> Add Landline number
                            </button>
                        @endif
                    </div>

                    <!-- Emails -->
                    <div>
                        <h3
                            class="font-medium text-white mb-4 flex items-center gap-2 text-sm uppercase tracking-wide">
                            <i class="bi bi-envelope-fill text-green-400"></i> Email Addresses
                        </h3>
                        <div class="space-y-4">
                            @forelse ($emails as $index => $email)
                                <div class="relative p-4 rounded-md bg-slate-700/30 border border-slate-600"
                                    wire:key="email-{{ $index }}">
                                    <!-- Controls -->
                                    <div class="relative right-0 top-0 flex items-center justify-end gap-4 pb-2">
                                        <label for="primary_email_{{ $index }}"
                                            class="flex items-center cursor-pointer gap-2">
                                            <span class="text-sm font-medium text-white">Preferable</span>
                                            <input type="radio" id="primary_email_{{ $index }}"
                                                name="primary_email"
                                                wire:click="setPrimaryEmail({{ $index }})"
                                                @if ($email['Is_Prmy'] ?? false) checked @endif
                                                class="form-radio-figma">
                                        </label>
                                        @if (count($emails) > 1)
                                            <button type="button" wire:click="removeEmail({{ $index }})"
                                                wire:confirm="Are you sure you want to remove this email?"
                                                class="text-slate-500 hover:text-red-500 transition-colors">
                                                <i class="bi bi-trash-fill text-lg"></i>
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Email Fields -->
                                    <div class="flex-grow grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div class="sm:col-span-2">
                                            <input type="email"
                                                wire:model.live="emails.{{ $index }}.Emai_Addr"
                                                placeholder="example@domain.com"
                                                class="form-input-figma text-sm w-full lowercase"
                                                :class="{ 'border-red-500 ring-red-500': @error('emails.' . $index . '.Emai_Addr') true @else false @enderror }">
                                            @error('emails.' . $index . '.Emai_Addr')
                                                <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <select wire:model.blur="emails.{{ $index }}.Emai_Type"
                                            class="form-select-figma text-sm w-full"
                                            :class="{ 'border-red-500 ring-red-500': @error('emails.' . $index . '.Emai_Type') true @else false @enderror }">
                                            <option value="Self Generated">Personal</option>
                                            <option value="Office">Office</option>
                                            <option value="Business">Business</option>
                                        </select>
                                        @error('emails.' . $index . '.Emai_Type')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @empty
                                <p class="text-slate-500 text-sm pl-1">No email addresses added.</p>
                            @endforelse
                        </div>
                        @if ($this->canAdd('emails'))
                            <button type="button" wire:click="addEmail" wire:loading.attr="disabled"
                                class="mt-4 text-sm font-semibold capitalize text-blue-400 hover:text-blue-300 flex items-center gap-2">
                                <i class="bi bi-plus-circle"></i> Add Email
                            </button>
                        @endif
                    </div>
                </div>
            </div>



            <!-- Web Presence Card -->
            <div class="figma-card">
                <h2 class="figma-card-header text-green-200"><i class="bi bi-globe2 mr-2"></i>Web Presence</h2>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Website -->
                    <div class="relative">
                        <i
                            class="bi bi-globe text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="url" wire:model.live="Web" placeholder="Website"
                            class="form-input-figma w-full pl-10"
                            :class="{ 'border-red-500 ring-red-500': @error('Web') true @else false @enderror }" />
                        @error('Web')
                            <p class="mt-2 text-xs text-red-400 ml-10">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- LinkedIn -->
                    <div class="relative">
                        <i
                            class="bi bi-linkedin text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="url" wire:model.live="LnDn" placeholder="LinkedIn"
                            class="form-input-figma w-full pl-10"
                            :class="{ 'border-red-500 ring-red-500': @error('LnDn') true @else false @enderror }" />
                        @error('LnDn')
                            <p class="mt-2 text-xs text-red-400 ml-10">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Twitter/X -->
                    <div class="relative">
                        <i
                            class="bi bi-twitter-x text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="url" wire:model.live="Twtr" placeholder="Twitter / X"
                            class="form-input-figma w-full pl-10"
                            :class="{ 'border-red-500 ring-red-500': @error('Twtr') true @else false @enderror }" />
                        @error('Twtr')
                            <p class="mt-2 text-xs text-red-400 ml-10">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Facebook -->
                    <div class="relative">
                        <i
                            class="bi bi-facebook text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="url" wire:model.live="FcBk" placeholder="Facebook"
                            class="form-input-figma w-full pl-10"
                            :class="{ 'border-red-500 ring-red-500': @error('FcBk') true @else false @enderror }" />
                        @error('FcBk')
                            <p class="mt-2 text-xs text-red-400 ml-10">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Instagram -->
                    <div class="relative">
                        <i
                            class="bi bi-instagram text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="url" wire:model.live="Intg" placeholder="Instagram"
                            class="form-input-figma w-full pl-10"
                            :class="{ 'border-red-500 ring-red-500': @error('Intg') true @else false @enderror }" />
                        @error('Intg')
                            <p class="mt-2 text-xs text-red-400 ml-10">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reddit -->
                    <div class="relative">
                        <i
                            class="bi bi-reddit text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="url" wire:model.live="Redt" placeholder="Reddit"
                            class="form-input-figma w-full pl-10"
                            :class="{ 'border-red-500 ring-red-500': @error('Redt') true @else false @enderror }" />
                        @error('Redt')
                            <p class="mt-2 text-xs text-red-400 ml-10">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- YouTube -->
                    <div class="relative">
                        <i
                            class="bi bi-youtube text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <input type="url" wire:model.live="Ytb" placeholder="YouTube"
                            class="form-input-figma w-full pl-10"
                            :class="{ 'border-red-500 ring-red-500': @error('Ytb') true @else false @enderror }" />
                        @error('Ytb')
                            <p class="mt-2 text-xs text-red-400 ml-10">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Yahoo -->
                    <div class="relative">
                        <i class="text-slate-500 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none">Y!</i>
                        <input type="url" wire:model.live="Yaho" placeholder="Yahoo"
                            class="form-input-figma w-full pl-10"
                            :class="{ 'border-red-500 ring-red-500': @error('Yaho') true @else false @enderror }" />
                        @error('Yaho')
                            <p class="mt-2 text-xs text-red-400 ml-10">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Address Card -->
            <div class="figma-card">
                <h2 class="figma-card-header text-green-200"><i class="bi bi-geo-alt-fill mr-2"></i>Address</h2>
                <div class="p-6 space-y-6">
                    @foreach ($addresses as $index => $address)
                        <div class="relative p-5 rounded-md bg-slate-700/30 border border-slate-600"
                            wire:key="address-{{ $index }}">
                            <!-- TOP ROW: Address Type (Left) + Actions (Right) -->
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <!-- Address Type -->
                                <div class="flex-1">
                                    <label class="block text-sm mb-1 font-medium text-gray-100">Address Type</label>
                                    <div class="relative">
                                        <select
                                            wire:model.live="addresses.{{ $index }}.Admn_Addr_Type_Mast_UIN"
                                            class="form-select-figma w-fit @error('addresses.' . $index . '.Admn_Addr_Type_Mast_UIN') border-red-500 @enderror">
                                            <option value="">Select</option>
                                            @if ($Prty === 'B')
                                                @foreach ($BaddressTypes as $type)
                                                    <option value="{{ $type->Admn_Addr_Type_Mast_UIN }}">
                                                        {{ $type->Name }}
                                                    </option>
                                                @endforeach
                                            @elseif ($Prty === 'I')
                                                @foreach ($addressTypes as $type)
                                                    <option value="{{ $type->Admn_Addr_Type_Mast_UIN }}">
                                                        {{ $type->Name }}
                                                    </option>
                                                @endforeach
                                            @endif

                                        </select>
                                        @error('addresses.' . $index . '.Admn_Addr_Type_Mast_UIN')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('addresses.' . $index . '.Admn_Addr_Type_Mast_UIN')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Primary & Remove Actions -->
                                <div class="flex items-center gap-4 ">
                                    <label for="primary_address_{{ $index }}"
                                        class="flex items-center cursor-pointer gap-2 whitespace-nowrap">
                                        <span class="text-sm font-medium text-white">Primary</span>
                                        <input type="radio" id="primary_address_{{ $index }}"
                                            name="primary_address"
                                            wire:click="setPrimaryAddress({{ $index }})"
                                            @if ($address['Is_Prmy'] ?? false) checked @endif
                                            class="form-radio-figma">
                                    </label>
                                    @if (count($addresses) > 1)
                                        <button type="button" wire:click="removeAddress({{ $index }})"
                                            class="text-white hover:text-red-500 transition-colors"
                                            title="Remove Address">
                                            <i class="bi bi-trash-fill text-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Address Fields Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Country -->
                                <div>
                                    <label class="block text-sm mb-1 font-medium text-gray-100">Country</label>
                                    <div class="relative">
                                        <select wire:model.live="addresses.{{ $index }}.Admn_Cutr_Mast_UIN"
                                            class="form-select-figma w-full @error('addresses.' . $index . '.Admn_Cutr_Mast_UIN') border-red-500 @enderror">
                                            <option value="">Select Country...</option>
                                            @foreach ($allCountries as $country)
                                                <option value="{{ $country->Admn_Cutr_Mast_UIN }}">
                                                    {{ $country->Name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('addresses.' . $index . '.Admn_Cutr_Mast_UIN')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('addresses.' . $index . '.Admn_Cutr_Mast_UIN')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- State -->
                                <div>
                                    <label class="block text-sm mb-1 font-medium text-gray-100">State</label>
                                    <div class="relative">
                                        <select wire:model.live="addresses.{{ $index }}.Admn_Stat_Mast_UIN"
                                            class="form-select-figma w-full @error('addresses.' . $index . '.Admn_Stat_Mast_UIN') border-red-500 @enderror"
                                            @if (empty($address['statesForDropdown'] ?? [])) disabled @endif>
                                            <option value="">Select State...</option>
                                            @foreach ($address['statesForDropdown'] ?? [] as $state)
                                                <option value="{{ $state['Admn_Stat_Mast_UIN'] }}">
                                                    {{ $state['Name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('addresses.' . $index . '.Admn_Stat_Mast_UIN')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('addresses.' . $index . '.Admn_Stat_Mast_UIN')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- District -->
                                <div>
                                    <label class="block text-sm mb-1 font-medium text-gray-100">District</label>
                                    <div class="relative">
                                        <select wire:model.live="addresses.{{ $index }}.Admn_Dist_Mast_UIN"
                                            class="form-select-figma w-full @error('addresses.' . $index . '.Admn_Dist_Mast_UIN') border-red-500 @enderror"
                                            @if (empty($address['districtsForDropdown'] ?? [])) disabled @endif>
                                            <option value="">Select District...</option>
                                            @foreach ($address['districtsForDropdown'] ?? [] as $district)
                                                <option value="{{ $district['Admn_Dist_Mast_UIN'] }}">
                                                    {{ $district['Name'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('addresses.' . $index . '.Admn_Dist_Mast_UIN')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('addresses.' . $index . '.Admn_Dist_Mast_UIN')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Pincode -->
                                <div>
                                    <label class="block text-sm mb-1 font-medium text-gray-100">Pincode</label>
                                    <div class="relative">
                                        <select wire:model.live="addresses.{{ $index }}.Admn_PinCode_Mast_UIN"
                                            class="form-select-figma w-full @error('addresses.' . $index . '.Admn_PinCode_Mast_UIN') border-red-500 @enderror"
                                            @if (empty($address['pincodesForDropdown'] ?? [])) disabled @endif>
                                            <option value="">Select Pincode...</option>
                                            @foreach ($address['pincodesForDropdown'] ?? [] as $pincode)
                                                <option value="{{ $pincode['Admn_PinCode_Mast_UIN'] }}">
                                                    {{ $pincode['Code'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('addresses.' . $index . '.Admn_PinCode_Mast_UIN')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('addresses.' . $index . '.Admn_PinCode_Mast_UIN')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Landmark -->
                                <div>
                                    <label class="block text-sm mb-1 font-medium text-gray-100">Landmark</label>
                                    <div class="relative">
                                        <input type="text" wire:model.live="addresses.{{ $index }}.Lndm"
                                            class="form-input-figma w-full @error('addresses.' . $index . '.Lndm') border-red-500 @enderror"
                                            placeholder="Near By School, Petrol Pump, Hospital or Famous Place">
                                        @error('addresses.' . $index . '.Lndm')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('addresses.' . $index . '.Lndm')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Street/Locality -->
                                <div>
                                    <label class="block text-sm mb-1 font-medium text-gray-100">Street/Locality</label>
                                    <div class="relative">
                                        <input type="text" wire:model.live="addresses.{{ $index }}.Loca"
                                            class="form-input-figma w-full @error('addresses.' . $index . '.Loca') border-red-500 @enderror"
                                            placeholder="Area, Street, Sector, Village">
                                        @error('addresses.' . $index . '.Loca')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('addresses.' . $index . '.Loca')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Address Line (Full Width) -->
                                <div class="sm:col-span-2">
                                    <label class="block text-sm mb-1 font-medium text-gray-100">Flat, House No,
                                        Building Name, Company</label>
                                    <div class="relative">
                                        <textarea wire:model.live="addresses.{{ $index }}.Addr" rows="2"
                                            class="form-input-figma w-full @error('addresses.' . $index . '.Addr') border-red-500 @enderror"
                                            placeholder="Flat, House No, Building Name, Company..."></textarea>
                                        @error('addresses.' . $index . '.Addr')
                                            <div
                                                class="absolute top-0 right-0 pt-3 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('addresses.' . $index . '.Addr')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Add Another -->
                    @if ($this->canAdd('addresses'))
                        <button type="button" wire:click="addAddress"
                            class="text-sm font-semibold text-blue-500 hover:text-blue-300 transition-colors flex items-center gap-2">
                            <i class="bi bi-plus-circle"></i> Add Another Address
                        </button>
                    @endif
                </div>
            </div>


            @if ($Prty === 'I')
                <!-- Education Card -->
                <div class="figma-card">
                    <h2 class="figma-card-header text-green-200"><i class="bi bi-book-fill mr-2"></i>Education</h2>
                    <div class="p-6 space-y-6">
                        @forelse ($this->educations as $index => $education)
                            <div class="relative p-5 border border-white/[.10] rounded-md"
                                wire:key="education-{{ $index }}">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <!-- Degree Name -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">Degree Name</label>
                                        <input type="text"
                                            wire:model.blur="educations.{{ $index }}.Deg_Name"
                                            placeholder="Bachelor of Science" class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('educations.' . $index . '.Deg_Name') true @else false @enderror }">
                                        @error('educations.' . $index . '.Deg_Name')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Institution -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">School / College /
                                            University / Institution Name</label>
                                        <input type="text"
                                            wire:model.blur="educations.{{ $index }}.Inst_Name"
                                            placeholder="University Name" class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('educations.' . $index . '.Inst_Name') true @else false @enderror }">
                                        @error('educations.' . $index . '.Inst_Name')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Completion Year -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">Completion Year</label>
                                        <input type="text" inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                                            wire:model.live="educations.{{ $index }}.Cmpt_Year"
                                            placeholder="YYYY" class="form-input-figma mt-1">
                                    </div>

                                    <!-- Country -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">Country</label>
                                        <select wire:model="educations.{{ $index }}.Admn_Cutr_Mast_UIN"
                                            class="form-input-figma mt-1">
                                            <option value="">Select Country</option>
                                            @foreach ($allCountries as $c)
                                                <option value="{{ $c->Admn_Cutr_Mast_UIN }}">{{ $c->Name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Delete Button -->
                                <div class="absolute top-4 right-4">
                                    @if (count($this->educations) > 1 || true)
                                        <button type="button" wire:click="removeEducation({{ $index }})"
                                            class="text-slate-500 hover:text-red-500 transition-colors">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm">No education added.</p>
                        @endforelse

                        @if ($this->canAdd('educations'))
                            <button type="button" wire:click="addEducation"
                                class="text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
                                <i class="bi bi-plus-circle"></i> Add Education
                            </button>
                        @endif

                        @error('educations')
                            <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Skill Card -->
                <div class="figma-card">
                    <h2 class="figma-card-header text-green-200"><i class="bi bi-star-fill mr-2"></i>Skills</h2>
                    <div class="p-6 space-y-6">
                        @forelse ($this->skills as $index => $skill)
                            <div class="relative p-5 border border-white/[.10] rounded-md"
                                wire:key="skill-{{ $index }}">

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                                    <div>
                                        <label class="block text-sm font-medium text-white">Worked On</label>
                                        <select wire:model.live="skills.{{ $index }}.Skil_Type"
                                            class="form-select-figma w-full"
                                            :class="{ 'border-red-500 ring-red-500': @error('skills.' . $index . '.Skil_Type') true @else false @enderror }">
                                            <option value="">..Select Worked On..</option>
                                            @foreach ($skillTypes as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                            <option value="Not in List"> Not in List </option>
                                        </select>
                                        @error('skills.' . $index . '.Skil_Type')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <div class="flex items-center mb-1"> <label
                                                class="block text-sm font-medium text-white">Proficiency Level</label>
                                            <span
                                                class="inline-block px-2 ml-2 py-0.5 bg-purple-500/20 text-purple-300 rounded text-xs font-semibold border border-purple-500/50">
                                                {{ $skill['Profc_Lvl'] ?? 0 }}/5
                                            </span>
                                        </div>
                                        <div class="flex items-center h-[42px]">
                                            <input type="range"
                                                wire:model.live="skills.{{ $index }}.Profc_Lvl"
                                                min="1" max="5"
                                                class="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-purple-500">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white">Skill Name</label>
                                        <select wire:model.live="skills.{{ $index }}.Skil_Type_1"
                                            class="form-select-figma w-full">
                                            <option value="">Select Skill Name..</option>
                                            @foreach ($skillSubtypes as $subtype)
                                                <option value="{{ $subtype }}">{{ $subtype }}</option>
                                            @endforeach
                                            <option value="Other"> Other Skill </option>
                                        </select>
                                        @error('skills.' . $index . '.Skil_Type_1')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white">
                                            Enter Skill Name (if not found in the list)
                                            @if ($skill['Skil_Type_1'] === 'Other')
                                            @endif
                                        </label>
                                        <input type="text" wire:model.blur="skills.{{ $index }}.Skil_Name"
                                            placeholder="Enter Skill Name.."
                                            @if ($skill['Skil_Type_1'] !== 'Other') disabled @endif
                                            class="form-input-figma w-full disabled:bg-slate-700/50 disabled:text-slate-500 disabled:cursor-not-allowed"
                                            :class="{ 'border-red-500 ring-red-500': @error('skills.' . $index . '.Skil_Name') true @else false @enderror }">
                                        @error('skills.' . $index . '.Skil_Name')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>

                                <div class="absolute top-2 right-2">
                                    <button type="button" wire:click="removeSkill({{ $index }})"
                                        class="p-2 text-slate-500 hover:text-red-500 transition-colors">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm">No skills added.</p>
                        @endforelse

                        @if ($this->canAdd('skills'))
                            <button type="button" wire:click="addSkill"
                                class="text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
                                <i class="bi bi-plus-circle"></i> Add Skill
                            </button>
                        @endif

                        @error('skills')
                            <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Employment Card -->
                <div class="figma-card">
                    <h2 class="figma-card-header text-green-200"><i class="bi bi-briefcase-fill mr-2"></i>Present
                        Employment</h2>
                    <div class="p-6 space-y-6">
                        <!-- Employment Type Toggle -->
                        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-900 rounded-xl">
                            <button type="button" wire:click="$set('Empl_Type', 'job')"
                                class="px-2 py-1.5 text-sm font-semibold rounded-md transition-colors duration-200"
                                :class="{ 'bg-blue-600 text-green-200 shadow-md': @js($Empl_Type) === 'job', 'text-white hover:bg-slate-700': @js($Empl_Type) !== 'job' }">
                                Job
                            </button>
                            <button type="button" wire:click="$set('Empl_Type', 'self-employed')"
                                class="px-2 py-1.5 text-sm font-semibold rounded-md transition-colors duration-200"
                                :class="{ 'bg-blue-600 text-green-200 shadow-md': @js($Empl_Type) === 'self-employed', 'text-white hover:bg-slate-700': @js($Empl_Type) !== 'self-employed' }">
                                Self Employed
                            </button>
                        </div>

                        <!-- Job Fields -->
                        @if ($Empl_Type === 'job')
                            <div class="space-y-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <!-- Company Name -->
                                    <div>
                                        <label for="Comp_Name" class="text-sm font-medium text-white">Company
                                            Name</label>
                                        <input type="text" id="Comp_Name" wire:model.blur="Comp_Name"
                                            placeholder="e.g., ABC Corporation" class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('Comp_Name') true @else false @enderror }">
                                        @error('Comp_Name')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Designation -->
                                    <div>
                                        <label for="Comp_Dsig"
                                            class="text-sm font-medium text-white">Designation</label>
                                        <input type="text" id="Comp_Dsig" wire:model.blur="Comp_Dsig"
                                            placeholder="e.g., Senior Manager,Software Engineer"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('Comp_Dsig') true @else false @enderror }">
                                        @error('Comp_Dsig')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Company Landline -->
                                    <div>
                                        <label for="Comp_LdLi" class="text-sm font-medium text-white">Company
                                            Landline</label>
                                        <input type="tel" id="Comp_LdLi" wire:model.live="Comp_LdLi"
                                            placeholder="e.g., 02012345678" class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('Comp_LdLi') true @else false @enderror }"
                                            x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')">
                                        @error('Comp_LdLi')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Company Business Description -->
                                    <div>
                                        <label for="Comp_Desp" class="text-sm font-medium text-white">Company Business
                                            Description</label>
                                        <input type="text" id="Comp_Desp" wire:model.blur="Comp_Desp"
                                            placeholder="e.g., Software Development, Manufacturing"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('Comp_Desp') true @else false @enderror }">
                                        @error('Comp_Desp')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Company Email -->
                                    <div>
                                        <label for="Comp_Emai" class="text-sm font-medium text-white">Company
                                            Email</label>
                                        <input type="email" id="Comp_Emai" wire:model.live="Comp_Emai"
                                            placeholder="e.g., info@company.com"
                                            class="form-input-figma mt-1 lowercase"
                                            :class="{ 'border-red-500 ring-red-500': @error('Comp_Emai') true @else false @enderror }">
                                        @error('Comp_Emai')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Company Website -->
                                    <div>
                                        <label for="Comp_Web" class="text-sm font-medium text-white">Company
                                            Website</label>
                                        <input type="url" id="Comp_Web" wire:model.live="Comp_Web"
                                            placeholder="https://company.com" class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('Comp_Web') true @else false @enderror }">
                                        @error('Comp_Web')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Company Address -->
                                <div>
                                    <label for="Comp_Addr" class="text-sm font-medium text-white">Company
                                        Address</label>
                                    <textarea id="Comp_Addr" wire:model.blur="Comp_Addr" rows="3"
                                        placeholder="Enter complete company address including street, city, state, and postal code"
                                        class="form-input-figma mt-1"
                                        :class="{ 'border-red-500 ring-red-500': @error('Comp_Addr') true @else false @enderror }"></textarea>
                                    @error('Comp_Addr')
                                        <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <!-- Self-Employed Fields -->
                        @if ($Empl_Type === 'self-employed')
                            <div class="space-y-5">
                                <!-- Profession/Service -->
                                <div>
                                    <label for="Prfl_Name" class="text-sm font-medium text-white">Profession /
                                        Service</label>
                                    <input type="text" id="Prfl_Name" wire:model.live="Prfl_Name"
                                        placeholder="e.g., Graphic Designer, Consultant, Freelance Developer"
                                        class="form-input-figma mt-1"
                                        :class="{ 'border-red-500 ring-red-500': @error('Prfl_Name') true @else false @enderror }">
                                    @error('Prfl_Name')
                                        <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Business Address -->
                                <div>
                                    <label for="Prfl_Addr" class="text-sm font-medium text-white">Business
                                        Address</label>
                                    <textarea id="Prfl_Addr" wire:model.live="Prfl_Addr" rows="3"
                                        placeholder="Enter your business address or work location" class="form-input-figma mt-1"
                                        :class="{ 'border-red-500 ring-red-500': @error('Prfl_Addr') true @else false @enderror }"></textarea>
                                    @error('Prfl_Addr')
                                        <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Work Experience Card -->
                <div class="figma-card">
                    <h2 class="figma-card-header text-green-200"><i class="bi bi-briefcase-fill mr-2"></i>Past Working
                        Experience
                    </h2>
                    <div class="p-6 space-y-6">
                        @forelse ($this->workExperiences as $index => $work)
                            <div class="relative p-5 border border-white/[.10] rounded-md"
                                wire:key="work-{{ $index }}">
                                <!-- Header -->
                                <div class="mb-4">
                                    <p class="text-white font-semibold">Previous Organization Details</p>
                                </div>

                                <!-- Organization & Designation -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4">
                                    <!-- Organization -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">Organization</label>
                                        <input type="text"
                                            wire:model.blur="workExperiences.{{ $index }}.Orga_Name"
                                            placeholder="Company Name" class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('workExperiences.' . $index . '.Orga_Name') true @else false @enderror }">
                                        @error('workExperiences.' . $index . '.Orga_Name')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Designation -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">Designation</label>
                                        <input type="text"
                                            wire:model.blur="workExperiences.{{ $index }}.Dsgn"
                                            placeholder="Job Title" class="form-input-figma mt-1">
                                    </div>
                                </div>

                                <!-- Dates & Type -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4">
                                    <!-- From Date -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">From</label>
                                        <input type="date"
                                            wire:model.blur="workExperiences.{{ $index }}.Prd_From"
                                            class="form-input-figma mt-1">
                                        @error('workExperiences.' . $index . '.Prd_From')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- To Date -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">To</label>
                                        <input type="date"
                                            wire:model.blur="workExperiences.{{ $index }}.Prd_To"
                                            class="form-input-figma mt-1">
                                        @error('workExperiences.' . $index . '.Prd_To')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Country & Description -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4">
                                    <!-- Country -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">Country</label>
                                        <select wire:model="workExperiences.{{ $index }}.Admn_Cutr_Mast_UIN"
                                            class="form-input-figma mt-1">
                                            <option value="">Select Country</option>
                                            @foreach ($allCountries as $c)
                                                <option value="{{ $c->Admn_Cutr_Mast_UIN }}">{{ $c->Name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Work Type -->
                                    <div>
                                        <label class="block text-sm font-medium text-white">Work Type</label>
                                        <select wire:model="workExperiences.{{ $index }}.Work_Type"
                                            class="form-input-figma mt-1">
                                            <option value="">Select Work Type...</option>
                                            @foreach ($workTypes as $type => $title)
                                                <option value="{{ $type }}">
                                                    {{ $type }} ({{ $title }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                                <!-- Job Description -->
                                <div>
                                    <label class="block text-sm font-medium text-white">Job Description</label>
                                    <input type="text"
                                        wire:model.blur="workExperiences.{{ $index }}.Job_Desp"
                                        placeholder="Key responsibilities..."
                                        class="form-input-figma mt-1 resize-none"></textarea>
                                </div>
                                <!-- Delete Button -->
                                <div class="absolute top-4 right-4">
                                    <button type="button" wire:click="removeWorkExperience({{ $index }})"
                                        class="text-slate-500 hover:text-red-500 transition-colors">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm">No work experience added.</p>
                        @endforelse

                        @if ($this->canAdd('workExperiences'))
                            <button type="button" wire:click="addWorkExperience" wire:loading.attr="disabled"
                                class="mt-4 text-sm font-semibold capitalize text-blue-400 hover:text-blue-300 flex items-center gap-2">
                                <i class="bi bi-plus-circle"></i> Add Experience
                            </button>
                        @endif

                        @error('workExperiences')
                            <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

            @endif


            @if ($Prty === 'I')
                <div class="figma-card">
                    <h2 class="figma-card-header text-green-200"><i class="bi bi-people-fill mr-2"></i>Reference
                        Persons</h2>
                    <div class="p-6 space-y-6">
                        @forelse ($references as $index => $reference)
                            <div class="relative p-5 border border-white/[.10] rounded-md bg-slate-700/30 border-slate-600"
                                wire:key="reference-{{ $index }}">

                                <div class="flex items-center justify-end gap-4 mb-4">
                                    <label for="primary_ref_{{ $index }}"
                                        class="flex items-center cursor-pointer gap-2 select-none">
                                        <span class="text-sm font-medium text-slate-300">
                                            Primary
                                        </span>
                                        <input type="radio" id="primary_ref_{{ $index }}"
                                            name="primary_reference_group"
                                            wire:click="setPrimaryReference({{ $index }})"
                                            @if ($reference['Is_Prmy'] ?? false) checked @endif
                                            class="form-radio-figma">
                                    </label>
                                    @if (count($references) > 1)
                                        <button type="button" wire:click="removeReference({{ $index }})"
                                            wire:confirm="Are you sure you want to remove this reference person?"
                                            class="text-slate-500 hover:text-red-500 transition-colors p-1"
                                            title="Remove Reference">
                                            <i class="bi bi-trash-fill text-lg"></i>
                                        </button>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-white">Reference Person
                                            Name</label>
                                        <input type="text"
                                            wire:model.blur="references.{{ $index }}.Refa_Name"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('references.' . $index . '.Refa_Name') true @else false @enderror }"
                                            placeholder="John Doe">
                                        @error('references.' . $index . '.Refa_Name')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white">Relationship</label>
                                        <input type="text"
                                            wire:model.blur="references.{{ $index }}.Refa_Rsip"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('references.' . $index . '.Refa_Rsip') true @else false @enderror }"
                                            placeholder="Colleague, Friend, Family...">
                                        @error('references.' . $index . '.Refa_Rsip')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white">Email Address</label>
                                        <input type="email"
                                            wire:model.live="references.{{ $index }}.Refa_Emai"
                                            class="form-input-figma mt-1 lowercase"
                                            :class="{ 'border-red-500 ring-red-500 ': @error('references.' . $index . '.Refa_Emai') true @else false @enderror }"
                                            placeholder="john.doe@example.com">
                                        @error('references.' . $index . '.Refa_Emai')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white">Mobile Number</label>
                                        <input type="tel"
                                            wire:model.live="references.{{ $index }}.Refa_Phon"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('references.' . $index . '.Refa_Phon') true @else false @enderror }"
                                            placeholder="9876543210"
                                            x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')">
                                        @error('references.' . $index . '.Refa_Phon')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm">No references added.</p>
                        @endforelse

                        @if ($this->canAdd('references'))
                            <button type="button" wire:click="addReference"
                                class="text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
                                <i class="bi bi-plus-circle"></i> Add Reference
                            </button>
                        @endif

                        @error('references')
                            <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endif

            @if ($Prty === 'B')
                <div class="figma-card">
                    <h2 class="figma-card-header text-green-200"><i class="bi bi-people-fill mr-2"></i>Authorized
                        Person</h2>
                    <div class="p-6 space-y-6">
                        @forelse ($references as $index => $reference)
                            <div class="relative p-5 border border-white/[.10] rounded-md bg-slate-700/30 border-slate-600"
                                wire:key="reference-{{ $index }}">

                                <div class="flex items-center justify-end gap-4 mb-4">
                                    <label for="primary_auth_{{ $index }}"
                                        class="flex items-center cursor-pointer gap-2 select-none">
                                        <span class="text-sm font-medium text-slate-300">
                                            Primary
                                        </span>
                                        <input type="radio" id="primary_auth_{{ $index }}"
                                            name="primary_auth_group"
                                            wire:click="setPrimaryReference({{ $index }})"
                                            @if ($reference['Is_Prmy'] ?? false) checked @endif
                                            class="form-radio-figma">
                                    </label>

                                    <div class="flex items-center justify-end gap-4 mb-4">
                                        @if (count($references) > 1)
                                            <button type="button"
                                                wire:click="removeReference({{ $index }})"
                                                wire:confirm="Are you sure you want to remove this authorized person?"
                                                class="text-slate-500 hover:text-red-500 transition-colors p-1"
                                                title="Remove Authorized Person">
                                                <i class="bi bi-trash-fill text-lg"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-white">Authorized Person
                                            Name</label>
                                        <input type="text"
                                            wire:model.blur="references.{{ $index }}.Refa_Name"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('references.' . $index . '.Refa_Name') true @else false @enderror }"
                                            placeholder="John Doe">
                                        @error('references.' . $index . '.Refa_Name')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white">Designation</label>
                                        <input type="text"
                                            wire:model.blur="references.{{ $index }}.Refa_Rsip"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('references.' . $index . '.Refa_Rsip') true @else false @enderror }"
                                            placeholder="Colleague, Friend, Family...">
                                        @error('references.' . $index . '.Refa_Rsip')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white">Email Address</label>
                                        <input type="email"
                                            wire:model.live="references.{{ $index }}.Refa_Emai"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('references.' . $index . '.Refa_Emai') true @else false @enderror }"
                                            placeholder="john.doe@example.com">
                                        @error('references.' . $index . '.Refa_Emai')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-white">Mobile Number</label>
                                        <input type="tel"
                                            wire:model.live="references.{{ $index }}.Refa_Phon"
                                            class="form-input-figma mt-1"
                                            :class="{ 'border-red-500 ring-red-500': @error('references.' . $index . '.Refa_Phon') true @else false @enderror }"
                                            placeholder="9876543210"
                                            x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')">
                                        @error('references.' . $index . '.Refa_Phon')
                                            <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 text-sm">No authorized persons added.</p>
                        @endforelse

                        @if ($this->canAdd('references'))
                            <button type="button" wire:click="addReference"
                                class="text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
                                <i class="bi bi-plus-circle"></i> Add authorized person
                            </button>
                        @endif

                        @error('references')
                            <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endif

            <!-- Bank Accounts Card -->
            <div class="figma-card">
                <h2 class="figma-card-header text-green-200">
                    <i class="bi bi-bank mr-2"></i> Bank Accounts
                </h2>
                <div class="p-4 sm:p-6 space-y-6">

                    @forelse ($bankAccounts as $index => $bank)
                        <div class="p-4 sm:p-5 rounded-md bg-slate-700/30 border border-slate-600"
                            wire:key="bank-{{ $index }}">

                            <!-- Primary & Remove Buttons (Desktop & Mobile Responsive) -->
                            <div class="flex items-center justify-between mb-4 gap-4">
                                <div class="flex-1"></div> <!-- Spacer -->
                                <div class="flex items-center gap-4">
                                    <label for="primary_bank_{{ $index }}"
                                        class="flex items-center cursor-pointer gap-2 whitespace-nowrap">
                                        <span class="text-sm font-medium text-white">Primary</span>
                                        <input type="radio" id="primary_bank_{{ $index }}"
                                            name="primary_bank" wire:click="setPrimaryBank({{ $index }})"
                                            @if ($bank['Prmy'] ?? false) checked @endif
                                            class="form-radio-figma">
                                    </label>
                                    @if (count($bankAccounts) > 1)
                                        <button type="button" wire:click="removeBank({{ $index }})"
                                            class="text-gray-200 hover:text-red-500 transition-colors p-2 rounded hover:bg-red-500/10"
                                            title="Remove Bank">
                                            <i class="bi bi-trash-fill text-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Content Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">

                                <!-- Bank Name -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Bank Name
                                    </label>
                                    <div class="relative">
                                        <select wire:model.live="bankAccounts.{{ $index }}.Bank_Name_UIN"
                                            class="form-select-figma w-full text-sm @error('bankAccounts.' . $index . '.Bank_Name_UIN') border-red-500 @enderror">
                                            <option value="">Select Bank...</option>
                                            @foreach ($bankOptions as $option)
                                                <option value="{{ $option->Bank_UIN }}">{{ $option->Bank_Name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('bankAccounts.' . $index . '.Bank_Name_UIN')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('bankAccounts.' . $index . '.Bank_Name_UIN')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Branch Name -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Branch Name
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                            wire:model.live="bankAccounts.{{ $index }}.Bank_Brnc_Name"
                                            class="form-input-figma w-full text-sm @error('bankAccounts.' . $index . '.Bank_Brnc_Name') border-red-500 @enderror"
                                            placeholder="Enter branch name">
                                        @error('bankAccounts.' . $index . '.Bank_Brnc_Name')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('bankAccounts.' . $index . '.Bank_Brnc_Name')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Account Type -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Account Type
                                    </label>
                                    <div class="relative">
                                        <select wire:model.live="bankAccounts.{{ $index }}.Acnt_Type"
                                            class="form-select-figma w-full text-sm @error('bankAccounts.' . $index . '.Acnt_Type') border-red-500 @enderror">
                                             <option value="" disabled selected>Select Type...</option>
                                            <option value="Savings Account">Savings Account</option>
                                            <option value="Current Account">Current Account</option>
                                            <option value="Fixed Deposit Account">Fixed Deposit Account</option>
                                            <option value="Recurring Deposit Account">Recurring Deposit Account
                                            </option>
                                            <option value="NRI Account">NRI Account</option>
                                            <option value="DEMAT Account">DEMAT Account</option>
                                            <option value="Salary Account">Salary Account</option>
                                        </select>
                                        @error('bankAccounts.' . $index . '.Acnt_Type')
                                            <span class="text-red-400 text-xs mt-1 block flex items-center gap-1">
                                                <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                    @error('bankAccounts.' . $index . '.Acnt_Type')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Account Number -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Account Number
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                            wire:model.live="bankAccounts.{{ $index }}.Acnt_Numb"
                                            class="form-input-figma w-full text-sm @error('bankAccounts.' . $index . '.Acnt_Numb') border-red-500 @enderror"
                                            placeholder="Enter account number">
                                        @error('bankAccounts.' . $index . '.Acnt_Numb')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('bankAccounts.' . $index . '.Acnt_Numb')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- IFSC Code -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        International Financial Services Centre (IFSC) Code
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                            wire:model.live="bankAccounts.{{ $index }}.IFSC_Code"
                                            class="form-input-figma w-full text-sm @error('bankAccounts.' . $index . '.IFSC_Code') border-red-500 @enderror"
                                            placeholder="e.g., SBIN0001234(11 Characters)">
                                        @error('bankAccounts.' . $index . '.IFSC_Code')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('bankAccounts.' . $index . '.IFSC_Code')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Swift Code -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Society for Worldwide Interbank Financial Telecommunication (SWIFT) Code
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                            wire:model.live="bankAccounts.{{ $index }}.Swift_Code"
                                            class="form-input-figma w-full text-sm @error('bankAccounts.' . $index . '.Swift_Code') border-red-500 @enderror"
                                            placeholder="e.g., SBININBB123(11 or 8 Characters)">
                                        @error('bankAccounts.' . $index . '.Swift_Code')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('bankAccounts.' . $index . '.Swift_Code')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Attachments Section (Full Width) -->
                                <div class="col-span-1 sm:col-span-2">
                                    <div class="flex items-start gap-3">
                                        <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                            Attachments
                                            <span class="text-gray-400 text-xs block mt-1">
                                                (PDF/JPG/PNG - Max 100KB per file)
                                            </span>
                                        </label>

                                        <!-- Upload Icon Button -->
                                        <label for="bank-attachment-{{ $index }}"
                                            class="flex-shrink-0 pt-3 text-blue-400 hover:text-blue-300 cursor-pointer transition mt-0.5"
                                            title="Click to upload attachment">
                                            <span wire:loading.remove
                                                wire:target="bankAccounts.{{ $index }}.temp_upload">
                                                <i class="bi bi-paperclip text-lg"></i>
                                            </span>
                                            <span wire:loading
                                                wire:target="bankAccounts.{{ $index }}.temp_upload">
                                                <span
                                                    class="inline-block w-4 h-4 border-2 border-blue-400 border-t-blue-300 rounded-full animate-spin"></span>
                                            </span>
                                        </label>
                                    </div>

                                    <!-- Hidden File Input -->
                                    <input type="file"
                                        wire:model.live="bankAccounts.{{ $index }}.temp_upload"
                                        id="bank-attachment-{{ $index }}" accept=".pdf,.jpg,.png,.webp"
                                        multiple class="hidden">

                                    <!-- Validation Error -->
                                    @error('bankAccounts.' . $index . '.newAttachments.*')
                                        <div
                                            class="mt-2 text-red-400 text-xs bg-red-900/20 p-2.5 rounded border border-red-600/30 flex items-start gap-2">
                                            <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-0.5"></i>
                                            <span>{{ $message }}</span>
                                        </div>
                                    @enderror

                                    <!-- List of New Files to Upload -->
                                    @if (!empty($bank['newAttachments']))
                                        <div class="mt-3 space-y-1.5 mb-3">
                                            <p class="text-xs text-slate-400 font-medium">Files staged for upload:</p>
                                            @foreach ($bank['newAttachments'] as $attachmentIndex => $attachment)
                                                <div class="mt-2 p-2.5 text-xs sm:text-sm text-blue-300 bg-blue-900/20 border border-blue-600/30 rounded flex items-center justify-between gap-2"
                                                    wire:key="bank-{{ $index }}-new-att-{{ $attachmentIndex }}">
                                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                                        <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="font-medium truncate">
                                                                {{ $attachment->getClientOriginalName() }}
                                                            </p>
                                                            <p class="text-gray-400 text-xs">
                                                                {{ round($attachment->getSize() / 1024, 1) }} KB
                                                                (Ready to upload)
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                        wire:click="removeNewAttachment({{ $index }}, {{ $attachmentIndex }})"
                                                        class="text-blue-400 hover:text-red-400 transition-colors text-lg px-2 flex-shrink-0"
                                                        title="Remove file">
                                                        &times;
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- List of Existing Files -->
                                    @if (!empty($bank['existing_attachments']))
                                        <div class="mt-3 space-y-1.5">
                                            <p class="text-xs text-slate-400 font-medium">Attached files:</p>
                                            @foreach ($bank['existing_attachments'] as $attachment)
                                                <div class="mt-2 p-2.5 text-xs sm:text-sm text-green-300 bg-green-900/20 border border-green-600/30 rounded flex items-center justify-between gap-2"
                                                    wire:key="bank-{{ $index }}-existing-att-{{ $attachment['Admn_Bank_Attachment_UIN'] }}">
                                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                                        <i class="bi bi-paperclip flex-shrink-0"></i>
                                                        <div class="flex-1 min-w-0">
                                                            <a href="{{ Storage::url($attachment['Atch_Path']) }}"
                                                                target="_blank"
                                                                class="font-medium truncate hover:text-blue-400 hover:underline"
                                                                title="{{ $attachment['Orgn_Name'] }}">
                                                                {{ $attachment['Orgn_Name'] }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                        wire:click="deleteExistingAttachment({{ $index }}, {{ $attachment['Admn_Bank_Attachment_UIN'] }})"
                                                        wire:confirm="Are you sure you want to delete this attachment?"
                                                        class="text-green-400 hover:text-red-400 transition-colors text-lg px-2 flex-shrink-0"
                                                        title="Delete file">
                                                        &times;
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>


                            </div>
                        </div>
                    @empty
                        <p class="text-slate-500 text-sm text-center py-4">No bank accounts added.</p>
                    @endforelse

                    <!-- Add Bank Button -->
                    @if ($this->canAdd('banks'))
                        <button type="button" wire:click="addBank"
                            class="w-full sm:w-auto text-sm font-semibold text-blue-400 hover:text-blue-300 flex items-center justify-center sm:justify-start gap-2 px-4 py-2.5 hover:bg-blue-600/10 rounded transition">
                            <i class="bi bi-plus-circle"></i> Add Bank Account
                        </button>
                    @endif
                </div>
            </div>


            <!-- Documents Card -->
            <div class="figma-card">
                <h2 class="figma-card-header text-green-200">
                    <i class="bi bi-file-earmark-pdf mr-2"></i>
                    @if ($Prty === 'B')
                        Statutory
                    @endif
                    Documents
                </h2>
                <div class="p-4 sm:p-6 space-y-6">
                    @forelse ($documents as $index => $document)
                        <div class="p-4 sm:p-5 rounded-md bg-slate-700/30 border border-slate-600"
                            wire:key="document-{{ $index }}">

                            <div class="flex items-center justify-between mb-4 gap-4">
                                <div class="flex-1"></div>
                                <div class="flex items-center gap-4">
                                    <label for="primary_doc_{{ $index }}"
                                        class="flex items-center cursor-pointer gap-2 whitespace-nowrap group">
                                        <span
                                            class="text-sm font-medium text-white group-hover:text-blue-300 transition-colors">
                                            Primary
                                        </span>
                                        <input type="radio" id="primary_doc_{{ $index }}"
                                            {{-- NO NAME ATTRIBUTE --}}
                                            wire:click="setPrimaryDocument({{ $index }})"
                                            @checked($document['Prmy'] ?? false) class="form-radio-figma cursor-pointer" />
                                    </label>

                                    @if (count($documents) > 1)
                                        <button type="button" wire:click="removeDocument({{ $index }})"
                                            wire:confirm="Are you sure you want to remove this document? This action cannot be undone."
                                            class="text-gray-200 hover:text-red-500 transition-colors p-2 rounded hover:bg-red-500/10"
                                            title="Remove Document">
                                            <i class="bi bi-trash-fill text-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Content Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">

                                <!-- Document Type (Full Width) -->
                                <div class="col-span-1 sm:col-span-2">
                                    <label class="block text-sm mb-2 font-medium text-gray-100">
                                        Document Types
                                    </label>

                                    <!-- Selected Types as Chips -->
                                    @if (!empty($document['selected_types']))
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            @foreach ($document['selected_types'] as $typeId)
                                                @php
                                                    $docType = collect($allDocumentTypes)->firstWhere(
                                                        'Admn_Docu_Type_Mast_UIN',
                                                        $typeId,
                                                    );
                                                @endphp
                                                @if ($docType)
                                                    <div wire:key="chip-{{ $index }}-{{ $typeId }}"
                                                        class="inline-flex items-center gap-2 px-3 py-1 bg-blue-600/20 text-blue-300 rounded-full text-xs sm:text-sm border border-blue-600/30">
                                                        <span class="truncate">{{ $docType->Docu_Name }}</span>
                                                        <button type="button"
                                                            wire:click="removeDocumentType({{ $index }}, {{ $typeId }})"
                                                            class="text-blue-400 hover:text-red-400 transition-colors flex-shrink-0"
                                                            title="Remove type">
                                                            <i class="bi bi-x-circle-fill text-xs"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Dropdown to Add Document Types -->
                                    <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                        <button type="button" @click="open = !open"
                                            class="form-select-figma w-full text-left flex items-center justify-between text-sm sm:text-base">
                                            <span class="text-gray-400 truncate">
                                                @if (empty($document['selected_types']))
                                                    Select types...
                                                @else
                                                    {{ count($document['selected_types']) }} selected
                                                @endif
                                            </span>
                                            <i class="bi bi-chevron-down flex-shrink-0 ml-2"></i>
                                        </button>

                                        <!-- Dropdown Options -->
                                        <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            class="absolute z-50 mt-2 w-full rounded-md bg-slate-800 shadow-xl border border-slate-600 max-h-48 sm:max-h-60 overflow-auto left-0 right-0">
                                            <div class="py-1">
                                                @forelse($allDocumentTypes as $docType)
                                                    @if (!in_array($docType->Admn_Docu_Type_Mast_UIN, $document['selected_types'] ?? []))
                                                        <button type="button"
                                                            wire:click="selectDocumentType({{ $index }}, {{ $docType->Admn_Docu_Type_Mast_UIN }})"
                                                            @click="open = false"
                                                            class="w-full text-left px-3 sm:px-4 py-2.5 sm:py-2 text-sm text-gray-300 hover:bg-slate-700 hover:text-white transition-colors flex items-center gap-2 active:bg-slate-600">
                                                            <i
                                                                class="bi bi-plus-circle text-xs text-gray-500 flex-shrink-0"></i>
                                                            <span class="truncate">{{ $docType->Docu_Name }}</span>
                                                        </button>
                                                    @endif
                                                @empty
                                                    <div class="px-3 sm:px-4 py-2 text-sm text-gray-500">
                                                        No document types available
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    @error('documents.' . $index . '.selected_types')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Document Name -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Document Name
                                    </label>
                                    <div class="relative">
                                        <select wire:model.live="documents.{{ $index }}.Docu_Name"
                                            class="form-select-figma w-full text-sm @error('documents.' . $index . '.Docu_Name') border-red-500 @enderror">
                                            <option value="">Select...</option>
                                            @foreach ($documentNameOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        @error('documents.' . $index . '.Docu_Name')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('documents.' . $index . '.Docu_Name')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Registration Number -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Registration / Reference Number
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                            wire:model.live="documents.{{ $index }}.Regn_Numb"
                                            class="form-input-figma w-full text-sm @error('documents.' . $index . '.Regn_Numb') border-red-500 @enderror"
                                            placeholder="Enter number">
                                        @error('documents.' . $index . '.Regn_Numb')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('documents.' . $index . '.Regn_Numb')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Country -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Country
                                    </label>
                                    <div class="relative">
                                        <select wire:model="documents.{{ $index }}.Admn_Cutr_Mast_UIN"
                                            class="form-select-figma w-full text-sm @error('documents.' . $index . '.Admn_Cutr_Mast_UIN') border-red-500 @enderror">
                                            <option value="">Select Country...</option>
                                            @foreach ($allCountries as $country)
                                                <option value="{{ $country->Admn_Cutr_Mast_UIN }}">
                                                    {{ $country->Name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('documents.' . $index . '.Admn_Cutr_Mast_UIN')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('documents.' . $index . '.Admn_Cutr_Mast_UIN')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Authority Issued -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Authority Issued
                                    </label>
                                    <div class="relative">
                                        <input type="text"
                                            wire:model.live="documents.{{ $index }}.Auth_Issd"
                                            class="form-input-figma w-full text-sm @error('documents.' . $index . '.Auth_Issd') border-red-500 @enderror"
                                            placeholder="e.g., Ministry of XYZ">
                                        @error('documents.' . $index . '.Auth_Issd')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('documents.' . $index . '.Auth_Issd')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Valid From -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Valid From
                                    </label>
                                    <div class="relative mt-1">
                                        <input type="date"
                                            wire:model.live="documents.{{ $index }}.Vald_From"
                                            class="form-input-figma w-full text-sm @error('documents.' . $index . '.Vald_From') border-red-500 @enderror">
                                        @error('documents.' . $index . '.Vald_From')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('documents.' . $index . '.Vald_From')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Valid Upto -->
                                <div>
                                    <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                        Valid Upto
                                    </label>
                                    <div class="relative mt-1">
                                        <input type="date"
                                            wire:model.live="documents.{{ $index }}.Vald_Upto"
                                            class="form-input-figma w-full text-sm @error('documents.' . $index . '.Vald_Upto') border-red-500 @enderror">
                                        @error('documents.' . $index . '.Vald_Upto')
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                                            </div>
                                        @enderror
                                    </div>
                                    @error('documents.' . $index . '.Vald_Upto')
                                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>


                                <div class="col-span-1 sm:col-span-2">
                                    <div class="flex items-start gap-3">
                                        <label class="block text-xs sm:text-sm mb-1.5 font-medium text-gray-100">
                                            Document Attachment
                                            <span class="text-gray-400 text-xs block mt-1">
                                                (PDF/JPG/PNG/WEBP - Max 200KB)
                                            </span>
                                        </label>

                                        <label for="doc-upload-{{ $index }}"
                                            class="flex-shrink-0 pt-3 text-blue-400 hover:text-blue-300 cursor-pointer transition mt-0.5">
                                            <i class="bi bi-paperclip text-lg"></i>
                                        </label>
                                    </div>

                                    <input type="file"
                                        wire:model.live="documents.{{ $index }}.Docu_Atch_Path"
                                        accept=".pdf,.jpg,.png,.webp" id="doc-upload-{{ $index }}"
                                        class="hidden">

                                    <!-- ERROR (shown first & exclusively) -->
                                    @error('documents.' . $index . '.Docu_Atch_Path')
                                        <div
                                            class="mt-2 text-red-400 text-xs bg-red-900/20 p-2.5 rounded border border-red-600/30 flex items-start gap-2">
                                            <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-0.5"></i>
                                            <span>{{ $message }}</span>
                                        </div>
                                    @enderror

                                    <!-- READY TO UPLOAD (ONLY if NO validation error) -->
                                    @if (
                                        !empty($document['Docu_Atch_Path']) &&
                                            is_object($document['Docu_Atch_Path']) &&
                                            !$errors->has('documents.' . $index . '.Docu_Atch_Path'))
                                        <div
                                            class="mt-2 p-2.5 text-xs sm:text-sm text-blue-300 bg-blue-900/20 border border-blue-600/30 rounded flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium truncate">
                                                        {{ $document['Docu_Atch_Path']->getClientOriginalName() }}
                                                    </p>
                                                    <p class="text-gray-400 text-xs">
                                                        {{ round($document['Docu_Atch_Path']->getSize() / 1024, 1) }}
                                                        KB (Ready to upload)
                                                    </p>
                                                </div>
                                            </div>

                                            <button type="button"
                                                wire:click="clearDocumentFile({{ $index }})"
                                                class="text-blue-400 hover:text-red-400 transition-colors text-lg px-2">
                                                &times;
                                            </button>
                                        </div>

                                        <!-- EXISTING FILE (only when no new file selected) -->
                                    @elseif (empty($document['Docu_Atch_Path']) && !empty($document['existing_file_path']))
                                        <div
                                            class="mt-2 p-2.5 text-xs sm:text-sm text-green-300 bg-green-900/20 border border-green-600/30 rounded flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                                <i class="bi bi-paperclip flex-shrink-0"></i>
                                                <a href="{{ Storage::url($document['existing_file_path']) }}"
                                                    target="_blank"
                                                    class="truncate hover:text-blue-400 hover:underline">
                                                    {{ basename($document['existing_file_path']) }}
                                                </a>
                                            </div>

                                            <button type="button"
                                                wire:click="removeDocumentAttachment({{ $index }})"
                                                class="text-green-400 hover:text-red-400 text-lg px-2">
                                                &times;
                                            </button>
                                        </div>
                                    @endif
                                </div>




                            </div>
                        </div>
                    @empty
                        <p class="text-slate-500 text-sm text-center py-4">No documents added.</p>
                    @endforelse

                    <!-- Add Document Button -->
                    @if ($this->canAdd('documents'))
                        <button type="button" wire:click="addDocument"
                            class="w-full sm:w-auto text-sm font-semibold text-blue-400 hover:text-blue-300 flex items-center justify-center sm:justify-start gap-2 px-4 py-2.5 hover:bg-blue-600/10 rounded transition">
                            <i class="bi bi-plus-circle"></i> Add Document
                        </button>
                    @endif
                </div>
            </div>

            <!-- Remarks Card -->
            <div class="figma-card">
                <h2 class="figma-card-header text-green-200"><i class="bi bi-journal-text mr-2"></i> Remarks for
                    @if ($Prty === 'I')
                        Contact Person
                    @else
                        Organization
                    @endif
                </h2>
                <div class="p-6">
                    <label for="Note" class="sr-only"> Remarks for
                        @if ($Prty === 'I')
                            Contact Person
                        @else
                            Organization
                        @endif
                    </label>
                    <div class="relative">
                        <textarea id="Note" wire:model.live="Note" rows="5"
                            class="form-input-figma @error('Note') border-red-500 @enderror"
                            placeholder="Add remarks about this contact..."></textarea>
                        @error('Note')
                            <div class="absolute top-0 right-0 pt-3 pr-3 flex items-center pointer-events-none">
                                <i class="bi bi-exclamation-circle-fill text-red-400"></i>
                            </div>
                        @enderror
                    </div>
                    @error('Note')
                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Group Assignment Dropdown -->
            <div class="figma-card">
                <h2 class="figma-card-header text-green-200">
                    <i class="bi bi-collection-fill mr-2"></i> Group
                </h2>
                <div class="mb-6 p-6 flex gap-2">
                    <label class="block text-sm font-medium text-white mb-2">Group Assigned to your Contacts:</label>
                    <select wire:model="assignedGroupId"
                        class="w-full sm:w-56 bg-slate-700/60 border-2 border-slate-600 text-white text-sm rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all duration-300 py-2 px-3 appearance-none cursor-pointer hover:border-blue-400/50 hover:bg-slate-700/80"
                        style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 20 20%22><path stroke=%22%236B7280%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M6 8l4 4 4-4%22/></svg>')">
                        <option value="">-- Select Group --</option>
                        @foreach ($availableGroups as $group)
                            <option value="{{ $group['Admn_Grup_Mast_UIN'] }}" class="bg-slate-900 text-gray-100">
                                {{ $group['Name'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('assignedGroupId')
                        <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Tags Card -->
            <div class="figma-card">
                <h2 class="figma-card-header text-green-200"><i class="bi bi-tags-fill mr-2"></i>Tag</h2>
                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @forelse($allTags as $tag)
                            <label for="tag-{{ $tag->Admn_Tag_Mast_UIN }}"
                                class="flex items-center space-x-3 bg-slate-900/50 p-3 rounded-md hover:bg-slate-800 cursor-pointer transition-colors">
                                <input type="checkbox" id="tag-{{ $tag->Admn_Tag_Mast_UIN }}"
                                    value="{{ $tag->Admn_Tag_Mast_UIN }}" wire:model.live="selectedTags"
                                    class="form-checkbox-figma">
                                <span class="text-sm font-medium text-white">{{ $tag->Name }}</span>
                            </label>
                        @empty
                            <p class="text-slate-500 text-sm sm:col-span-4">No tags available to select.</p>
                        @endforelse
                    </div>
                    @error('selectedTags')
                        <p class="mt-2 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- COMPREHENSIVE ERROR DISPLAY SECTION --}}
            @if ($errors->any())
                <div class="mt-6 bg-red-900/50 border border-red-500/50 rounded-md p-6" id="error-section">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="bi bi-exclamation-triangle-fill text-red-400 text-2xl"></i>
                        </div>
                        <div class="ml-4 flex-1">
                            @php
                                // Filter duplicate error messages
                                $uniqueErrors = array_unique($errors->all());
                            @endphp

                            <h3 class="text-lg font-semibold text-red-300 mb-4">
                                Please review the errors highlighted in red below your entered data:
                            </h3>
                            <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                                @foreach ($uniqueErrors as $error)
                                    <div class="flex items-start gap-2 bg-red-900/30 p-3 rounded-md">
                                        <i class="bi bi-x-circle text-red-400 mt-0.5"></i>
                                        <span class="text-sm text-red-200">{{ $error }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Form Actions -->
            <div class="flex justify-end items-center gap-4 mt-6">
                <a href="{{ route('contacts.index') }}"
                    class="text-sm font-semibold text-white hover:text-green-200 transition-colors">
                    Cancel
                </a>


                <button type="submit" class="figma-button-primary">
                    <div wire:loading.remove wire:target="save">
                        <i class="bi bi-person-fill-plus"></i>
                        <span>Update</span>
                    </div>
                    <div wire:loading wire:target="save">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-green-200"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>Updating...</span>
                    </div>
                </button>
            </div>
        </form>

    </div>

    @push('scripts')
        {{-- Cropper.js library --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">

        {{-- Flag icons CSS (for country picker) --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.11.0/css/flag-icons.min.css">

        <script>
            function imageCropperComponent() {
                return {
                    showCropper: false,
                    imageToCrop: null,
                    cropper: null,
                    handleFileSelect(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.imageToCrop = e.target.result;
                            this.showCropper = true;
                            this.$nextTick(() => this.initCropper());
                        };
                        reader.readAsDataURL(file);
                    },
                    initCropper() {
                        if (this.cropper) this.cropper.destroy();
                        this.cropper = new Cropper(this.$refs.imageToCropEl, {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 1
                        });
                    },
                    cropImage() {
                        let canvas = this.cropper.getCroppedCanvas({
                            width: 400,
                            height: 400
                        });

                        // CHANGE: Use 'image/jpeg' and quality 0.8 (0 to 1)
                        // 0.8 usually results in a 512x512 image being ~40kb-70kb
                        canvas.toBlob((blob) => {
                            // Change extension to .jpg
                            const file = new File([blob], 'cropped-avatar.jpg', {
                                type: 'image/jpeg'
                            });

                            // Upload
                            this.$wire.upload('Prfl_Pict', file, () => this.closeCropper());
                        }, 'image/jpeg', 0.8); // <--- Quality Parameter
                    },
                    closeCropper() {
                        this.showCropper = false;
                        if (this.cropper) {
                            this.cropper.destroy();
                            this.cropper = null;
                        }
                        if (this.$refs.fileInput) {
                            this.$refs.fileInput.value = "";
                        }
                    }
                }
            }

            // Handle profile picture updates
            document.addEventListener('profile-picture-updated', () => {
                console.log('Profile picture updated event received');
                // Find the profile image and refresh it
                const profileImage = document.querySelector('[x-ref="displayImage"]');
                if (profileImage) {
                    const currentSrc = profileImage.src;
                    // Add cache-bust parameter
                    const newSrc = currentSrc.includes('?') ?
                        currentSrc.split('?')[0] + '?t=' + Date.now() :
                        currentSrc + '?t=' + Date.now();
                    profileImage.src = newSrc;
                    console.log('Profile image refreshed');
                }
            });

            const allCountries = @json(collect($allCountries)->toArray());

            function countryPicker(initialCode, livewireIndex, type = 'phones') {
                return {
                    open: false,
                    search: '',
                    selectedCountry: null,
                    fieldType: type, // 'phones' or 'landlines'

                    getPhoneInput() {
                        const selector = this.fieldType === 'landlines' ?
                            `input[wire\\:model\\.live="landlines.${livewireIndex}.Land_Numb"]` :
                            `input[wire\\:model\\.live="phones.${livewireIndex}.Phon_Numb"]`;
                        return this.$el.closest('.grid')?.querySelector(selector);
                    },

                    updateMaxLength(country) {
                        const phoneInput = this.getPhoneInput();
                        if (phoneInput && country) {
                            phoneInput.maxLength = country.MoNo_Digt || 15;
                        }
                    },

                    init() {
                        this.selectedCountry = allCountries.find(c => c.Phon_Code == initialCode) ||
                            allCountries.find(c => c.Phon_Code == '91') ||
                            allCountries[0];
                        if (this.selectedCountry) {
                            // Update the correct Livewire property based on type
                            const cutrCodePath = `${this.fieldType}.${livewireIndex}.Cutr_Code`;
                            this.$wire.set(cutrCodePath, this.selectedCountry.Phon_Code);
                            this.$nextTick(() => this.updateMaxLength(this.selectedCountry));
                        }
                    },

                    get filteredCountries() {
                        if (!this.search) return allCountries;
                        const q = this.search.toLowerCase();
                        return allCountries.filter(c =>
                            c.Name.toLowerCase().includes(q) ||
                            c.Phon_Code.includes(this.search) ||
                            c.Code.toLowerCase().includes(q)
                        );
                    },

                    choose(country) {
                        this.selectedCountry = country;
                        this.open = false;
                        this.search = '';
                        // Update the correct Livewire property based on type
                        const cutrCodePath = `${this.fieldType}.${livewireIndex}.Cutr_Code`;
                        this.$wire.set(cutrCodePath, country.Phon_Code);
                        this.updateMaxLength(country);
                    },

                    updateFromPrimary(detail) {
                        const newCode = detail.newPhoneCode;
                        const newCountry = allCountries.find(c => c.Phon_Code == newCode);
                        if (newCountry) {
                            this.selectedCountry = newCountry;
                            const cutrCodePath = `${this.fieldType}.${livewireIndex}.Cutr_Code`;
                            this.$wire.set(cutrCodePath, newCountry.Phon_Code);
                            this.updateMaxLength(newCountry);
                        }
                    }
                }
            }

            document.addEventListener('livewire:init', () => {
                // Ensure page starts at top when opening the edit component
                // Use setTimeout to let Livewire/Alpine stabilize before forcing scroll
                setTimeout(() => window.scrollTo({
                    top: 0,
                    left: 0,
                    behavior: 'instant'
                }), 0);

                Livewire.on('scroll-to-errors', () => {
                    const errorSection = document.getElementById('error-section');
                    if (errorSection) {
                        errorSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        errorSection.classList.add('animate-pulse');
                        setTimeout(() => errorSection.classList.remove('animate-pulse'), 2000);
                    }
                });

                // Listen for profile picture updated event from Livewire
                Livewire.on('profile-picture-updated', () => {
                    console.log('Livewire event: profile picture updated');
                    window.dispatchEvent(new CustomEvent('profile-picture-updated'));
                });
            });
        </script>
    @endpush
</div>

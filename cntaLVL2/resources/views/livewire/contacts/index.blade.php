<div class="text-white">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <!-- ======================================= -->
        <!-- 2. ACTION BUTTONS (Mobile First)        -->
        <!-- ======================================= -->
        <div class="flex justify-end items-center mb-4 px-4 sm:px-0 space-x-2">

            <button x-data x-on:click="$dispatch('openGroupManager'); console.log('Groups button clicked');"
                class="inline-flex items-center justify-center space-x-2 bg-slate-700 hover:bg-slate-600 text-gray-200 font-bold p-2 sm:px-4 rounded transition">
                <i class="bi bi-diagram-3-fill"></i>
                <span class="hidden sm:inline">Groups</span>
            </button>

            <button wire:click="$dispatch('openTagManager')"
                class="inline-flex items-center justify-center space-x-2 bg-slate-700 hover:bg-slate-600 text-gray-200 font-bold p-2 sm:px-4 rounded transition">
                <i class="bi bi-tags"></i> <span class="hidden sm:inline">Tags</span>
            </button>

            <button wire:click="openInviteModal"
                class="inline-flex items-center justify-center space-x-2 bg-slate-700 hover:bg-slate-600 text-gray-200 font-bold p-2 sm:px-4 rounded transition"><i
                    class="bi bi-person-plus"></i> <span class="hidden sm:inline">Invite</span></button>
            <button wire:click="openImportModal"
                class="inline-flex items-center justify-center space-x-2 bg-slate-700 hover:bg-slate-600 text-gray-200 font-bold p-2 sm:px-4 rounded transition"><i
                    class="bi bi-box-arrow-in-down"></i> <span class="hidden sm:inline">Import</span></button>

            <button wire:click="exportCsv" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center space-x-2 bg-slate-700 hover:bg-slate-600 text-gray-200 font-bold p-2 sm:px-4 rounded transition disabled:opacity-50">
                <span wire:loading.remove wire:target="exportCsv">
                    <i class="bi bi-box-arrow-up"></i>
                </span>
                <span wire:loading wire:target="exportCsv">
                    <i class="bi bi-arrow-repeat animate-spin"></i>
                </span>
                <span class="hidden sm:inline">Export</span>
            </button>

            <a href="{{ route('contacts.create') }}" wire:navigate
                class="inline-flex items-center justify-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 sm:px-4 rounded transition">
                <i class="bi bi-plus-circle"></i>
                <span class="hidden sm:inline">Add</span>
            </a>
        </div>

        {{-- messages --}}
        @if (session()->has('message'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 10000)" x-show="show" x-transition.duration.500ms
                class="px-4 sm:px-0 mb-4">

                <div id="successToast"
                    class="bg-green-800/50 border border-green-600 text-green-300 px-4 py-3 rounded relative flex items-center justify-between"
                    role="alert">
                    <span class="block sm:inline">{{ session('message') }}</span>

                    {{-- Updated Close Button to use Alpine --}}
                    <button type="button" @click="show = false"
                        class="ml-4 inline-flex text-green-300 hover:text-green-100 focus:outline-none focus:text-green-100 transition-colors duration-200 flex-shrink-0">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- ======================================= -->
        <!-- 3. SEARCH & ADVANCED (Mobile First)    -->
        <!-- ======================================= -->
        <div class="px-4 sm:px-0 mb-4">
            <!-- Main Search and Advanced Search Button Row -->
            <div class="flex items-center space-x-2">
                <!-- Search Input Wrapper -->
                <div class="relative flex-grow">
                    <!-- Search Icon -->
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="bi bi-search text-gray-400"></i>
                    </div>

                    <!-- Search Input -->
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search contacts..."
                        class="pl-10 pr-10 shadow appearance-none border rounded w-full py-2 px-3 text-white leading-tight focus:outline-none focus:shadow-outline bg-slate-700 border-slate-600 focus:border-blue-500">

                    <!-- NEW: Clear button inside the search bar -->
                    @if (!empty($this->search))
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button wire:click="$set('search', '')" title="Clear search"
                                class="text-gray-400 hover:text-white transition">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Advanced Search Button -->
                <!-- MODIFIED: wire:click now calls our new method -->
                <button wire:click="openAdvancedSearch"
                    class="bg-slate-700 hover:bg-slate-600 text-gray-200 font-bold p-1 rounded transition relative h-10 w-10 flex-shrink-0 flex items-center justify-center">
                    <i class="bi bi-sliders2 text-blue-400 text-lg"></i>
                    <!-- Filter indicator dot -->
                    @if ($this->hasAdvancedFilters())
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                        </span>
                    @endif
                </button>
            </div>

            <!-- NEW: Active Filters Status Bar -->
            @if ($this->hasAdvancedFilters())
                <div wire:click="clearAdvancedSearch" x-data x-show="true"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="cursor-pointer mt-3 flex items-center justify-between bg-slate-800/50 border border-slate-700 rounded-md px-3 py-2 text-sm">

                    <span
                        class="flex items-center font-semibold text-blue-400 hover:text-blue-300 transition-colors duration-150">
                        <i class="bi bi-x-lg mr-1"></i>
                        <span>Clear All Search</span>
                    </span>
                </div>
            @endif
        </div>

        <!-- ======================================= -->
        <!-- 4. CONTACTS LIST CONTAINER (FULLY POPULATED) -->
        <!-- ======================================= -->
        <div class="px-4 sm:px-0">
            <div class="flex items-center justify-between py-2">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-400">Show</span>
                    <select wire:model.live="perPage" class="bg-slate-700 border-slate-600 rounded text-sm py-1">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                </div>
                @if ($contacts->total() > 0)
                    <div class="flex items-center space-x-1">
                        {{-- Previous Button --}}
                        @if ($contacts->onFirstPage())
                            <span class="px-3 py-1 text-sm text-gray-500 bg-slate-800 rounded cursor-not-allowed">
                                <i class="bi bi-chevron-left"></i>
                            </span>
                        @else
                            <button wire:click="previousPage"
                                class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                        @endif

                        {{-- Page Numbers --}}
                        @foreach ($contacts->getUrlRange(1, $contacts->lastPage()) as $page => $url)
                            @if ($page == $contacts->currentPage())
                                <span class="px-3 py-1 text-sm font-bold text-white bg-blue-600 rounded">
                                    {{ $page }}
                                </span>
                            @else
                                <button wire:click="gotoPage({{ $page }})"
                                    class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach

                        {{-- Next Button --}}
                        @if ($contacts->hasMorePages())
                            <button wire:click="nextPage"
                                class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        @else
                            <span class="px-3 py-1 text-sm text-gray-500 bg-slate-800 rounded cursor-not-allowed">
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Contacts Table -->
            <div class=" bg-slate-900/50 backdrop-blur-sm shadow-md rounded overflow-hidden mt-4">
                <div class="overflow-x-auto h-full">
                    <div class="w-full text-sm text-left text-gray-400">

                        <div
                            class="hidden text-cyan-400 md:grid grid-cols-[50px_1.5fr_1fr_1fr_60px_40px] gap-x-4 px-4 py-3 bg-slate-800 text-xs font-medium uppercase tracking-wider items-center border-b border-slate-700">
                            <div class="text-center"></div>
                            <div wire:click="sortBy('FaNm')"
                                class="cursor-pointer hover:text-white transition flex items-center gap-1">Name</div>
                            <div>Individual</div>
                            <div>Organization</div>
                            <div class="text-center">Date</div>
                            <div></div>
                        </div>

                        <div class="divide-y divide-slate-700 bg-slate-900/50">
                            @forelse($contacts as $contact)
                                @php $avatar = $this->generateAvatar($contact); @endphp

                                <div x-data="{ showAvatarModal: false }" wire:key="row-{{ $contact->Admn_User_Mast_UIN }}"
                                    class="relative flex flex-col gap-3 p-4 hover:bg-slate-800 transition
                       md:grid md:grid-cols-[50px_1.5fr_1fr_1fr_60px_40px] md:gap-4 md:items-center md:py-3 md:px-4">

                                    <div class="hidden md:flex justify-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            @if ($contact->Prfl_Pict)
                                                <img @click="showAvatarModal = true"
                                                    class="h-10 w-10 rounded-full object-cover border border-slate-600 cursor-pointer hover:opacity-80 transition"
                                                    src="{{ asset('storage/' . $contact->Prfl_Pict) }}">
                                            @else
                                                <div class="h-10 w-10 rounded-full flex items-center justify-center font-bold text-white border border-slate-600 cursor-default"
                                                    style="background-color: {{ $avatar['color'] }}">
                                                    {{ $avatar['initials'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3 md:block min-w-0">

                                        <div class="md:hidden flex-shrink-0">
                                            @if ($contact->Prfl_Pict)
                                                <img @click="showAvatarModal = true"
                                                    class="h-12 w-12 rounded-full object-cover border border-slate-600 cursor-pointer hover:opacity-80 transition"
                                                    src="{{ asset('storage/' . $contact->Prfl_Pict) }}">
                                            @else
                                                <div class="h-12 w-12 rounded-full flex items-center justify-center font-bold text-white border border-slate-600"
                                                    style="background-color: {{ $avatar['color'] }}">
                                                    {{ $avatar['initials'] }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center md:justify-start gap-2">
                                                <a href="{{ route('contacts.show', $contact->Admn_User_Mast_UIN) }}"
                                                    class="text-base md:text-sm font-bold text-green-200 hover:text-blue-400 truncate">

                                                    {{ Str::limit(trim($contact->FaNm . ' ' . $contact->MiNm . ' ' . $contact->LaNm), 30) }}
                                                    {{-- Birthday Indicator - ADD THIS --}}
                                                    @if ($contact->Brth_Dt)
                                                        @php
                                                            try {
                                                                $birthDate = \Carbon\Carbon::parse($contact->Brth_Dt);
                                                                $today = \Carbon\Carbon::today();
                                                                $isBirthday =
                                                                    $birthDate->format('m-d') === $today->format('m-d');
                                                            } catch (\Exception $e) {
                                                                $isBirthday = false;
                                                            }
                                                        @endphp
                                                        @if ($contact->Prty === 'B')
                                                            @if ($isBirthday)
                                                                <span class="flex-shrink-0"
                                                                    title="🎉 Happy Foundation Day!">
                                                                    🎂
                                                                </span>
                                                            @endif
                                                        @elseif ($contact->Prty === 'I')
                                                            @if ($isBirthday)
                                                                <span class="flex-shrink-0"
                                                                    title="🎉 Happy Birthday!">
                                                                    🎂
                                                                </span>
                                                            @endif
                                                        @endif
                                                    @endif


                                                    @if ($contact->Prty === 'I')
                                                        @if ($contact->Anvy_Dt)
                                                            @php
                                                                try {
                                                                    $AnvyDate = \Carbon\Carbon::parse(
                                                                        $contact->Anvy_Dt,
                                                                    );
                                                                    $today = \Carbon\Carbon::today();
                                                                    $isAnvyday =
                                                                        $AnvyDate->format('m-d') ===
                                                                        $today->format('m-d');
                                                                } catch (\Exception $e) {
                                                                    $isAnvyday = false;
                                                                }
                                                            @endphp

                                                            @if ($isAnvyday)
                                                                <span class="flex-shrink-0"
                                                                    title="🎉 Happy Anniversary!">
                                                                    🎉
                                                                </span>
                                                            @endif
                                                        @endif
                                                    @endif
                                                </a>
                                                @if ($contact->Is_Vf == 100206)
                                                    <span class="h-2.5 w-2.5 bg-yellow-500 rounded-full flex-shrink-0"
                                                        title="Not Verified"></span>
                                                @endif

                                            </div>

                                            @if ($contact->Prty == 'I')
                                                <div class="text-xs text-cyan-500 mt-0.5 truncate">
                                                    {{ $contact->Comp_Name ?? 'No Company' }}
                                                    @if ($contact->Comp_Dsig)
                                                        <span class="text-slate-600 mx-1">|</span>
                                                        {{ $contact->Comp_Dsig }}
                                                    @endif
                                                </div>
                                            @endif


                                        </div>
                                    </div>

                                    <!-- WRAPPER: Personal & Organization - Side by side on mobile -->
                                    <div class="flex md:contents gap-3">

                                        <!-- PERSONAL INFO COLUMN -->
                                        <div class="flex-1 min-w-0 ">
                                            <span
                                                class="md:hidden text-[10px] uppercase font-bold text-cyan-400 mb-1 block">Personal
                                                Info</span>
                                            <div class="pl-2 md:pl-0 border-l-2 border-slate-700 md:border-0">

                                                @if ($contact->Prty === 'B')
                                                    {{-- CASE 1: Reference Person Logic (Prty == B) --}}
                                                    @php
                                                        $refPerson = $contact->referencePersons->first();
                                                    @endphp

                                                    @if ($refPerson)
                                                        <div class="flex items-center gap-2 text-gray-300 text-sm"
                                                            title="Reference Contact">
                                                            <i class="bi bi-person-badge text-indigo-400 text-xs"></i>
                                                            <span class="truncate text-green-200">
                                                                {{ Str::limit($refPerson->Refa_Name, 20) }}</span>
                                                        </div>
                                                        <div
                                                            class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                                            <i class="bi bi-phone "></i>
                                                            <span
                                                                class="text-green-200">{{ $refPerson->Refa_Phon ?? 'No Phone' }}</span>
                                                        </div>
                                                    @else
                                                        <span class="text-gray-600 text-xs italic">No Reference
                                                            Data</span>
                                                    @endif
                                                @else
                                                    {{-- CASE 2: Standard Contact Logic --}}

                                                    @if ($phone = $contact->phones->first())
                                                        <div class="flex items-center gap-2 text-gray-300 text-sm">
                                                            <i class="bi bi-phone text-gray-500 text-xs"></i>
                                                            <span
                                                                class="text-green-200">{{ $phone->Phon_Numb }}</span>
                                                            @if ($phone->Has_WtAp)
                                                                <i
                                                                    class="bi bi-whatsapp text-green-500 text-[10px]"></i>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-gray-600 text-xs italic">No phone</span>
                                                    @endif

                                                    @if ($email = $contact->emails->first())
                                                        <div
                                                            class="flex items-center gap-2 text-xs text-green-200 mt-0.5 truncate">
                                                            <i class="bi bi-envelope text-gray-600"></i>
                                                            {{ $email->Emai_Addr }}
                                                        </div>
                                                    @endif
                                                @endif

                                            </div>
                                        </div>

                                        <!-- ORGANIZATION INFO COLUMN -->
                                        <div class="flex-1 min-w-0">
                                            <span
                                                class="md:hidden text-[10px] uppercase font-bold text-cyan-400 mb-1 block">Organization</span>
                                            <div class="pl-2 md:pl-0 border-l-2 border-slate-700 md:border-0">
                                                @if ($contact->Prty === 'B')
                                                    @if ($phone = $contact->phones->first())
                                                        <div class="flex items-center gap-2 text-gray-300 text-sm">
                                                            <i class="bi bi-phone text-gray-500 text-xs"></i>
                                                            <span
                                                                class="text-green-200">{{ $phone->Phon_Numb }}</span>
                                                            @if ($phone->Has_WtAp)
                                                                <i
                                                                    class="bi bi-whatsapp text-green-500 text-[10px]"></i>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-gray-600 text-xs italic">No phone</span>
                                                    @endif

                                                    @if ($email = $contact->emails->first())
                                                        <div
                                                            class="flex items-center gap-2 text-xs text-green-200 mt-0.5 truncate">
                                                            <i class="bi bi-envelope text-gray-600"></i>
                                                            {{ $email->Emai_Addr }}
                                                        </div>
                                                    @endif
                                                @else
                                                    @if ($contact->Comp_LdLi)
                                                        <div class="flex items-center gap-2 text-gray-300 text-sm">
                                                            <i class="bi bi-telephone text-gray-500 text-xs"></i>
                                                            <span
                                                                class="text-green-200">{{ $contact->Comp_LdLi }}</span>
                                                        </div>
                                                    @else
                                                        <span class="text-gray-600 text-xs italic md:hidden">No office
                                                            phone</span>
                                                    @endif
                                                    @if ($contact->Comp_Emai)
                                                        <div
                                                            class="flex items-center gap-2 text-xs text-green-200 mt-0.5 truncate">
                                                            <i class="bi bi-envelope text-gray-600"></i>
                                                            {{ $contact->Comp_Emai }}
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                    </div>

                                    <div class="absolute top-4 right-12 md:static md:flex md:justify-center">
                                        @if ($date = $contact->CrOn ?? $contact->MoOn)
                                            <div
                                                class="flex md:flex-col items-center gap-1 md:gap-0 leading-tight bg-slate-800 md:bg-transparent px-2 py-1 md:p-0 rounded md:rounded-none">
                                                <span
                                                    class="text-[10px] uppercase font-bold text-slate-500">{{ \Carbon\Carbon::parse($date)->format('M') }}</span>
                                                <span
                                                    class="text-sm md:text-lg font-bold text-slate-400">{{ \Carbon\Carbon::parse($date)->format('y') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="absolute top-4 right-2 md:static md:flex md:justify-end"
                                        x-data="{ open: false }">
                                        <button @click="open = !open" type="button"
                                            class="p-1.5 rounded-full text-slate-400 hover:bg-slate-700 hover:text-white transition">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <div x-show="open" @click.away="open = false"
                                            class="absolute right-0 md:right-8 top-8 md:top-0 mt-2 w-32 bg-slate-800 rounded-md shadow-xl ring-1 ring-black ring-opacity-5 z-20 py-1 border border-slate-700">
                                            <a href="{{ route('contacts.edit', $contact->Admn_User_Mast_UIN) }}"
                                                class="block px-4 py-2 text-xs text-gray-300 hover:bg-slate-700 hover:text-white">
                                                <i class="bi bi-pencil me-2"></i> Edit
                                            </a>
                                            <button wire:click="deleteContact({{ $contact->Admn_User_Mast_UIN }})"
                                                wire:confirm="Move to trash?"
                                                class="w-full text-left px-4 py-2 text-xs text-red-400 hover:bg-slate-700">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </button>
                                        </div>
                                    </div>

                                    @if ($contact->Prfl_Pict)
                                        <template x-teleport="body">
                                            <div x-show="showAvatarModal" style="display: none;"
                                                x-transition.opacity.duration.300ms
                                                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-90 backdrop-blur-sm p-4">
                                                <button @click="showAvatarModal = false"
                                                    class="absolute top-5 right-5 text-gray-400 hover:text-white z-50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="w-10 h-10">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>

                                                <img @click.outside="showAvatarModal = false"
                                                    src="{{ asset('storage/' . $contact->Prfl_Pict) }}"
                                                    class="max-w-full max-h-[90vh] rounded shadow-2xl object-contain">
                                            </div>
                                        </template>
                                    @endif

                                </div>
                            @empty
                                <div class="py-12 text-center text-gray-500">No contacts found.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                @if ($contacts->total() > 0)
                    <div
                        class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 py-3 bg-slate-900/30 rounded px-4">
                        <!-- Left: Showing text -->
                        <div class="text-sm text-gray-400">
                            Showing
                            <span class="font-medium">{{ $contacts->firstItem() }}</span>
                            to
                            <span class="font-medium">{{ $contacts->lastItem() }}</span>
                            of
                            <span class="font-medium">{{ $contacts->total() }}</span>
                            results
                        </div>

                        <!-- Right: Pagination Buttons -->
                        <div class="flex items-center space-x-1">
                            {{-- Previous Button --}}
                            @if ($contacts->onFirstPage())
                                <span class="px-3 py-1 text-sm text-gray-500 bg-slate-800 rounded cursor-not-allowed">
                                    <i class="bi bi-chevron-left"></i>
                                </span>
                            @else
                                <button wire:click="previousPage"
                                    class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                            @endif

                            {{-- Page Numbers --}}
                            @foreach ($contacts->getUrlRange(1, $contacts->lastPage()) as $page => $url)
                                @if ($page == $contacts->currentPage())
                                    <span class="px-3 py-1 text-sm font-bold text-white bg-blue-600 rounded">
                                        {{ $page }}
                                    </span>
                                @else
                                    <button wire:click="gotoPage({{ $page }})"
                                        class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                                        {{ $page }}
                                    </button>
                                @endif
                            @endforeach

                            {{-- Next Button --}}
                            @if ($contacts->hasMorePages())
                                <button wire:click="nextPage"
                                    class="px-3 py-1 text-sm text-gray-300 bg-slate-700 hover:bg-slate-600 rounded transition">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            @else
                                <span class="px-3 py-1 text-sm text-gray-500 bg-slate-800 rounded cursor-not-allowed">
                                    <i class="bi bi-chevron-right"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-4 text-center text-gray-400 py-8">
                        No results found
                    </div>
                @endif

            </div>
        </div>

        <!-- Backdrop -->
        @if ($showAdvancedSearch)
            <div class="fixed inset-0 z-30 transition-opacity duration-300"
                style="background-color: rgba(2, 20, 32, 0.6);" wire:click="$set('showAdvancedSearch', false)"></div>
        @endif

        <!-- Panel -->
        <div class="fixed top-0 right-0 h-full w-full sm:w-96 transform transition-transform duration-300 z-40 {{ $showAdvancedSearch ? 'translate-x-0' : 'translate-x-full' }}"
            style="background-color: #1E293B; border-left: 1px solid #334155;">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex-shrink-0 flex items-center justify-between p-4 border-b border-slate-700">
                    <h3 class="text-lg font-medium text-white flex items-center gap-2">
                        <i class="bi bi-sliders2 text-blue-400"></i> Advanced Search
                    </h3>
                    <button wire:click="$set('showAdvancedSearch', false)"
                        class="text-gray-400 hover:text-white text-xl transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">First Name</label>
                        <input type="text" wire:model.live.debounce.300ms="advancedSearch.FaNm"
                            placeholder="Enter first name..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Middle Name</label>
                        <input type="text" wire:model.live.debounce.300ms="advancedSearch.MiNm"
                            placeholder="Enter middle name..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Last Name</label>
                        <input type="text" wire:model.live.debounce.300ms="advancedSearch.LaNm"
                            placeholder="Enter last name..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Mobile Number</label>
                        <input type="tel" wire:model.live.debounce.300ms="advancedSearch.mobile"
                            placeholder="Enter mobile..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Email Address</label>
                        <input type="email" wire:model.live.debounce.300ms="advancedSearch.email"
                            placeholder="Enter Personal/Job/Referrence email address..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Company Name</label>
                        <select wire:model.live="advancedSearch.company"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- All Companies --</option>
                            @foreach ($this->allCompanies as $company)
                                <option value="{{ $company }}">{{ $company }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Designation</label>
                        <input type="text" wire:model.live.debounce.300ms="advancedSearch.designation"
                            placeholder="Enter designation..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- ======================================= -->
                    <!--  NEW ADDRESS-SPECIFIC INPUT FIELDS      -->
                    <!-- ======================================= -->


                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Country</label>
                        <select wire:model.live="advancedSearch.country"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- All Countries --</option>
                            @foreach ($this->countries as $country)
                                <option value="{{ $country->Admn_Cutr_Mast_UIN }}">{{ $country->Name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">State</label>
                        <select wire:model.live="advancedSearch.state" @disabled(empty($advancedSearch['country']))
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-800 disabled:cursor-not-allowed disabled:text-gray-500">
                            <option value="">-- Select State --</option>
                            {{-- Loop over the new dynamic property --}}
                            @foreach ($this->stateOptions as $state)
                                <option value="{{ $state->Admn_Stat_Mast_UIN }}">{{ $state->Name }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">District</label>
                        <select wire:model.live="advancedSearch.district" @disabled(empty($advancedSearch['state']))
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-800 disabled:cursor-not-allowed disabled:text-gray-500">
                            <option value="">-- Select District --</option>
                            {{-- Loop over the new dynamic property --}}
                            @foreach ($this->districtOptions as $district)
                                <option value="{{ $district->Admn_Dist_Mast_UIN }}">{{ $district->Name }}</option>
                            @endforeach
                        </select>
                    </div>


                    <!-- NEW/MODIFIED: Pincode input with autocomplete suggestions -->
                    <div x-data="{ open: true }" @click.away="open = false" class="relative">
                        <label class="block text-xs font-medium text-gray-300 mb-1">Pincode</label>
                        <input type="text" inputmode="numeric"
                            wire:model.live.debounce.300ms="advancedSearch.pincode" placeholder="Enter pincode..."
                            @input="$event.target.value = $event.target.value.trimStart()" @focus="open = true"
                            autocomplete="off"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">

                        {{-- Suggestions Dropdown --}}
                        @if (!empty($pincodeSuggestions))
                            <div x-show="open" x-transition
                                class="absolute z-10 w-full mt-1 bg-slate-700 border border-slate-600 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                <ul class="py-1">
                                    @forelse($pincodeSuggestions as $suggestion)
                                        <li wire:click="selectPincode('{{ $suggestion }}')"
                                            wire:key="pincode-{{ $suggestion }}"
                                            class="px-3 py-2 text-sm text-gray-300 cursor-pointer hover:bg-slate-600">
                                            {{ $suggestion }}
                                        </li>
                                    @empty
                                        <li class="px-3 py-2 text-sm text-gray-500 italic">No matches found...</li>
                                    @endforelse
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Location</label>
                        <input type="text" wire:model.live.debounce.300ms="advancedSearch.locality"
                            placeholder="Enter locality..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1"> Town/City, Locality,
                            Landmark</label>
                        <input type="text" wire:model.live.debounce.300ms="advancedSearch.landmark"
                            placeholder="Town/City, Locality, Landmark..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Flat, House No</label>
                        <input type="text" wire:model.live.debounce.300ms="advancedSearch.address"
                            placeholder="Flat, House No,  Building Name..."
                            @input="$event.target.value = $event.target.value.trimStart()"
                            class="w-full bg-slate-700 border-slate-600 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>



                    <div class="border-t border-slate-700"></div>
                    {{-- Tags Filter --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-2">Tags</label>
                        <div
                            class="space-y-1 max-h-40 overflow-y-auto border border-slate-600 rounded-md p-2 bg-slate-800">
                            @forelse($allTags as $tag)
                                <label
                                    class="flex items-center gap-2 cursor-pointer hover:bg-slate-700 p-1 rounded transition-colors"
                                    wire:key="tag-{{ $tag->Admn_Tag_Mast_UIN }}">
                                    <input type="checkbox" wire:model.live="advancedSearch.tags"
                                        value="{{ $tag->Admn_Tag_Mast_UIN }}"
                                        class="rounded border-gray-500 text-blue-500 focus:ring-blue-500">
                                    <span class="text-sm text-gray-300">{{ $tag->Name }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 italic p-2">No tags available</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- GROUPS FILTER -->
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-2">Groups</label>
                        <div
                            class="space-y-1 max-h-40 overflow-y-auto border border-slate-600 rounded-md p-2 bg-slate-800">
                            @forelse($allGroups as $group)
                                <label
                                    class="flex items-center gap-2 cursor-pointer hover:bg-slate-700 p-1 rounded transition-colors"
                                    wire:key="group-{{ $group->Admn_Grup_Mast_UIN }}">
                                    <input type="checkbox" wire:model.live="advancedSearch.groups"
                                        value="{{ $group->Admn_Grup_Mast_UIN }}"
                                        class="rounded border-gray-500 text-blue-500 focus:ring-blue-500">
                                    <span class="text-sm text-gray-300">{{ $group->Name }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 italic p-2">No groups available</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex-shrink-0 p-4 border-t border-slate-700 bg-slate-800">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-gray-400 flex items-center gap-2">
                            <i class="bi bi-info-circle"></i>
                            @if ($this->hasAdvancedFilters())
                                {{ $this->getAdvancedFilterCount() }} filter(s) applied
                            @else
                                No filters applied
                            @endif
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="clearAdvancedSearch"
                            class="flex-1 px-4 py-2 text-sm border border-slate-600 text-gray-300 hover:bg-slate-700 transition rounded-md flex items-center justify-center gap-2">
                            <i class="bi bi-arrow-clockwise"></i> <span>Clear All</span>
                        </button>
                        <button wire:click="applyAdvancedSearch"
                            class="flex-1 px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 transition rounded-md flex items-center justify-center gap-2">
                            <i class="bi bi-check2"></i> <span>Search</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================= -->
        <!-- NEW INVITE MODAL                          -->
        <!-- ======================================= -->
        @if ($showInviteModal)
            <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-slate-900/75 transition-opacity"
                        wire:click="$set('showInviteModal', false)"></div>

                    <!-- Modal Panel -->
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div
                        class="inline-block align-bottom bg-slate-800 rounded-md text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-700">
                        <div class="bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-100 flex items-center gap-2">
                                    <i class="bi bi-send text-blue-400"></i>
                                    Generate Invite Link
                                </h3>
                                <button wire:click="$set('showInviteModal', false)"
                                    class="text-gray-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
                            </div>

                            <p class="text-sm text-gray-400 mb-4">
                                Generate a unique, single-use link to invite someone to add their contact information.
                                The
                                link will expire after 24 hours or once it has been used.
                            </p>

                            <button wire:click="generateInviteLink"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition mb-4">
                                <span wire:loading.remove wire:target="generateInviteLink">
                                    <i class="bi bi-magic"></i> Generate Link
                                </span>
                                <span wire:loading wire:target="generateInviteLink">
                                    Generating...
                                </span>
                            </button>

                            @if ($generatedInviteLink)
                                <div x-data="{ copied: false }" class="mt-4">
                                    <label class="block text-sm font-medium text-gray-300">Generated Link:</label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <input type="text" value="{{ $generatedInviteLink }}" readonly
                                            class="flex-1 block w-full rounded-none rounded-l-md bg-slate-700 border-slate-600 text-gray-300 sm:text-sm">
                                        <button
                                            @click="navigator.clipboard.writeText('{{ $generatedInviteLink }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="relative inline-flex items-center space-x-2 px-4 py-2 border border-slate-600 text-sm font-medium rounded-r-md text-gray-300 bg-slate-900 hover:bg-slate-600">
                                            <i class="bi"
                                                :class="copied ? 'bi-check-lg text-green-400' : 'bi-clipboard'"></i>
                                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ======================================= -->
        <!-- NEW IMPORT MODAL                          -->
        <!-- ======================================= -->
        @if ($showImportModal)
            <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-slate-900/75 transition-opacity"
                        wire:click="$set('showImportModal', false)"></div>
                    <!-- Modal Panel -->
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div
                        class="inline-block align-bottom bg-slate-800 rounded-md text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-700">
                        <div class="bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-100 flex items-center gap-2">
                                    <i class="bi bi-box-arrow-in-down text-blue-400"></i>
                                    Upload Contacts using a CSV file.
                                </h3>
                                <button wire:click="$set('showImportModal', false)"
                                    class="text-gray-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
                            </div>

                            @if ($importResults)
                                <div class="bg-slate-700 p-4 rounded-md text-center">
                                    <h4 class="text-lg font-medium text-white">Import Complete!</h4>

                                    {{-- ============================================= --}}
                                    {{-- ========= START: NEW CODE BLOCK ========= --}}
                                    {{-- ============================================= --}}
                                    @if (isset($importResults['limit_message']))
                                        <div
                                            class="mt-4 bg-yellow-900/50 border border-yellow-700 text-yellow-300 px-3 py-2 rounded text-sm text-left">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 pt-0.5">
                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p>{{ $importResults['limit_message'] }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    {{-- ============================================= --}}
                                    {{-- ========= END: NEW CODE BLOCK =========== --}}
                                    {{-- ============================================= --}}

                                    <div class="mt-4 flex justify-center gap-6 text-sm">
                                        <p class="text-green-400"><i class="bi bi-check-circle-fill"></i>
                                            {{ $importResults['imported'] }} Imported</p>
                                        <p class="text-yellow-400"><i class="bi bi-exclamation-triangle-fill"></i>
                                            {{ $importResults['skipped'] }} Skipped</p>
                                    </div>
                                    <button wire:click="openImportModal"
                                        class="mt-4 text-sm text-blue-400 hover:underline">Import another file</button>
                                </div>
                            @else
                                <div>
                                    <!-- NEW: Download Templates Section -->
                                    <div class="space-y-2 mb-4 bg-slate-900/50 p-3 rounded-md border border-slate-700">
                                        <p class="text-xs text-gray-400 mb-2 flex items-center gap-2">

                                            Download all the files to upload CSV data in the proper format.
                                        </p>

                                        <div class="grid grid-cols-1 gap-2">
                                            <!-- Contact Sample -->
                                            <button wire:click="downloadSampleCsv"
                                                class="w-full text-left px-3 py-2 text-xs bg-slate-700 hover:bg-slate-600 text-gray-300 rounded transition flex items-center justify-between group">
                                                <span class="flex items-center gap-2">
                                                    <i class="bi bi-person-vcard text-blue-400"></i>
                                                    <span class="font-medium">CSV Format for Contact Upload</span>
                                                </span>
                                                <i
                                                    class="bi bi-download text-gray-500 group-hover:text-blue-400 transition"></i>
                                            </button>

                                            <!-- Tags Sample -->
                                            <button wire:click="downloadTagsSampleCsv"
                                                class="w-full text-left px-3 py-2 text-xs bg-slate-700 hover:bg-slate-600 text-gray-300 rounded transition flex items-center justify-between group">
                                                <span class="flex items-center gap-2">
                                                    <i class="bi bi-tags text-green-400"></i>
                                                    <span class="font-medium">List of All Contact Tags</span>
                                                </span>
                                                <i
                                                    class="bi bi-download text-gray-500 group-hover:text-green-400 transition"></i>
                                            </button>

                                            <!-- Prefixes Sample -->
                                            <button wire:click="downloadPrefixesSampleCsv"
                                                class="w-full text-left px-3 py-2 text-xs bg-slate-700 hover:bg-slate-600 text-gray-300 rounded transition flex items-center justify-between group">
                                                <span class="flex items-center gap-2">
                                                    <i class="bi bi-type text-purple-400"></i>
                                                    <span class="font-medium">List of All Name Prefixes</span>
                                                </span>
                                                <i
                                                    class="bi bi-download text-gray-500 group-hover:text-purple-400 transition"></i>
                                            </button>
                                        </div>
                                    </div>


                                    <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
                                        x-on:livewire-upload-finish="isUploading = false"
                                        x-on:livewire-upload-error="isUploading = false"
                                        x-on:livewire-upload-progress="progress = $event.detail.progress">

                                        <label for="csv-upload"
                                            class="relative block w-full border-2 border-dashed border-slate-600 rounded-md p-8 text-center cursor-pointer hover:border-blue-500">
                                            <i class="bi bi-file-earmark-arrow-up text-3xl text-gray-500"></i>
                                            <span class="mt-2 block text-sm font-medium text-gray-400">
                                                @if ($csvFile)
                                                    {{ $csvFile->getClientOriginalName() }}
                                                @else
                                                    Click to import contacts from Google, iOS and other software in CSV
                                                    format.
                                                    <span class="text-xs text-gray-300 block mt-1">(To upload data in
                                                        CSV
                                                        format, first download all three files above and fill the Prefix
                                                        and
                                                        Tag columns in the 'CSV Format for Contact Upload' as
                                                        instructed.)</span>
                                                @endif
                                            </span>
                                            <input id="csv-upload" wire:model="csvFile" type="file"
                                                class="sr-only">
                                        </label>

                                        <!-- Progress Bar -->
                                        <div x-show="isUploading" class="w-full bg-slate-700 rounded-full mt-2">
                                            <div class="bg-blue-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full"
                                                :style="`width: ${progress}%`" x-text="`${progress}%`"></div>
                                        </div>
                                    </div>
                                    @error('csvFile')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mt-6">
                                    <button wire:click="importContacts" wire:loading.attr="disabled"
                                        wire:target="importContacts, csvFile" @disabled(!$csvFile)
                                        class="w-full flex justify-center items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition disabled:bg-slate-600 disabled:cursor-not-allowed">
                                        <span wire:loading.remove wire:target="importContacts">Import Data</span>
                                        <span wire:loading wire:target="importContacts">Processing... <i
                                                class="bi bi-arrow-repeat animate-spin"></i></span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @livewire('contacts.tag-manager')
        @livewire('contacts.manage-groups')

    </div>

<div class="min-h-screen flex justify-center bg-[#021420] py-12 px-4 sm:px-6 lg:px-8">

    <div class="max-w-md w-full space-y-8">

        @if (session('success'))
            <div class="bg-green-800/50 border border-green-600 text-green-300 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

       {{-- @if (session('error'))
            <div class="bg-red-800/50 border border-red-600 text-red-300 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif --}}
      

        <div class="space-y-4">
            @if ($this->userOrganizations->count() > 0)
                <div class="w-full">
                    <select wire:model.live="selectedOrgaUIN" id="organization-select"
                        class="block w-full text-lg bg-slate-800 border border-slate-600 rounded-md text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 py-3 px-4">

                        <option value="" class="text-gray-400 text-center">-- Select Organization --</option>

                        @foreach ($this->userOrganizations as $org)
                            <option value="{{ $org->Orga_UIN }}" class="text-gray-100 bg-slate-800 py-2">
                                {{ $org->Orga_Name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($selectedOrgaUIN)
                    @php
                        $selectedOrg = $this->userOrganizations->firstWhere('Orga_UIN', $selectedOrgaUIN);
                    @endphp

                    @if ($selectedOrg)
                        <div class="bg-blue-600/20 border border-blue-500 rounded-md p-4 text-center">
                            <div class="flex items-center justify-center space-x-3">
                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                    <i class="bi bi-check text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="text-blue-300 font-medium">Selected Organization</div>
                                    <div class="text-blue-200 text-sm">{{ $selectedOrg->Orga_Name }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                @if ($selectedOrgaUIN)
                    <div class="text-center">
                        <button wire:click="selectOrganization({{ $selectedOrgaUIN }})"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 w-full justify-center">
                            <i class="bi bi-arrow-right mr-2"></i>
                            Continue to Contacts
                        </button>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-red-600/20">
                        <i class="bi bi-exclamation-triangle text-2xl text-red-400"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-medium text-gray-300">No Organizations Found</h3>
                    <p class="mt-2 text-sm text-gray-400">
                        You don't have access to any organizations yet.<br>
                        Please contact your administrator for access.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

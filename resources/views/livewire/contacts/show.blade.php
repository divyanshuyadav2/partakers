<div class="m-2">
    <div class="max-w-6xl mx-auto py-6 sm:px-4">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex space-x-3">
                <a href="{{ route('contacts.index') }}" class="text-green-200 hover:text-white">
                    <i class="bi bi-arrow-left text-lg ml-4"></i>
                </a>
            </div>
        </div>

        {{-- Define Standard Header Classes for Uniformity --}}
        @php
            $sectionHeaderClass = 'text-md font-semibold text-purple-300 mb-3 flex items-center gap-2';
            $labelClass = 'text-cyan-400 text-sm font-semibold';
            $subHeading = 'text-sm font-semibold';
            $dataClass = 'text-green-200 text-sm';
            // Unified wrapper for Label + Value pairs to ensure consistent gap
            $fieldWrapperClass = 'flex nowrap gap-1 items-baseline';
        @endphp

        <div class="space-y-4">
            {{-- Quick Fields --}}
            @php
                $hasQuickFields =
                    $contact->Gend ||
                    ($contact->Prty === 'I' && $contact->Blood_Grp) ||
                    $contact->Brth_Dt ||
                    ($contact->Prty === 'I' && $contact->Anvy_Dt) ||
                    ($contact->Prty === 'I' && $contact->Deth_Dt);
            @endphp
            <div class="figma-card bg-slate-600/40">
                <div class="flex items-center space-x-6 pl-2 m-4">

                    {{-- Left Side: Avatar --}}
                    <div x-data="{ showAvatarModal: false }">
                        @if ($contact->avatar_url)
                            <img src="{{ $contact->avatar_url }}" @click="showAvatarModal = true"
                                class="w-20 h-20 rounded-full border border-slate-600 cursor-pointer hover:opacity-90 transition"
                                alt="Profile Avatar">

                            <div x-show="showAvatarModal" style="display: none;" x-transition.opacity
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80">
                                <button @click="showAvatarModal = false"
                                    class="absolute top-5 right-5 text-white hover:text-gray-300 focus:outline-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-8 h-8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                <img @click.outside="showAvatarModal = false" src="{{ $contact->avatar_url }}"
                                    class="max-w-full max-h-screen rounded shadow-lg object-contain">
                            </div>
                        @else
                            <div class="w-20 h-20 rounded-full border border-slate-600 flex items-center justify-center"
                                style="background-color: {{ $contact->avatar_color ?? '#334155' }}">
                                <span class="text-white text-lg font-bold">{{ $contact->initials }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Right Side: Details Wrapper --}}
                    <div class="flex-1">
                        @if ($hasQuickFields)
                            <div class="mb-1">
                                <h1 class="text-xl font-normal text-green-200">
                                    @if ($contact->Prty === 'B')
                                        {{ $contact->FaNm }}
                                    @else
                                        {{ collect([$prefixName, $contact->FaNm, $contact->MiNm, $contact->LaNm])->filter()->implode(' ') }}
                                    @endif
                                </h1>
                            </div>

                            {{-- 2. Data Row: Standardized Gap --}}
                            <div class="flex flex-row gap-x-6 gap-y-1 flex-wrap">

                                {{-- Gender --}}
                                @if ($contact->Gend)
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">
                                            @if ($contact->Prty === 'B')
                                                Organization Type:
                                            @else
                                                Gender:
                                            @endif
                                        </span>
                                        <span class="{{ $dataClass }}">{{ ucfirst($contact->Gend) ?? '-' }}</span>
                                    </div>
                                @endif

                                {{-- DOB / Incorporation --}}
                                @if ($contact->Brth_Dt)
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">
                                            @if ($contact->Prty === 'B')
                                                Date of Incorporation:
                                            @else
                                                DOB:
                                            @endif
                                        </span>
                                        <span
                                            class="{{ $dataClass }}">{{ $this->formatDate($contact->Brth_Dt, 'd-M-Y') }}</span>
                                    </div>
                                @endif

                                {{-- Blood Group --}}
                                @if ($contact->Prty === 'I' && $contact->Blood_Grp)
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Blood Group:</span>
                                        <span class="{{ $dataClass }}">{{ $contact->Blood_Grp }}</span>
                                    </div>
                                @endif

                                {{-- Anniversary --}}
                                @if ($contact->Prty === 'I' && $contact->Anvy_Dt)
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Anniversary:</span>
                                        <span
                                            class="{{ $dataClass }}">{{ $this->formatDate($contact->Anvy_Dt, 'd-M-Y') }}</span>
                                    </div>
                                @endif

                                {{-- Date of Death --}}
                                @if ($contact->Prty === 'I' && $contact->Deth_Dt)
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Date of Death:</span>
                                        <span
                                            class="{{ $dataClass }}">{{ $this->formatDate($contact->Deth_Dt, 'd-M-Y') }}</span>
                                    </div>
                                @endif

                            </div>
                        @else
                            <div class="text-slate-500 pl-2 m-4">No additional information available.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            @if (!empty($phones) || !empty($emails) || !empty($landlines))
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header">
                        <i class="bi bi-person-lines-fill"></i> Contact Information
                    </h2>

                    {{-- Grid Layout matching Address Section --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 pl-2 m-4 gap-4">

                        {{-- Phones Block --}}
                        @if (!empty($phones))
                            <div class="border-l-2 border-slate-600 pl-3">
                                <h3 class="font-semibold text-slate-300 mb-2 flex items-center gap-1">
                                    Phones
                                </h3>
                                <ul class="space-y-1">
                                    @foreach ($phones as $phone)
                                        <li class="text-white flex items-center gap-1">
                                            <div class="flex items-center gap-1">
                                                @if ($phone['Is_Prmy'])
                                                    <span class="p-1 rounded-full bg-yellow-400" title="Primary"></span>
                                                @endif
                                                <span
                                                    class="{{ $labelClass }} capitalize">{{ $phone['Phon_Type'] }}:</span>
                                                <span class="{{ $dataClass }}">+{{ $phone['Cutr_Code'] }}
                                                    {{ $phone['Phon_Numb'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                @if ($phone['Has_WtAp'])
                                                    <i class="bi bi-whatsapp text-green-400" title="WhatsApp"></i>
                                                @endif
                                                @if ($phone['Has_Telg'])
                                                    <i class="bi bi-telegram text-sky-400" title="Telegram"></i>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Emails Block --}}
                        @if (!empty($emails))
                            <div class="border-l-2 border-slate-600 pl-3">
                                <h3 class="font-semibold text-slate-300 mb-2 flex items-center gap-1">
                                    Emails
                                </h3>
                                <ul class="space-y-1">
                                    @foreach ($emails as $email)
                                        <li class="flex items-center gap-1 text-white">
                                            @if ($email['Is_Prmy'])
                                                <span class="p-1 rounded-full bg-yellow-400" title="Primary"></span>
                                            @endif
                                            <span class="break-all flex gap-1">
                                                <span
                                                    class="{{ $labelClass }} capitalize">{{ $email['Emai_Type'] }}:</span>
                                                <span class="{{ $dataClass }}">{{ $email['Emai_Addr'] }}</span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Landlines Block --}}
                        @if (!empty($landlines))
                            <div class="border-l-2 border-slate-600 pl-3">
                                <h3 class="font-semibold text-slate-300 mb-2 flex items-center gap-1">
                                    Landlines
                                </h3>
                                <ul class="space-y-1">
                                    @foreach ($landlines as $landline)
                                        <li class="text-white flex items-center gap-1">
                                            <div class="flex items-center gap-1">
                                                @if ($landline['Is_Prmy'])
                                                    <span class="p-1 rounded-full bg-yellow-400" title="Primary"></span>
                                                @endif
                                                <span
                                                    class="{{ $labelClass }} capitalize">{{ $landline['Land_Type'] }}:</span>
                                                <span class="{{ $dataClass }}">+{{ $landline['Cutr_Code'] }}
                                                    {{ $landline['Land_Numb'] }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-person-lines-fill"></i> Contact Information
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">No contact information available.</p>
                </div>
            @endif


            {{-- Social Links --}}
            @if ($this->hasSocialLinks() && !empty($this->getSocialLinks()))
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-share-fill"></i> Social Links
                    </h2>
                    <div class="flex flex-wrap pl-2 m-4 gap-4">
                        @php
                            $socialIcons = [
                                'website' => 'bi-globe',
                                'linkedin' => 'bi-linkedin',
                                'twitter' => 'bi-twitter-x',
                                'facebook' => 'bi-facebook',
                                'instagram' => 'bi-instagram',
                                'reddit' => 'bi-reddit',
                                'youtube' => 'bi-youtube',
                                'yahoo' => 'bi-yahoo',
                            ];
                        @endphp
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            @foreach ($this->getSocialLinks() as $platform => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                    class="bg-green-100 px-2 rounded-md social-link-hover flex items-center gap-1"
                                    style="color: {{ $socialHexColors[$platform] ?? '#000000' }}">
                                    <i class="{{ $socialIcons[$platform] ?? 'bi-link' }}"></i>
                                    <span class="font-medium">{{ ucfirst($platform) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-share-fill"></i> Social Links
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">No social links added.</p>
                </div>
            @endif

            {{-- Addresses --}}
            @if (!empty($addresses))
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-geo-alt-fill"></i> Addresses
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 pl-2 m-4 gap-4">
                        @foreach ($addresses as $address)
                            <div class=" border-l-2 border-slate-600 pl-3">
                                <div class="flex items-center gap-1 mb-1">
                                    {{-- Address is stdClass Object --}}
                                    @if ($address->Is_Prmy)
                                        <span class="p-1 rounded-full bg-yellow-400" title="Primary"></span>
                                    @endif
                                    <span class="{{ $labelClass }} block">
                                        {{ $this->getAddressTypeName($address->Admn_Addr_Type_Mast_UIN) }}:
                                    </span>
                                </div>
                                <address class="{{ $dataClass }} not-italic block">
                                    {{-- Line 1: Street, Locality, Landmark --}}
                                    {{ collect([$address->Addr, $address->Loca, $address->Lndm])->filter()->implode(', ') }}
                                    {{-- Line 2: District, State, Pincode --}}
                                    @if ($address->district_name || $address->state_name || $address->pincode)
                                        <br>
                                        {{ collect([$address->district_name, $address->state_name])->filter()->implode(', ') }}
                                        @if ($address->pincode || $address->country_name)
                                            -
                                            {{ collect([$address->pincode, $address->country_name])->filter()->implode(', ') }}
                                        @endif
                                    @endif
                                </address>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-geo-alt-fill"></i> Addresses
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">No addresses added.</p>
                </div>
            @endif

            @if ($contact->Prty === 'I')
                @php
                    $hasEmploymentDetails =
                        $contact->Comp_Name ||
                        $contact->Comp_Dsig ||
                        $contact->Comp_LdLi ||
                        $contact->Comp_Desp ||
                        $contact->Comp_Emai ||
                        $contact->Comp_Web ||
                        $contact->Comp_Addr;
                    $hasProfessionDetails = $contact->Prfl_Name || $contact->Prfl_Addr;
                @endphp

                @if ($hasEmploymentDetails || $hasProfessionDetails)
                    <div class="figma-card bg-slate-600/40">
                        <h2 class="{{ $sectionHeaderClass }} figma-card-header">
                            <i class="bi bi-briefcase-fill"></i> Present Employment
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 pl-2 m-4 border-slate-600 gap-4">
                            @if ($hasEmploymentDetails)
                                <div class=" border-slate-600  border-l-2 pl-3">
                                    <div class="pb-1">
                                        <span class="text-slate-300 font-semibold">Employment</span>
                                    </div>
                                    <div class="space-y">

                                        @if ($contact->Comp_Name)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Company Name:</span>
                                                <span class="{{ $dataClass }}">{{ $contact->Comp_Name }}</span>
                                            </div>
                                        @endif
                                        @if ($contact->Comp_Dsig)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Designation:</span>
                                                <span class="{{ $dataClass }}">{{ $contact->Comp_Dsig }}</span>
                                            </div>
                                        @endif
                                        @if ($contact->Comp_Addr)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Address:</span>
                                                <span class="{{ $dataClass }}">{{ $contact->Comp_Addr }}</span>
                                            </div>
                                        @endif


                                        @if ($contact->Comp_Web)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Website:&nbsp</span>
                                                <a href="{{ $contact->Comp_Web }}" target="_blank"
                                                    class="text-xs text-blue-400 hover:text-blue-300 break-all font-thin">
                                                    {{ $contact->Comp_Web }}</a>
                                            </div>
                                        @endif
                                        @if ($contact->Comp_Emai)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Email:</span>
                                                <span
                                                    class="{{ $dataClass }} break-all">{{ $contact->Comp_Emai }}</span>
                                            </div>
                                        @endif
                                        @if ($contact->Comp_LdLi)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Landline:</span>
                                                <span class="{{ $dataClass }}">{{ $contact->Comp_LdLi }}</span>
                                            </div>
                                        @endif

                                        @if ($contact->Comp_Desp)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Company Business
                                                    Description:</span>
                                                <span class="{{ $dataClass }}">{{ $contact->Comp_Desp }}</span>
                                            </div>
                                        @endif

                                    </div>
                                </div>
                            @endif

                            @if ($hasProfessionDetails)
                                <div class=" border-slate-600 border-l-2 pl-3">
                                    <div class=" text-slate-300 pb-1">
                                        <span class="text-slate-300 font-semibold ">Profession</span>
                                    </div>
                                    <div class="space-y">
                                        @if ($contact->Prfl_Name)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Profession:</span>
                                                <span class="{{ $dataClass }}">{{ $contact->Prfl_Name }}</span>
                                            </div>
                                        @endif
                                        @if ($contact->Prfl_Addr)
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Profession Address:</span>
                                                <span class="{{ $dataClass }}">{{ $contact->Prfl_Addr }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif


                {{-- WORK EXPERIENCE CARD --}}
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-briefcase-fill"></i> Past Working Experience
                    </h2>

                    @if ($workExperiences && count($workExperiences) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 pl-2 m-4 gap-4">
                            @foreach ($workExperiences as $work)
                                @php
                                    $safeFormat = function ($d) {
                                        if (empty($d)) {
                                            return null;
                                        }
                                        try {
                                            return \Carbon\Carbon::parse($d)->format('M Y');
                                        } catch (\Throwable $e) {
                                            return null;
                                        }
                                    };

                                    $from = $safeFormat($work['Prd_From'] ?? null);
                                    $to = $safeFormat($work['Prd_To'] ?? null);

                                    if ($from && $to) {
                                        $period = "$from - $to";
                                    } elseif ($from) {
                                        $period = "From $from";
                                    } elseif ($to) {
                                        $period = "To $to";
                                    } else {
                                        $period = '-';
                                    }
                                @endphp
                                <div class="border-l-2 border-slate-600 pl-3">
                                    <div class="space-y text-white">
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Organization:</span>
                                            <span class="{{ $dataClass }}">{{ $work['Orga_Name'] ?? '-' }}</span>
                                        </div>
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Designation:</span>
                                            <span class="{{ $dataClass }}">{{ $work['Dsgn'] ?? '-' }}</span>
                                        </div>
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Period:</span>
                                            <span class="{{ $dataClass }}">{{ $period }}</span>
                                        </div>
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Country:</span>
                                            <span
                                                class="{{ $dataClass }}">{{ $this->getCountryName($work['Admn_Cutr_Mast_UIN'] ?? null) ?? '-' }}</span>
                                        </div>
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Type:</span>
                                            <span
                                                class="{{ $dataClass }}">{{ $work['Work_Type'] ?? '-' }}</span>
                                        </div>
                                        @if (!empty($work['Job_Desp']))
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Description:</span>
                                                <span class="{{ $dataClass }}">{{ $work['Job_Desp'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-slate-500 pl-2 m-4">No work experience added.</p>
                    @endif
                </div>

                {{-- Education Card --}}
                <div class="figma-card bg-slate-600/40 mb-4">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-book"></i> Education
                    </h2>

                    @if ($educations && count($educations) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 pl-2 m-4 gap-4">
                            @foreach ($educations as $edu)
                                <div class=" border-l-2 border-slate-600 pl-3">
                                    <div class="space-y text-white">
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Degree Name:</span>
                                            <span class="{{ $dataClass }}">{{ $edu['Deg_Name'] }}</span>
                                        </div>
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Institute Name:</span>
                                            <span class="{{ $dataClass }}">{{ $edu['Inst_Name'] }}</span>
                                        </div>
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Completion Year:</span>
                                            <span class="{{ $dataClass }}">{{ $edu['Cmpt_Year'] }}</span>
                                        </div>
                                        @if ($edu['Admn_Cutr_Mast_UIN'])
                                            <div class="{{ $fieldWrapperClass }}">
                                                <span class="{{ $labelClass }}">Country:</span>
                                                <span
                                                    class="{{ $dataClass }}">{{ $this->getCountryName($edu['Admn_Cutr_Mast_UIN']) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-slate-500 pl-2 m-4">No education details added.</p>
                    @endif
                </div>

                {{-- Skills --}}
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-star"></i> Skills
                    </h2>

                    @if ($skills && count($skills) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 pl-2 m-4 gap-4">
                            @foreach ($skills as $skill)
                                <div class=" border-l-2 border-slate-600 pl-3">
                                    <div class="grid text-white space-y">
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Worked On:</span>
                                            <span class="{{ $dataClass }}">{{ $skill['Skil_Type'] }}</span>
                                        </div>

                                        <div>
                                            @if ($skill['Skil_Type_1'] === 'Other')
                                                {{-- Case 1: Type is 'Other', so we hide the type and show the specific Name --}}
                                                <div class="{{ $fieldWrapperClass }}">
                                                    <span class="{{ $labelClass }}">Skill Name:</span>
                                                    <span
                                                        class="{{ $dataClass }}">{{ $skill['Skil_Name'] }}</span>
                                                </div>
                                            @else
                                                {{-- Case 2: Standard Type, show the Type value --}}
                                                <div class="{{ $fieldWrapperClass }}">
                                                    <span class="{{ $labelClass }}">Skill Name:</span>
                                                    <span
                                                        class="{{ $dataClass }}">{{ $skill['Skil_Type_1'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="flex gap-4 items-center">
                                                <span class="{{ $labelClass }}">Proficiency Level:</span>
                                                <span
                                                    class="text-green-400 font-normal">{{ $skill['Profc_Lvl'] }}/5</span>
                                            </div>
                                            <div class="w-1/2 bg-slate-700 rounded-full h-1.5 mt-1">
                                                <div class="bg-green-400 h-1.5 rounded-full"
                                                    style="width: {{ (int) ($skill['Profc_Lvl'] * 20) }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-slate-500 pl-2 m-4">No skills added.</p>
                    @endif
                </div>

            @endif

            {{-- Reference Card --}}
            @if ($contact->referencePersons && $contact->referencePersons->isNotEmpty())
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-people-fill "></i>
                        @if ($contact->Prty === 'B')
                            Authorized Person
                        @else
                            Reference Person
                        @endif
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 pl-2 m-4 gap-4">
                        {{-- Added ->sortByDesc('Is_Prmy') here as a safeguard to ensure Primary is always first --}}
                        @foreach ($contact->referencePersons->sortByDesc('Is_Prmy') as $reference)
                            <div class=" border-l-2 border-slate-600 pl-3">
                                <div class="space-y text-white">
                                    <div class="{{ $fieldWrapperClass }}">
                                        {{-- References are Models, use Object Syntax -> --}}
                                        @if ($reference->Is_Prmy)
                                            <span class="p-1 rounded-full bg-yellow-400" title="Primary"></span>
                                        @endif
                                        <span class="{{ $labelClass }}">
                                            Name:
                                        </span>
                                        <span class="{{ $dataClass }}">{{ $reference->Refa_Name }}</span>
                                    </div>

                                    @if ($reference->Refa_Rsip)
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">
                                                @if ($contact->Prty === 'B')
                                                    Designation:
                                                @else
                                                    Relationship:
                                                @endif
                                            </span>
                                            <span class="{{ $dataClass }}">{{ $reference->Refa_Rsip }}</span>
                                        </div>
                                    @endif

                                    <div class="flex flex-wrap gap-4 mt-1">
                                        @if ($reference->Refa_Emai)
                                            <span class="{{ $dataClass }}">
                                                <i class="bi bi-envelope"></i> {{ $reference->Refa_Emai }}
                                            </span>
                                        @endif
                                        @if ($reference->Refa_Phon)
                                            <span class="{{ $dataClass }}">
                                                <i class="bi bi-telephone"></i> {{ $reference->Refa_Phon }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-people-fill"></i>
                        @if ($contact->Prty === 'B')
                            Authorized Person
                        @else
                            Reference Person
                        @endif
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">No reference persons added.</p>
                </div>
            @endif

            @if (!empty($bankAccounts) && is_array($bankAccounts) && count($bankAccounts) > 0)
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-building"></i> Bank Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 pl-2 m-4 gap-4">
                        @foreach ($bankAccounts as $bank)
                            <div class=" border-l-2 border-slate-600 pl-3">

                                <div class="space-y">
                                    <div class="{{ $fieldWrapperClass }}">
                                        {{-- Bank is stdClass Object, use Object Syntax -> --}}
                                        @if ($bank->Prmy)
                                            <span class="p-1 rounded-full bg-yellow-400"
                                                title="Primary Bank"></span>
                                        @endif
                                        <span class="{{ $labelClass }}">

                                            Bank Name:
                                        </span>
                                        <span class="{{ $dataClass }}">{{ $bank->Bank_Name ?? '-' }}</span>
                                    </div>
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Branch Name:</span>
                                        <span
                                            class="{{ $dataClass }}">{{ $bank->Bank_Brnc_Name ?? '-' }}</span>
                                    </div>
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}"> SWIFT Code:</span>
                                        <span class="{{ $dataClass }}">{{ $bank->Swift_Code ?? '-' }}</span>
                                    </div>

                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">IFSC Code:</span>
                                        <span class="{{ $dataClass }}">{{ $bank->IFSC_Code ?? '-' }}</span>
                                    </div>
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Account Number:</span>
                                        <span
                                            class="{{ $dataClass }} font-mono">{{ $bank->Acnt_Numb ?? '-' }}</span>
                                    </div>
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Account Type:</span>
                                        <span class="{{ $dataClass }}">{{ $bank->Acnt_Type ?? '-' }}</span>
                                    </div>
                                </div>
                                {{-- Attachments for Bank (Array of stdClass Objects) --}}
                                @if (isset($bank->attachments) && count($bank->attachments) > 0)
                                    <div class=" border-slate-700">
                                        <p class="{{ $labelClass }}">Attachments</p>
                                        <div class="flex gap-4 flex-wrap">
                                            @foreach ($bank->attachments as $attachment)
                                                <a href="{{ Storage::url($attachment->Atch_Path) }}"
                                                    target="_blank"
                                                    class=" text-blue-400 text-xs hover:text-blue-300 break-all underline inline-flex items-center gap-1 font-normal">
                                                    <i
                                                        class="bi bi-download"></i>{{ basename($attachment->Atch_Path) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-building"></i> Bank Details
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">No bank accounts added.</p>
                </div>
            @endif

            @if (!empty($documents) && is_array($documents) && count($documents) > 0)
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-file-earmark-pdf"></i>
                        @if ($contact->Prty === 'B')
                            Statutory
                        @endif
                        Documents
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 pl-2 m-4 gap-4">
                        @foreach ($documents as $document)
                            <div class=" border-l-2 border-slate-600 pl-3">
                                <div class="space-y">

                                    <div class="{{ $fieldWrapperClass }}">
                                        {{-- Documents are Arrays, use Array Syntax [] --}}
                                        @if ($document['Prmy'])
                                            <span class="p-1 rounded-full bg-yellow-400"
                                                title="Primary Document"></span>
                                        @endif
                                        <span class="{{ $labelClass }}">

                                            Document Name:
                                        </span>
                                        <span
                                            class="{{ $dataClass }} text-sm !font-normal ">{{ $document['Docu_Name'] ?? '-' }}</span>
                                    </div>
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Registration Number:</span>
                                        <span
                                            class="{{ $dataClass }}">{{ $document['Regn_Numb'] ?? '-' }}</span>
                                    </div>

                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Valid From:</span>
                                        <span class="{{ $dataClass }} ">
                                            @if ($document['Vald_From'] ?? null)
                                                {{ $this->formatDate($document['Vald_From'], 'd-M-Y') }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>

                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Country:</span>
                                        <span class="{{ $dataClass }} ">
                                            @if ($document['Admn_Cutr_Mast_UIN'] ?? null)
                                                {{ $this->getCountryName($document['Admn_Cutr_Mast_UIN']) }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Authority Issued:</span>
                                        <span
                                            class="{{ $dataClass }} ">{{ $document['Auth_Issd'] ?? '-' }}</span>
                                    </div>
                                    <div class="{{ $fieldWrapperClass }}">
                                        <span class="{{ $labelClass }}">Valid Upto:</span>
                                        <span class="{{ $dataClass }} ">
                                            @if ($document['Vald_Upto'] ?? null)
                                                {{ $this->formatDate($document['Vald_Upto'], 'd-M-Y') }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>

                                    @php
                                        $docPath = $document['Docu_Atch_Path'] ?? null;
                                        $hasFile =
                                            isset($docPath) &&
                                            !empty($docPath) &&
                                            !is_object($docPath) &&
                                            $docPath !== '';
                                    @endphp

                                    @if ($hasFile)
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Attachment:</span>
                                            <a href="{{ Storage::url($docPath) }}" target="_blank"
                                                class=" text-blue-400 text-xs hover:text-blue-300 break-all underline inline-flex items-center gap-1 font-normal">
                                                <i class="bi bi-download"></i>{{ basename($docPath) }}
                                            </a>
                                        </div>
                                    @else
                                        <div class="{{ $fieldWrapperClass }}">
                                            <span class="{{ $labelClass }}">Attachment:</span>
                                            <span class="text-slate-400 font-normal">-</span>
                                        </div>
                                    @endif
                                    @if (!empty($document['selected_types']))
                                        <div>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                @foreach ($document['selected_types'] as $typeId)
                                                    @php
                                                        $docType = $allDocumentTypes->firstWhere(
                                                            'Admn_Docu_Type_Mast_UIN',
                                                            $typeId,
                                                        );
                                                    @endphp
                                                    @if ($docType)
                                                        <span
                                                            class="inline-flex items-center border-blue-300/20  font-medium border
                                                            bg-blue-600/20 text-blue-300 text-xs px-1 py rounded-full">
                                                            {{ $docType->Docu_Name }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-file-earmark-pdf"></i> Documents
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">No documents added.</p>
                </div>
            @endif


            @if ($contact->Note)
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-journal-text "></i> Remarks for
                        @if ($contact->Prty === 'I')
                            Contact Person
                        @else
                            Organization
                        @endif
                    </h2>
                    <p class="{{ $dataClass }} whitespace-pre-line pl-2 m-4">{{ $contact->Note }}</p>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-journal-text"></i> Remarks
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">No remarks added.</p>
                </div>
            @endif

            @if ($contact->group?->Name)
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-people-fill"></i> Group Information
                    </h2>
                    <div class="">
                        <div class="flex flex-wrap gap-1 items-baseline pl-2 m-4">
                            <span class="{{ $labelClass }}">Assigned to Group: </span>
                            <span
                                class="{{ $dataClass }} whitespace-pre-line">{{ $contact->group->Name }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-people-fill"></i> Group Information
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">Not assigned to any group.</p>
                </div>
            @endif

            @if ($contact->tags->isNotEmpty())
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-tag"></i> Tags
                    </h2>
                    <div class="flex flex-wrap gap-2 pl-2 m-4">
                        @foreach ($contact->tags as $tag)
                            <span
                                class="bg-blue-600/20 text-blue-300 text-sm px-2 py-1 rounded-full ">{{ $tag->Name }}</span>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-tag"></i> Tags
                    </h2>
                    <p class="text-slate-500 pl-2 m-4">No tags added.</p>
                </div>
            @endif

            @if ($contactNotes && count($contactNotes) > 0)
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-sticky-fill"></i> Notes
                    </h2>
                    <div class="max-h-80 overflow-y-auto pr-2 space-y-2 pl-2 m-4">
                        @foreach ($contactNotes as $note)
                            <div class="py-2.5 px-3 rounded border bg-slate-700/20 border-slate-600/50 ">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex gap-2">
                                        {{-- Note is an Array, use Array Syntax [] --}}
                                        @if ($note['isPinned'])
                                            <i class="bi bi-pin-fill text-yellow-400 "></i>
                                        @endif
                                        <p class="{{ $labelClass }} !font-thin !text-xs">
                                            <span class="">{{ $note['User_Name'] }}</span>
                                            @if ($note['Vertical_Name'])
                                                <span class=" ">
                                                    ({{ $note['Vertical_Name'] }})
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                    <span class="{{ $labelClass }} !font-thin !text-xs">
                                        {{ $note['CrOn']->setTimezone('Asia/Kolkata')->format('d-M-Y h:i A') }}
                                    </span>
                                </div>
                                <p class="{{ $dataClass }} line-clamp-2 break-words">{{ $note['Note_Detl'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="figma-card bg-slate-600/40">
                    <h2 class="{{ $sectionHeaderClass }} figma-card-header ">
                        <i class="bi bi-sticky-fill"></i> Contact Notes
                    </h2>
                    <p class="text-slate-500 pl-2 m-4 "><i class="bi bi-info-circle"></i> No notes added yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>

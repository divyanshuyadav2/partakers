<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\FormatsDates;
use App\Livewire\Traits\GeneratesUINs;
use App\Livewire\Traits\HasAddressCascade;
use App\Livewire\Traits\HasMaxConstants;
use App\Livewire\Traits\HasSkillData;
use App\Livewire\Traits\HasWorkTypes;
use App\Livewire\Traits\LoadsReferenceData;
use App\Livewire\Traits\WithContactValidation;
use App\Livewire\Traits\WithDocumentNames;
use App\Models\Admn_Cnta_Link_Mast;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Dist_Mast;
use App\Models\Admn_PinCode_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Stat_Mast;
use App\Models\Admn_Tag_Mast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateByLink extends Component
{
    use FormatsDates;
    use GeneratesUINs;
    use HasAddressCascade;
    use HasMaxConstants, HasSkillData, HasWorkTypes, WithContactValidation, WithDocumentNames;
    use LoadsReferenceData;
    use WithFileUploads;

    public $organization;

    public $link;

    public $linkExpiresAt;

    public $hasError = false;

    public $errorMessage = '';

    public $errorType = '';

    public $isSuccess = false;

    // Personal Details

    public $Prty = 'I';

    public $Prfx_UIN;

    public $FaNm = '';

    public $MiNm = '';

    public $LaNm = '';

    public $Gend = '';

    public $Blood_Grp;

    public $Brth_Dt;

    public $Anvy_Dt; // Added

    public $Deth_Dt; // Added

    public $Note;

    public $Prfl_Pict;

    public $existing_avatar;

    // Employment
    public $Empl_Type = 'job';

    public $Comp_Name = '';

    public $Comp_Dsig = '';

    public $Comp_LdLi = '';

    public $Comp_Desp = '';

    public $Comp_Emai = '';

    public $Comp_Web = '';

    public $Comp_Addr = '';

    public $Prfl_Name = '';

    public $Prfl_Addr = '';

    // Web Presence
    public $Web = '';

    public $FcBk = '';

    public $Twtr = '';

    public $LnDn = '';

    public $Intg = '';

    public $Yaho = '';

    public $Redt = '';

    public $Ytb = '';

    // Collections
    public array $emails = [];

    public array $phones = [];

    public array $landlines = [];

    public array $addresses = [];

    public array $references = [];

    public array $bankAccounts = [];

    public array $documents = [];

    public array $educations = [];

    public array $skills = [];

    public $skillTypes = [];

    public $skillSubtypes = [];

    public array $workTypes = [];

    public array $workExperiences = [];

    public array $availableGroups = [];

    // Reference Data
    public $allPrefixes;

    public $allCountries;

    public $addressTypes;

    public $bankOptions = [];

    public $allDocumentTypes = [];

    public function mount(string $token): void
    {
        try {
            $this->validateAndLoadLink($token);

            if ($this->hasError) {
                return;
            }

            $this->loadCommonReferenceData();
            $this->initializeCollections();
            $this->skillTypes = $this->getSkillTypes();
            $this->skillSubtypes = $this->getSkillSubtypes();
            // load work-type options from trait
            $this->workTypes = $this->getWorkTypes();
            $this->initializeWithDocumentNames();
        } catch (\Exception $e) {
            Log::error('Error loading CreateByLink component: '.$e->getMessage(), [
                'token' => $token,
                'exception' => $e,
            ]);
            $this->setError('System Error', 'An unexpected error occurred while loading this page.');
        }
    }

    public function updated($propertyName)
    {
        // 1. Skip validation for UI flags or reference data
        $skipValidation = [
            'addresses', 'Empl_Type', 'addressTypes', 'allCountries', 'allPrefixes',
            'organization', 'link', 'linkExpiresAt', 'hasError', 'errorMessage',
            'errorType', 'isSuccess', 'bankOptions', 'allDocumentTypes', 'existing_avatar',
            'pincodeSearch',
        ];

        if (Str::contains($propertyName, $skipValidation) || Str::endsWith($propertyName, 'pincodeSearch')) {
            return;
        }

        if (in_array($propertyName, ['FaNm', 'MiNm', 'LaNm'])) {
            $this->$propertyName = preg_replace('/[^a-zA-Z ]/', '', $this->$propertyName);
        }

        // 2. Handle Bank File Uploads (Logic, not just validation)
        // We must keep this call to process the temp file into the array
        if (preg_match('/bankAccounts\.(\d+)\.temp_upload/', $propertyName, $matches)) {
            $this->handleBankUpload($matches[1]);

            return;
        }

        // 3. Skip flags
        if (Str::contains($propertyName, ['Has_WtAp', 'Has_Telg', 'Is_Prmy'])) {
            return;
        }

        // 4. Cross-Validation: Bank Name <-> Account Number
        if (preg_match('/bankAccounts\.(\d+)\.(Bank_Name_UIN|Acnt_Numb)/', $propertyName, $matches)) {
            $index = $matches[1];

            $this->validateOnly($propertyName); // Validate changed field

            // Trigger validation on the partner field
            if (str_contains($propertyName, 'Bank_Name_UIN')) {
                $this->validateOnly("bankAccounts.{$index}.Acnt_Numb");
            } elseif (str_contains($propertyName, 'Acnt_Numb')) {
                $this->validateOnly("bankAccounts.{$index}.Bank_Name_UIN");
            }

            return;
        }

        // 5. Cross-Validation: Document Dates (From <-> To)
        if (preg_match('/documents\.(\d+)\.(Vald_From|Vald_Upto)/', $propertyName, $matches)) {
            $index = $matches[1];

            $this->validateOnly($propertyName); // Validate changed field

            // Trigger validation on the partner field
            if (str_contains($propertyName, 'Vald_From')) {
                $this->validateOnly("documents.{$index}.Vald_Upto");
            } elseif (str_contains($propertyName, 'Vald_Upto')) {
                $this->validateOnly("documents.{$index}.Vald_From");
            }

            return;
        }

        // 6. Default Validation (Uses Trait Rules)
        $this->validateOnly($propertyName);
    }

    public function render()
    {
        return view('livewire.contacts.create-by-link')->layout('components.layouts.guest');
    }

    private function validateAndLoadLink(string $token): void
    {
        $link = Admn_Cnta_Link_Mast::where('Tokn', $token)->first();

        if (! $link) {
            $this->setError('Invalid Link', 'This invitation link is not valid or does not exist.', self::ERROR_TYPE_INVALID);

            return;
        }

        if ($link->Is_Used) {
            $this->setError('Link Already Used', 'This invitation link has already been used.', self::ERROR_TYPE_USED);

            return;
        }

        if ($link->Expy_Dt && $link->Expy_Dt->isPast()) {
            $expiredDate = $link->Expy_Dt->format('M j, Y \a\t g:i A');
            $this->setError('Link Expired', "This invitation link expired on {$expiredDate}.", self::ERROR_TYPE_EXPIRED);

            return;
        }

        if (! $link->Is_Actv) {
            $this->setError('Link Inactive', 'This invitation link has been deactivated.', self::ERROR_TYPE_INACTIVE);

            return;
        }

        $this->link = $link;

        if ($link->Expy_Dt) {
            $this->linkExpiresAt = $link->Expy_Dt->toISOString();
        }

        $this->organization = DB::table('admn_orga_mast')
            ->where('Orga_UIN', $this->link->Admn_Orga_Mast_UIN)
            ->value('Orga_Name');
    }

    private function setError(string $title, string $message, string $type = self::ERROR_TYPE_INVALID): void
    {
        $this->hasError = true;
        $this->errorMessage = $message;
        $this->errorType = $type;

        Log::warning("Link access error: {$title}", [
            'message' => $message,
            'type' => $type,
        ]);
    }

    private function loadPrefixes()
    {
        try {
            return Admn_Prfx_Name_Mast::where('Stau_UIN', self::STATUS_ACTIVE)->orderBy('Prfx_Name')->get();
        } catch (\Exception $e) {
            return Admn_Prfx_Name_Mast::orderBy('Prfx_Name')->get();
        }
    }

    private function initializeCollections(): void
    {
        $this->addEmail();
        $this->addPhone();
        $this->addLandline();
        $this->addAddress();
        $this->addReference();
        $this->addBank();
        $this->addDocument();
        $this->addEducation();
        $this->addSkill();
        $this->addWorkExperience();
    }

    // Collection Methods (Email, Phone, Landline, etc.)
    public function addEmail(): void
    {
        if (count($this->emails) < self::MAX_EMAILS) {
            $this->emails[] = [
                'Emai_Addr' => '',
                'Emai_Type' => 'self generated',
                'Is_Prmy' => empty($this->emails),
            ];
        }
    }

    public function removeEmail($index): void
    {
        $this->removeItem('emails', $index);
    }

    public function setPrimaryEmail($index): void
    {
        $this->setPrimaryItem('emails', $index);
    }

    public function addPhone(): void
    {
        if (count($this->phones) < self::MAX_PHONES) {
            $this->phones[] = [
                'Phon_Numb' => '',
                'Phon_Type' => 'self',
                'Cutr_Code' => self::PHONE_CODE_INDIA,
                'Has_WtAp' => false,
                'Has_Telg' => false,
                'Is_Prmy' => empty($this->phones),
            ];
        }
    }

    public function removePhone($index): void
    {
        $this->removeItem('phones', $index);
    }

    public function setPrimaryPhone($index): void
    {
        $this->setPrimaryItem('phones', $index);
    }

    public function addLandline(): void
    {
        if (count($this->landlines) < self::MAX_LANDLINES) {
            $this->landlines[] = [
                'Land_Numb' => '',
                'Land_Type' => 'home',
                'Cutr_Code' => self::PHONE_CODE_INDIA,
                'Is_Prmy' => empty($this->landlines),
            ];
        }
    }

    public function removeLandline($index): void
    {
        $this->removeItem('landlines', $index);
    }

    public function setPrimaryLandline($index): void
    {
        $this->setPrimaryItem('landlines', $index);
    }

    public function addAddress(): void
    {
        if (count($this->addresses) >= self::MAX_ADDRESSES) {
            return;
        }

        $india = collect($this->allCountries)->firstWhere('Admn_Cutr_Mast_UIN', self::COUNTRY_INDIA_UIN);
        $defaultCountryUIN = $india ? $india->Admn_Cutr_Mast_UIN : null;

        $this->addresses[] = $this->hydrateAddressFields([
            'Addr' => '',
            'Loca' => '',
            'Lndm' => '',
            'Admn_Addr_Type_Mast_UIN' => null,
            'Is_Prmy' => empty($this->addresses),
            'Admn_Cutr_Mast_UIN' => $defaultCountryUIN,
            'Admn_Stat_Mast_UIN' => null,
            'Admn_Dist_Mast_UIN' => null,
            'Admn_PinCode_Mast_UIN' => null,
        ]);
    }

    public function removeAddress(int $index): void
    {
        $this->resetErrorBag("addresses.$index");
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);

        if (collect($this->addresses)->where('Is_Prmy', true)->isEmpty() && ! empty($this->addresses)) {
            $this->addresses[0]['Is_Prmy'] = true;
        }
    }

    public function setPrimaryAddress($index): void
    {
        $this->setPrimaryItem('addresses', $index);
        $this->syncPhoneCodeWithPrimaryAddress();
    }

    private function syncPhoneCodeWithPrimaryAddress(): void
    {
        try {
            $primaryAddress = collect($this->addresses)->firstWhere('Is_Prmy', true);

            if ($primaryAddress && ! empty($primaryAddress['Admn_Cutr_Mast_UIN'])) {
                $country = Admn_Cutr_Mast::find($primaryAddress['Admn_Cutr_Mast_UIN']);

                if ($country && $country->Phon_Code) {
                    foreach ($this->phones as &$phone) {
                        $phone['Cutr_Code'] = $country->Phon_Code;
                    }
                    $this->dispatch('primary-country-changed', newPhoneCode: $country->Phon_Code);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing phone code: '.$e->getMessage());
        }
    }

    public function updatedAddresses($value, $key): void
    {
        [$index, $field] = explode('.', $key) + [null, null];

        if (! isset($this->addresses[$index])) {
            return;
        }

        $addr = &$this->addresses[$index];

        switch ($field) {

            case 'Admn_Cutr_Mast_UIN':
                $addr['Admn_Stat_Mast_UIN'] =
                $addr['Admn_Dist_Mast_UIN'] =
                $addr['Admn_PinCode_Mast_UIN'] = null;

                $addr['statesForDropdown'] = $value
                    ? Admn_Stat_Mast::where('Admn_Cutr_Mast_UIN', $value)
                        ->where('Stau_UIN', 100201) // status Active
                        ->orderBy('Name')
                        ->get()
                        ->toArray()
                    : [];

                $addr['districtsForDropdown'] = [];
                $addr['pincodesForDropdown'] = [];

                if ($addr['Is_Prmy']) {
                    $this->syncPhoneCodeWithPrimaryAddress();
                }
                break;

            case 'Admn_Stat_Mast_UIN':
                $addr['Admn_Dist_Mast_UIN'] =
                $addr['Admn_PinCode_Mast_UIN'] = null;

                $addr['districtsForDropdown'] = $value
                    ? Admn_Dist_Mast::where('Admn_Stat_Mast_UIN', $value)
                        ->where('Stau_UIN', 100201) // status Active
                        ->orderBy('Name')
                        ->get()
                        ->toArray()
                    : [];

                $addr['pincodesForDropdown'] = [];
                break;

            case 'Admn_Dist_Mast_UIN':
                $addr['Admn_PinCode_Mast_UIN'] = null;

                $addr['pincodesForDropdown'] = $value
                    ? Admn_PinCode_Mast::where('Admn_Dist_Mast_UIN', $value)
                        ->where('Stau_UIN', 100201) // status Active
                        ->orderBy('Code')
                        ->select('Admn_PinCode_Mast_UIN', 'Code')
                        ->get()
                        ->toArray()
                    : [];
                break;

            case 'pincodeSearch':
                if (strlen(trim($value)) < 3) {
                    $addr['pincodeResults'] = [];

                    return;
                }

                $query = Admn_PinCode_Mast::where('Code', 'like', $value.'%')
                    ->where('Stau_UIN', 100201); // status Active

                if ($addr['Admn_Dist_Mast_UIN']) {
                    $query->where('Admn_Dist_Mast_UIN', $addr['Admn_Dist_Mast_UIN']);
                } elseif ($addr['Admn_Stat_Mast_UIN']) {
                    $query->whereHas('district', fn ($q) => $q->where('Admn_Stat_Mast_UIN', $addr['Admn_Stat_Mast_UIN'])
                    );
                } elseif ($addr['Admn_Cutr_Mast_UIN']) {
                    $query->whereHas('district.state', fn ($q) => $q->where('Admn_Cutr_Mast_UIN', $addr['Admn_Cutr_Mast_UIN'])
                    );
                }

                $addr['pincodeResults'] = $query->take(10)->get()->toArray();
                break;
        }
    }

    private function handleCountryChange(&$addr, $value): void
    {
        $addr['Admn_Stat_Mast_UIN'] = $addr['Admn_Dist_Mast_UIN'] = $addr['Admn_PinCode_Mast_UIN'] = null;
        $addr['statesForDropdown'] = $value ? Admn_Stat_Mast::where('Admn_Cutr_Mast_UIN', $value)->orderBy('Name')->get()->toArray() : [];
        $addr['districtsForDropdown'] = $addr['pincodesForDropdown'] = [];

        if ($addr['Is_Prmy']) {
            $this->syncPhoneCodeWithPrimaryAddress();
        }
    }

    private function handleStateChange(&$addr, $value): void
    {
        $addr['Admn_Dist_Mast_UIN'] = $addr['Admn_PinCode_Mast_UIN'] = null;
        $addr['districtsForDropdown'] = $value ? Admn_Dist_Mast::where('Admn_Stat_Mast_UIN', $value)->orderBy('Name')->get()->toArray() : [];
        $addr['pincodesForDropdown'] = [];
    }

    private function handleDistrictChange(&$addr, $value): void
    {
        $addr['Admn_PinCode_Mast_UIN'] = null;
        $addr['pincodesForDropdown'] = $value ? Admn_PinCode_Mast::where('Admn_Dist_Mast_UIN', $value)
            ->orderBy('Code')
            ->select('Admn_PinCode_Mast_UIN', 'Code')
            ->get()
            ->toArray() : [];
    }

    private function handlePincodeSearch(&$addr, $value): void
    {
        if (strlen(trim($value)) < 3) {
            $addr['pincodeResults'] = [];

            return;
        }

        $query = Admn_PinCode_Mast::where('Code', 'like', $value.'%');

        if ($addr['Admn_Dist_Mast_UIN']) {
            $query->where('Admn_Dist_Mast_UIN', $addr['Admn_Dist_Mast_UIN']);
        } elseif ($addr['Admn_Stat_Mast_UIN']) {
            $query->whereHas('district', fn ($q) => $q->where('Admn_Stat_Mast_UIN', $addr['Admn_Stat_Mast_UIN']));
        } elseif ($addr['Admn_Cutr_Mast_UIN']) {
            $query->whereHas('district.state', fn ($q) => $q->where('Admn_Cutr_Mast_UIN', $addr['Admn_Cutr_Mast_UIN']));
        }

        $addr['pincodeResults'] = $query->take(10)->get()->toArray();
    }

    public function selectPincode($index, $pincodeUIN): void
    {
        $pincode = Admn_PinCode_Mast::with('district.state.country')
            ->where('Stau_UIN', 100201) // status Active
            ->where('Admn_PinCode_Mast_UIN', $pincodeUIN)
            ->first();

        if (! $pincode || ! isset($this->addresses[$index])) {
            return;
        }

        $addr = &$this->addresses[$index];

        $addr['pincodeSearch'] = $pincode->Code;
        $addr['Admn_PinCode_Mast_UIN'] = $pincode->Admn_PinCode_Mast_UIN;
        $addr['Admn_Dist_Mast_UIN'] = $pincode->district?->Admn_Dist_Mast_UIN;
        $addr['Admn_Stat_Mast_UIN'] = $pincode->district?->state?->Admn_Stat_Mast_UIN;
        $addr['Admn_Cutr_Mast_UIN'] = $pincode->district?->state?->country?->Admn_Cutr_Mast_UIN;

        $addr['statesForDropdown'] = $addr['Admn_Cutr_Mast_UIN']
            ? Admn_Stat_Mast::where('Admn_Cutr_Mast_UIN', $addr['Admn_Cutr_Mast_UIN'])
                ->where('Stau_UIN', 100201) // status Active
                ->orderBy('Name')
                ->get()
                ->toArray()
            : [];

        $addr['districtsForDropdown'] = $addr['Admn_Stat_Mast_UIN']
            ? Admn_Dist_Mast::where('Admn_Stat_Mast_UIN', $addr['Admn_Stat_Mast_UIN'])
                ->where('Stau_UIN', 100201) // status Active
                ->orderBy('Name')
                ->get()
                ->toArray()
            : [];

        $addr['pincodeResults'] = [];
    }

    private function hydrateAddressFields($data, $id = null)
    {
        $countryId = $data['Admn_Cutr_Mast_UIN'] ?? null;
        $stateId = $data['Admn_Stat_Mast_UIN'] ?? null;
        $distId = $data['Admn_Dist_Mast_UIN'] ?? null;

        return array_merge($data, [
            'id' => $id,
            'pincodeSearch' => $data['pincode_value'] ?? '',

            'statesForDropdown' => $countryId
                ? Admn_Stat_Mast::where('Admn_Cutr_Mast_UIN', $countryId)
                    ->where('Stau_UIN', 100201) // status Active
                    ->orderBy('Name')
                    ->get()
                    ->toArray()
                : [],

            'districtsForDropdown' => $stateId
                ? Admn_Dist_Mast::where('Admn_Stat_Mast_UIN', $stateId)
                    ->where('Stau_UIN', 100201) // status Active
                    ->orderBy('Name')
                    ->get()
                    ->toArray()
                : [],

            'pincodesForDropdown' => $distId
                ? Admn_PinCode_Mast::where('Admn_Dist_Mast_UIN', $distId)
                    ->where('Stau_UIN', 100201) // status Active
                    ->orderBy('Code')
                    ->select('Admn_PinCode_Mast_UIN', 'Code')
                    ->get()
                    ->toArray()
                : [],

            'pincodeResults' => [],
        ]);
    }

    public function addReference(): void
    {
        if (count($this->references) < self::MAX_REFERENCES) {
            $isFirst = empty($this->references);
            $this->references[] = [
                'Refa_Name' => '',
                'Refa_Phon' => '',
                'Refa_Emai' => '',
                'Refa_Rsip' => '',
                'Is_Prmy' => $isFirst,
            ];
        }
    }

    public function removeReference(int $index): void
    {
        $this->resetErrorBag("references.$index");
        unset($this->references[$index]);
        $this->references = array_values($this->references);

        if (collect($this->references)->where('Is_Prmy', true)->isEmpty() && ! empty($this->references)) {
            $this->references[0]['Is_Prmy'] = true;
        }
    }

    public function setPrimaryReference($selectedIndex)
    {
        foreach ($this->references as $index => &$ref) {
            $ref['Is_Prmy'] = ($index === $selectedIndex);
        }
    }

    public function addBank(): void
    {
        if (count($this->bankAccounts) < self::MAX_BANKS) {
            $this->bankAccounts[] = [
                'Bank_Name_UIN' => '',
                'Bank_Brnc_Name' => '',
                'Acnt_Type' => '',
                'Acnt_Numb' => '',
                'IFSC_Code' => '',
                'Swift_Code' => '',
                'Prmy' => false,
                'newAttachments' => [],
                'existing_attachments' => [],
                'temp_upload' => [],
            ];
        }
    }

    public function removeBank(int $index): void
    {
        $this->removeItem('bankAccounts', $index);
    }

    public function setPrimaryBank(int $index): void
    {
        $this->setPrimaryItem('bankAccounts', $index, 'Prmy');
    }

    public function handleBankUpload($index)
    {
        if (! isset($this->bankAccounts[$index])) {
            return;
        }

        // Clear previous errors FIRST
        $this->resetErrorBag("bankAccounts.$index.newAttachments.*");

        $tempFiles = $this->bankAccounts[$index]['temp_upload'] ?? [];

        if (empty($tempFiles)) {
            return;
        }

        if (! is_array($tempFiles)) {
            $tempFiles = [$tempFiles];
        }

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

        foreach ($tempFiles as $file) {
            if (! ($file instanceof \Illuminate\Http\UploadedFile)) {
                continue;
            }

            if (! in_array($file->getMimeType(), $allowedMimes)) {
                $this->addError(
                    "bankAccounts.{$index}.newAttachments.*",
                    "Bank attachment '{$file->getClientOriginalName()}' must be PDF, JPG, or PNG."
                );

                continue;
            }

            if ($file->getSize() > self::MAX_BANK_FILE_SIZE) {
                $fileSizeKB = round($file->getSize() / 1024, 1);
                $this->addError(
                    "bankAccounts.{$index}.newAttachments.*",
                    "Bank attachment '{$file->getClientOriginalName()}' exceeds the 100KB limit. File size: {$fileSizeKB}KB."
                );

                continue;
            }

            // Only add to newAttachments if validation passed
            $this->bankAccounts[$index]['newAttachments'][] = $file;
        }

        $this->bankAccounts[$index]['temp_upload'] = [];
    }

    public function validateDocumentFile($index)
    {
        if (! isset($this->documents[$index])) {
            return;
        }

        $file = $this->documents[$index]['Docu_Atch_Path'] ?? null;

        if (! $file) {
            return;
        }

        // If it's not an uploaded file, return
        if (! ($file instanceof \Illuminate\Http\UploadedFile)) {
            return;
        }

        // Validate file type
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($file->getMimeType(), $allowedMimes)) {
            $this->addError(
                "documents.{$index}.Docu_Atch_Path",
                'Document attachment must be PDF, JPG, PNG, or WEBP.'
            );
            $this->documents[$index]['Docu_Atch_Path'] = null;

            return;
        }

        // Validate file size
        if ($file->getSize() > self::MAX_DOCUMENT_FILE_SIZE) {
            $fileSizeKB = round($file->getSize() / 1024, 1);
            $this->addError(
                "documents.{$index}.Docu_Atch_Path",
                "Document attachment '{$file->getClientOriginalName()}' exceeds the 100KB limit. File size: {$fileSizeKB}KB."
            );
            $this->documents[$index]['Docu_Atch_Path'] = null;

            return;
        }
    }

    public function removeNewAttachment($bankIndex, $attachmentIndex): void
    {
        if (isset($this->bankAccounts[$bankIndex]['newAttachments'][$attachmentIndex])) {
            array_splice($this->bankAccounts[$bankIndex]['newAttachments'], $attachmentIndex, 1);
        }
    }

    public function addDocument(): void
    {
        if (count($this->documents) < self::MAX_DOCUMENTS) {
            $this->documents[] = [
                'Docu_Name' => '',
                'selected_types' => [],
                'Regn_Numb' => '',
                'Admn_Cutr_Mast_UIN' => '',
                'Auth_Issd' => '',
                'Vald_From' => '',
                'Vald_Upto' => '',
                'Docu_Atch_Path' => null,
                'is_dropdown_open' => false,
                'Prmy' => count($this->documents) === 0,
            ];
        }
    }

    public function removeDocument(int $index): void
    {
        if (isset($this->documents[$index]['Docu_Atch_Path'])) {
            $path = $this->documents[$index]['Docu_Atch_Path'];
            if ($path && ! is_object($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        $this->resetErrorBag("documents.$index");
        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
    }

    public function setPrimaryDocument($index)
    {
        foreach ($this->documents as $key => &$doc) {
            $doc['Prmy'] = ($key === $index);
        }
    }

    public function addEducation(): void
    {
        if (count($this->educations) < self::MAX_EDUCATIONS) {
            $this->educations[] = [
                'Deg_Name' => '',
                'Inst_Name' => '',
                'Cmpt_Year' => (int) date('Y'),
                'Admn_Cutr_Mast_UIN' => self::COUNTRY_INDIA_UIN,
            ];
        }
    }

    public function removeEducation(int $index): void
    {
        $this->resetErrorBag("educations.$index");
        unset($this->educations[$index]);
        $this->educations = array_values($this->educations);
    }

    public function addSkill(): void
    {
        if (count($this->skills) < self::MAX_SKILLS) {
            $this->skills[] = [
                'Skil_Type' => '',
                'Skil_Type_1' => '',
                'Skil_Name' => '',
                'Profc_Lvl' => 1,
            ];
        }
    }

    public function removeSkill(int $index): void
    {
        $this->resetErrorBag("skills.$index");
        unset($this->skills[$index]);
        $this->skills = array_values($this->skills);
    }

    public function addWorkExperience(): void
    {
        if (count($this->workExperiences) < self::MAX_WORK_EXPERIENCES) {
            $this->workExperiences[] = [
                'Orga_Name' => '',
                'Dsgn' => '',
                'Prd_From' => '',
                'Prd_To' => '',
                'Work_Type' => 'Full',
                'Admn_Cutr_Mast_UIN' => self::COUNTRY_INDIA_UIN,
                'Job_Desp' => '',
            ];
        }
    }

    public function removeWorkExperience(int $index): void
    {
        $this->resetErrorBag("workExperiences.$index");
        unset($this->workExperiences[$index]);
        $this->workExperiences = array_values($this->workExperiences);
    }

    public function toggleDocumentDropdown($index): void
    {
        $currentState = $this->documents[$index]['is_dropdown_open'] ?? false;

        foreach ($this->documents as &$doc) {
            $doc['is_dropdown_open'] = false;
        }

        $this->documents[$index]['is_dropdown_open'] = ! $currentState;
    }

    public function selectDocumentType($docIndex, $typeId): void
    {
        if (! in_array($typeId, $this->documents[$docIndex]['selected_types'] ?? [])) {
            $this->documents[$docIndex]['selected_types'][] = $typeId;
        }
        $this->documents[$docIndex]['is_dropdown_open'] = false;
    }

    public function removeDocumentType($docIndex, $typeId): void
    {
        $this->documents[$docIndex]['selected_types'] = array_values(
            array_filter(
                $this->documents[$docIndex]['selected_types'] ?? [],
                fn ($id) => $id != $typeId
            )
        );
    }

    public function removeDocumentAttachment($index): void
    {
        if (! isset($this->documents[$index])) {
            return;
        }

        $path = $this->documents[$index]['Docu_Atch_Path'] ?? null;

        if ($path && ! is_object($path)) {
            Storage::disk('public')->delete($path);
        }

        $this->documents[$index]['Docu_Atch_Path'] = null;
        $this->resetErrorBag("documents.$index.Docu_Atch_Path");
    }

    public function removeProfilePicture(): void
    {
        $this->Prfl_Pict = null;
        $this->resetErrorBag('Prfl_Pict');
    }

    private function generateAttachmentFileName($extension = ''): string
    {
        $orgUIN = $this->link->Admn_Orga_Mast_UIN;
        $timestamp = now()->format('Ymd_His');
        $filename = "{$orgUIN}_{$timestamp}";

        if (! empty($extension)) {
            $filename .= ".{$extension}";
        }

        return $filename;
    }

    private function removeItem($arrayName, $index): void
    {
        if (count($this->$arrayName) <= 1) {
            return;
        }

        $this->resetErrorBag("$arrayName.$index");
        unset($this->{$arrayName}[$index]);
        $this->{$arrayName} = array_values($this->{$arrayName});

        if (collect($this->$arrayName)->where('Is_Prmy', true)->isEmpty() && ! empty($this->$arrayName)) {
            $this->{$arrayName}[0]['Is_Prmy'] = true;
        }
    }

    private function setPrimaryItem($arrayName, $index, $field = 'Is_Prmy'): void
    {
        foreach ($this->$arrayName as $idx => &$item) {
            $item[$field] = ($idx == $index);
        }
    }

    public function getAllCountriesCollectionProperty(): Collection
    {
        return collect($this->allCountries);
    }

    public function save(): void
    {
        try {
            if (! $this->link) {
                throw new \Exception('Invalid link session.');
            }

            $this->link->refresh();

            if (! $this->isLinkValid()) {
                $this->setError('Link Invalid', 'This link is no longer valid.');

                return;
            }

            $this->validate($this->rules(), $this->messages());
            $this->validateCustomLogic();

            if ($this->getErrorBag()->any()) {
                $this->dispatch('scroll-to-errors');

                return;
            }

            DB::transaction(function () {
                $userUIN = $this->link->CrBy ?? self::DEFAULT_CREATOR;
                $orgaUIN = $this->link->Admn_Orga_Mast_UIN;

                $contactId = $this->insertMainContact($userUIN, $orgaUIN);
                $this->insertEmails($contactId, $userUIN);
                $this->insertPhones($contactId, $userUIN);
                $this->insertLandlines($contactId, $userUIN);
                $this->insertAddresses($contactId, $userUIN);
                $this->insertReferences($contactId, $userUIN);
                $this->insertBankAccounts($contactId, $userUIN);
                $this->insertDocuments($contactId, $userUIN);
                $this->insertByLinkTag($contactId, $userUIN);
                $this->insertEducations($contactId, $userUIN);
                $this->insertSkills($contactId, $userUIN);
                $this->insertWorkExperiences($contactId, $userUIN);

                $this->link->markAsUsed();
            });

            $this->isSuccess = true;
            $this->dispatch('save-success', ['message' => 'Your information has been submitted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for link token: '.($this->link->Tokn ?? 'unknown'), [
                'errors' => $e->errors(),
            ]);
            $this->dispatch('scroll-to-errors');
            throw $e;
        } catch (\Exception $e) {

            Log::error('Error saving contact via link: '.$e->getMessage(), [
                'exception' => $e,
                'token' => $this->link->Tokn ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
            dd($e->getMessage());
            session()->flash('error', 'An unexpected error occurred while saving your information. Please try again.');
            $this->dispatch('scroll-to-errors');
        }
    }

    private function isLinkValid(): bool
    {
        if ($this->link->Is_Used) {
            $this->setError('Link Already Used', 'This invitation link has already been used.', self::ERROR_TYPE_USED);

            return false;
        }

        if ($this->link->Expy_Dt && $this->link->Expy_Dt->isPast()) {
            $expiredDate = $this->link->Expy_Dt->format('M j, Y \a\t g:i A');
            $this->setError('Link Expired', "This invitation link expired on {$expiredDate}.", self::ERROR_TYPE_EXPIRED);

            return false;
        }

        if (! $this->link->Is_Actv) {
            $this->setError('Link Inactive', 'This invitation link has been deactivated.', self::ERROR_TYPE_INACTIVE);

            return false;
        }

        return true;
    }

    private function insertMainContact(): int
    {
        $contactId = $this->generateUniqueUIN('admn_user_mast', 'Admn_User_Mast_UIN');

        $avatarPath = $this->processProfilePicture();

        $insertData = [
            'Admn_User_Mast_UIN' => $contactId,
            'Prfx_UIN' => $this->Prfx_UIN ?: null,
            'FaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->FaNm)),
            'MiNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->MiNm)) ?: null,
            'LaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->LaNm)) ?: null,
            'Gend' => $this->Gend ?: null,
            'Blood_Grp' => $this->Blood_Grp ?: null,

            // Updated Date Logic using formatDateForDatabase
            'Brth_Dt' => $this->formatDateForDatabase($this->Brth_Dt),
            'Anvy_Dt' => $this->formatDateForDatabase($this->Anvy_Dt),
            'Deth_Dt' => $this->formatDateForDatabase($this->Deth_Dt),

            'Prfl_Pict' => $avatarPath,
            'Note' => trim($this->Note) ?: null, // Added Note
            'Comp_Name' => trim($this->Comp_Name) ?: null,
            'Comp_Dsig' => trim($this->Comp_Dsig) ?: null,
            'Comp_LdLi' => trim($this->Comp_LdLi) ?: null,
            'Comp_Desp' => trim($this->Comp_Desp) ?: null,
            'Comp_Emai' => trim(strtolower($this->Comp_Emai)) ?: null,
            'Comp_Web' => trim($this->Comp_Web) ?: null,
            'Comp_Addr' => trim($this->Comp_Addr) ?: null,
            'Prfl_Name' => trim($this->Prfl_Name) ?: null,
            'Prfl_Addr' => trim($this->Prfl_Addr) ?: null,
            'Web' => trim($this->Web) ?: null,
            'FcBk' => trim($this->FcBk) ?: null,
            'Twtr' => trim($this->Twtr) ?: null,
            'LnDn' => trim($this->LnDn) ?: null,
            'Intg' => trim($this->Intg) ?: null,
            'Yaho' => trim($this->Yaho) ?: null,
            'Redt' => trim($this->Redt) ?: null,
            'Ytb' => trim($this->Ytb) ?: null,
            'Admn_Orga_Mast_UIN' => $this->link->Admn_Orga_Mast_UIN,
            'Is_Actv' => self::STATUS_ACTIVE,
            'Is_Vf' => self::STATUS_UNVERIFIED,
            'Prty' => 'I', // Assuming by link usually means Individual for now, adjust if needed
            'CrOn' => now(),
            'MoOn' => now(),
            'CrBy' => $this->link->CrBy ?? self::DEFAULT_CREATOR,
        ];

        DB::table('admn_user_mast')->insert($insertData);

        Log::info("Contact created via link with UIN: {$contactId}");

        return $contactId;
    }

    private function processProfilePicture(): ?string
    {
        if (! $this->Prfl_Pict) {
            return null;
        }

        try {
            Log::info('Processing profile picture upload...');

            if ($this->Prfl_Pict->getSize() > self::MAX_PROFILE_FILE_SIZE) {
                Log::warning('Profile picture size exceeds 2MB limit');

                return null;
            }

            $extension = $this->Prfl_Pict->getClientOriginalExtension();
            $filename = $this->generateAttachmentFileName($extension);
            $filePath = $this->Prfl_Pict->storeAs('Attachment/Profile', $filename, 'public');

            if (! $filePath) {
                throw new \Exception('File storage returned false/null');
            }

            Log::info('File stored as: '.$filePath);

            try {
                $fullPath = Storage::disk('public')->path($filePath);

                if (file_exists($fullPath) && class_exists('Intervention\Image\Facades\Image')) {
                    Image::make($fullPath)->fit(512, 512)->save();
                    Log::info('Image resized successfully');
                }
            } catch (\Exception $e) {
                Log::warning('Image processing failed (but file upload succeeded): '.$e->getMessage());
            }

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error storing profile picture: '.$e->getMessage());

            return null;
        }
    }

    private function insertEmails(int $contactId, int $userUIN): void
    {
        $validEmails = collect($this->emails)->filter(
            fn ($email) => ! empty(trim($email['Emai_Addr'] ?? ''))
        );

        if ($validEmails->isEmpty()) {
            return;
        }

        $emailUINs = $this->generateBatchUINs('admn_cnta_emai_mast', 'Admn_Cnta_Emai_Mast_UIN', $validEmails->count());

        foreach ($validEmails as $index => $email) {
            DB::table('admn_cnta_emai_mast')->insert([
                'Admn_Cnta_Emai_Mast_UIN' => $emailUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Emai_Addr' => trim(strtolower($email['Emai_Addr'])),
                'Emai_Type' => $email['Emai_Type'] ?? 'self generated',
                'Is_Prmy' => $email['Is_Prmy'] ?? false,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertPhones(int $contactId, int $userUIN): void
    {
        $validPhones = collect($this->phones)->filter(
            fn ($phone) => ! empty(trim($phone['Phon_Numb'] ?? ''))
        );

        if ($validPhones->isEmpty()) {
            return;
        }

        $phoneUINs = $this->generateBatchUINs('admn_cnta_phon_mast', 'Admn_Cnta_Phon_Mast_UIN', $validPhones->count());

        foreach ($validPhones as $index => $phone) {
            DB::table('admn_cnta_phon_mast')->insert([
                'Admn_Cnta_Phon_Mast_UIN' => $phoneUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Phon_Numb' => trim($phone['Phon_Numb']),
                'Phon_Type' => $phone['Phon_Type'] ?? 'self',
                'Cutr_Code' => $phone['Cutr_Code'] ?? self::PHONE_CODE_INDIA,
                'Has_WtAp' => $phone['Has_WtAp'] ?? false,
                'Has_Telg' => $phone['Has_Telg'] ?? false,
                'Is_Prmy' => $phone['Is_Prmy'] ?? false,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertLandlines(int $contactId, int $userUIN): void
    {
        $validLandlines = collect($this->landlines)->filter(
            fn ($landline) => ! empty(trim($landline['Land_Numb'] ?? ''))
        );

        if ($validLandlines->isEmpty()) {
            return;
        }

        $landlineUINs = $this->generateBatchUINs('admn_cnta_land_mast', 'Admn_Cnta_Land_Mast_UIN', $validLandlines->count());

        foreach ($validLandlines as $index => $landline) {
            DB::table('admn_cnta_land_mast')->insert([
                'Admn_Cnta_Land_Mast_UIN' => $landlineUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Land_Numb' => trim($landline['Land_Numb']),
                'Land_Type' => $landline['Land_Type'] ?? 'home',
                'Cutr_Code' => $landline['Cutr_Code'] ?? self::PHONE_CODE_INDIA,
                'Is_Prmy' => $landline['Is_Prmy'] ?? false,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertAddresses(int $contactId, int $userUIN): void
    {
        $validAddresses = collect($this->addresses)->filter(
            fn ($address) => ! empty(trim($address['Addr'] ?? '')) || ! empty($address['Admn_PinCode_Mast_UIN'])
        );

        if ($validAddresses->isEmpty()) {
            return;
        }

        $addressUINs = $this->generateBatchUINs('admn_cnta_addr_mast', 'Admn_Cnta_Addr_Mast_UIN', $validAddresses->count());

        foreach ($validAddresses->values() as $index => $address) {
            $cleanAddress = $this->cleanDataForDatabase($address);

            DB::table('admn_cnta_addr_mast')->insert([
                'Admn_Cnta_Addr_Mast_UIN' => $addressUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Addr' => trim($cleanAddress['Addr'] ?? '') ?: null,
                'Loca' => trim($cleanAddress['Loca'] ?? '') ?: null,
                'Lndm' => trim($cleanAddress['Lndm'] ?? '') ?: null,
                'Admn_Addr_Type_Mast_UIN' => $cleanAddress['Admn_Addr_Type_Mast_UIN'] ?? null,
                'Is_Prmy' => $cleanAddress['Is_Prmy'] ?? false,
                'Admn_Cutr_Mast_UIN' => $cleanAddress['Admn_Cutr_Mast_UIN'] ?? null,
                'Admn_Stat_Mast_UIN' => $cleanAddress['Admn_Stat_Mast_UIN'] ?? null,
                'Admn_Dist_Mast_UIN' => $cleanAddress['Admn_Dist_Mast_UIN'] ?? null,
                'Admn_PinCode_Mast_UIN' => $cleanAddress['Admn_PinCode_Mast_UIN'] ?? null,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertReferences(int $contactId, int $userUIN): void
    {
        $validReferences = collect($this->references)->filter(
            fn ($reference) => ! empty(trim($reference['Refa_Name'] ?? '')) || ! empty(trim($reference['Refa_Emai'] ?? '')) || ! empty(trim($reference['Refa_Phon'] ?? ''))
        );

        if ($validReferences->isEmpty()) {
            return;
        }

        $referenceUINs = $this->generateBatchUINs('admn_cnta_refa_mast', 'Admn_Cnta_Refa_Mast_UIN', $validReferences->count());

        foreach ($validReferences as $index => $reference) {
            DB::table('admn_cnta_refa_mast')->insert([
                'Admn_Cnta_Refa_Mast_UIN' => $referenceUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Refa_Name' => trim($reference['Refa_Name']) ?: null,
                'Refa_Phon' => trim($reference['Refa_Phon']) ?: null,
                'Refa_Emai' => trim(strtolower($reference['Refa_Emai'])) ?: null,
                'Refa_Rsip' => trim($reference['Refa_Rsip']) ?: null,
                'Is_Prmy' => $reference['Is_Prmy'] ?? false,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertBankAccounts(int $contactId, int $userUIN): void
    {
        $validBanks = collect($this->bankAccounts)->filter(
            fn ($bank) => ! empty($bank['Bank_Name_UIN']) && ! empty(trim($bank['Acnt_Numb'] ?? ''))
        );

        if ($validBanks->isEmpty()) {
            return;
        }

        $bankUINs = $this->generateBatchUINs('admn_user_bank_mast', 'Admn_User_Bank_Mast_UIN', $validBanks->count());

        $totalAttachments = $validBanks->sum(fn ($bank) => count($bank['newAttachments'] ?? []));
        $attachmentUINs = $totalAttachments > 0 ? $this->generateBatchUINs('admn_bank_attachments', 'Admn_Bank_Attachment_UIN', $totalAttachments) : [];

        $attachmentUinIndex = 0;

        foreach ($validBanks->values() as $index => $bank) {
            $currentBankUIN = $bankUINs[$index];

            DB::table('admn_user_bank_mast')->insert([
                'Admn_User_Bank_Mast_UIN' => $currentBankUIN,
                'Admn_User_Mast_UIN' => $contactId,
                'Admn_Orga_Mast_UIN' => $this->link->Admn_Orga_Mast_UIN,
                'Bank_Name_UIN' => $bank['Bank_Name_UIN'],
                'Bank_Brnc_Name' => trim($bank['Bank_Brnc_Name']) ?: null,
                'Acnt_Type' => $bank['Acnt_Type'] ?: null,
                'Acnt_Numb' => trim($bank['Acnt_Numb']),
                'IFSC_Code' => trim($bank['IFSC_Code']) ?: null,
                'Swift_Code' => trim($bank['Swift_Code']) ?: null,
                'Prmy' => $bank['Prmy'] ?? false,
                'Stau' => self::STATUS_ACTIVE,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if (! empty($bank['newAttachments'])) {
                foreach ($bank['newAttachments'] as $file) {
                    $this->storeBankAttachment(
                        $currentBankUIN,
                        $file,
                        $attachmentUINs[$attachmentUinIndex++],
                        $userUIN
                    );
                }
            }
        }
    }

    private function storeBankAttachment($bankUIN, $file, $attachmentUIN, $userUIN): void
    {
        try {
            if ($file->getSize() > self::MAX_BANK_FILE_SIZE) {
                Log::warning("Bank attachment exceeds 100KB limit: {$file->getClientOriginalName()}");
                session()->flash('error', "Bank attachment '{$file->getClientOriginalName()}' exceeds 100 KB limit.");

                return;
            }

            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (! in_array($file->getMimeType(), $allowedMimes)) {
                Log::warning("Invalid bank attachment type: {$file->getMimeType()}");
                session()->flash('error', "Invalid file type for '{$file->getClientOriginalName()}'. Allowed: PDF, JPG, PNG.");

                return;
            }

            $extension = $file->getClientOriginalExtension();
            $filename = $this->generateAttachmentFileName($extension);
            $attachmentPath = $file->storeAs('Attachment/Bank', $filename, 'public');

            DB::table('admn_bank_attachments')->insert([
                'Admn_Bank_Attachment_UIN' => $attachmentUIN,
                'Admn_User_Bank_Mast_UIN' => $bankUIN,
                'Atch_Path' => $attachmentPath,
                'Orgn_Name' => $file->getClientOriginalName(),
                'CrOn' => now(),
                'CrBy' => $userUIN,
            ]);

            Log::info("Bank attachment stored: {$attachmentPath}");
        } catch (\Exception $e) {
            Log::error('Error storing bank attachment: '.$e->getMessage());
            session()->flash('error', 'Error uploading bank attachment: '.$e->getMessage());
        }
    }

    public function clearDocumentFile($index)
    {
        if (isset($this->documents[$index])) {
            $this->documents[$index]['Docu_Atch_Path'] = null;
            $this->resetErrorBag("documents.$index.Docu_Atch_Path");
        }
    }

    private function insertDocuments(int $contactId, int $userUIN): void
    {
        $validDocuments = collect($this->documents)->filter(
            fn ($doc) => ! empty($doc['selected_types']) && ! empty(trim($doc['Regn_Numb'] ?? ''))
        );

        if ($validDocuments->isEmpty()) {
            return;
        }

        $totalDocRecords = $validDocuments->sum(
            fn ($doc) => count(is_array($doc['selected_types'] ?? []) ? $doc['selected_types'] : [])
        );

        $docUINs = $this->generateBatchUINs('admn_docu_mast', 'Admn_Docu_Mast_UIN', $totalDocRecords);

        $uinIndex = 0;

        foreach ($validDocuments as $doc) {
            $docAttachmentPath = $this->storeDocumentFile($doc);
            $docTypes = is_array($doc['selected_types'] ?? []) ? $doc['selected_types'] : [];

            foreach ($docTypes as $typeId) {
                DB::table('admn_docu_mast')->insert([
                    'Admn_Docu_Mast_UIN' => $docUINs[$uinIndex++],
                    'Admn_User_Mast_UIN' => $contactId,
                    'Admn_Orga_Mast_UIN' => $this->link->Admn_Orga_Mast_UIN,
                    'Admn_Cutr_Mast_UIN' => $doc['Admn_Cutr_Mast_UIN'] ?: null,
                    'Admn_Docu_Type_Mast_UIN' => $typeId,
                    'Docu_Name' => trim($doc['Docu_Name']) ?: null,
                    'Auth_Issd' => trim($doc['Auth_Issd']) ?: null,
                    'Regn_Numb' => trim($doc['Regn_Numb']),

                    // Updated Date Logic
                    'Vald_From' => $this->formatDateForDatabase($doc['Vald_From']),
                    'Vald_Upto' => $this->formatDateForDatabase($doc['Vald_Upto']),

                    'Docu_Atch_Path' => $docAttachmentPath,
                    'Prmy' => $doc['Prmy'] ?? false,
                    'Stau' => self::STATUS_ACTIVE,
                    'CrOn' => now(),
                    'MoOn' => now(),
                    'CrBy' => $userUIN,
                ]);

                if ($uinIndex > 1) {
                    usleep(50000);
                }
            }
        }
    }

    private function storeDocumentFile($doc): ?string
    {
        try {
            if (empty($doc['Docu_Atch_Path'])) {
                return null;
            }

            if (is_object($doc['Docu_Atch_Path'])) {
                $file = $doc['Docu_Atch_Path'];

                if ($file->getSize() > self::MAX_DOCUMENT_FILE_SIZE) {
                    Log::warning("Document attachment exceeds 100KB: {$file->getClientOriginalName()}");
                    session()->flash('error', "Document '{$file->getClientOriginalName()}' exceeds 100 KB limit.");

                    return null;
                }

                $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
                if (! in_array($file->getMimeType(), $allowedMimes)) {
                    Log::warning("Invalid document type: {$file->getMimeType()}");
                    session()->flash('error', 'Invalid document type. Allowed: PDF, JPG, PNG, WEBP.');

                    return null;
                }

                $extension = $file->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $storagePath = $file->storeAs('Attachment/Document', $filename, 'public');

                Log::info("Document stored: {$storagePath}");

                return $storagePath;
            }

            return $doc['Docu_Atch_Path'];
        } catch (\Exception $e) {
            Log::error('Error uploading document: '.$e->getMessage());
            session()->flash('error', 'Error uploading document: '.$e->getMessage());

            return null;
        }
    }

    private function insertEducations(int $contactId, int $userUIN): void
    {
        $validItems = collect($this->educations)->filter(
            fn ($item) => ! empty(trim($item['Deg_Name'] ?? '')) && ! empty(trim($item['Inst_Name'] ?? ''))
        );

        if ($validItems->isEmpty()) {
            return;
        }

        $uins = $this->generateBatchUINs('admn_user_educ_mast', 'Admn_User_Educ_Mast_UIN', $validItems->count());

        foreach ($validItems->values() as $index => $item) {
            DB::table('admn_user_educ_mast')->insert([
                'Admn_User_Educ_Mast_UIN' => $uins[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Deg_Name' => trim($item['Deg_Name']),
                'Inst_Name' => trim($item['Inst_Name']),
                'Cmpt_Year' => (int) $item['Cmpt_Year'],
                'Admn_Cutr_Mast_UIN' => $item['Admn_Cutr_Mast_UIN'] ?: null,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertSkills(int $contactId, int $userUIN): void
    {
        $validSkills = collect($this->skills)->filter(function ($skill) {
            if (empty($skill['Skil_Type']) || empty($skill['Skil_Type_1'])) {
                return false;
            }
            if ($skill['Skil_Type_1'] !== 'Other') {
                return true;
            }

            return ! empty(trim($skill['Skil_Name'] ?? ''));
        });

        if ($validSkills->isEmpty()) {
            return;
        }

        $uins = $this->generateBatchUINs('admn_user_skil_mast', 'Admn_User_Skil_Mast_UIN', $validSkills->count());

        foreach ($validSkills->values() as $index => $skill) {
            $skillName = ($skill['Skil_Type_1'] !== 'Other') ? $skill['Skil_Type_1'] : trim($skill['Skil_Name']);

            DB::table('admn_user_skil_mast')->insert([
                'Admn_User_Skil_Mast_UIN' => $uins[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Skil_Type' => trim($skill['Skil_Type']) ?: null,
                'Skil_Type_1' => trim($skill['Skil_Type_1']) ?: null,
                'Skil_Name' => $skillName,
                'Profc_Lvl' => $skill['Profc_Lvl'] ?: null,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertWorkExperiences(int $contactId, int $userUIN): void
    {
        $validItems = collect($this->workExperiences)->filter(
            fn ($item) => ! empty(trim($item['Orga_Name'] ?? '')) && ! empty(trim($item['Dsgn'] ?? ''))
        );

        if ($validItems->isEmpty()) {
            return;
        }

        $uins = $this->generateBatchUINs('admn_user_work_mast', 'Admn_User_Work_Mast_UIN', $validItems->count());

        foreach ($validItems->values() as $index => $item) {
            DB::table('admn_user_work_mast')->insert([
                'Admn_User_Work_Mast_UIN' => $uins[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Orga_Name' => trim($item['Orga_Name']),
                'Dsgn' => trim($item['Dsgn']),

                // Updated Date Logic
                'Prd_From' => $this->formatDateForDatabase($item['Prd_From']),
                'Prd_To' => $this->formatDateForDatabase($item['Prd_To']),

                'Work_Type' => $item['Work_Type'] ?? 'Full',
                'Admn_Cutr_Mast_UIN' => $item['Admn_Cutr_Mast_UIN'] ?: null,
                'Job_Desp' => trim($item['Job_Desp']) ?: null,
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertByLinkTag(int $contactId, int $userUIN): void
    {
        $byLinkTag = Admn_Tag_Mast::where('Admn_Tag_Mast_UIN', self::BY_LINK_TAG_ID)->first();

        if (! $byLinkTag) {
            return;
        }

        $tagUIN = $this->generateUniqueUIN('admn_cnta_tag_mast', 'Admn_Cnta_Tag_Mast_UIN');

        DB::table('admn_cnta_tag_mast')->insert([
            'Admn_Cnta_Tag_Mast_UIN' => $tagUIN,
            'Admn_User_Mast_UIN' => $contactId,
            'Admn_Tag_Mast_UIN' => $byLinkTag->Admn_Tag_Mast_UIN,
            'CrOn' => now(),
            'MoOn' => now(),
            'CrBy' => $userUIN,
        ]);
    }

    private function cleanDataForDatabase($data): array
    {
        return collect($data)->except([
            'id',
            'statesForDropdown',
            'districtsForDropdown',
            'pincodeResults',
            'pincodeSearch',
            'pincodesForDropdown',
            'existing_docs',
            'is_dropdown_open',
            'temp_upload',
            'newAttachments',
            'existing_attachments',
            'Admn_User_Bank_Mast_UIN',
            'Admn_Cnta_Addr_Mast_UIN',
            'Admn_Cnta_Emai_Mast_UIN',
            'Admn_Cnta_Phon_Mast_UIN',
            'Admn_Cnta_Land_Mast_UIN',
            'Admn_Cnta_Refa_Mast_UIN',
        ])->toArray();
    }

    public function cancel(): void
    {
        redirect()->route('contacts.index');
    }
}

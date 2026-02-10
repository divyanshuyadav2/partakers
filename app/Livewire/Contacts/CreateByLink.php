<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\FormatsDates;
use App\Livewire\Traits\GeneratesUINs;
use App\Livewire\Traits\HasAddressCascade;
use App\Livewire\Traits\HasMaxConstants;
use App\Livewire\Traits\HasSkillData;
use App\Livewire\Traits\HasWorkTypes;
use App\Livewire\Traits\LoadsReferenceData;
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
    use HasMaxConstants, HasSkillData, HasWorkTypes, WithDocumentNames;
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
            Log::error('Error loading CreateByLink component: ' . $e->getMessage(), [
                'token' => $token,
                'exception' => $e,
            ]);
            $this->setError('System Error', 'An unexpected error occurred while loading this page.');
        }
    }



    public function updated($propertyName)
    {
        /* -------------------------------------------------
         | 1. Selected Tags (normalize + validate as whole)
         |--------------------------------------------------*/
        if (Str::startsWith($propertyName, 'selectedTags')) {
            $this->normalizeSelectedTags();

            // ✅ Use trait rules only (NO manual rules/messages)
            $this->validateOnly('selectedTags');

            return;
        }

        /* -------------------------------------------------
         | 2. Skip validation for UI / reference-only props
         |--------------------------------------------------*/
        $skipValidation = [
            'addresses',
            'Empl_Type',
            'addressTypes',
            'allCountries',
            'allPrefixes',
            'organization',
            'link',
            'linkExpiresAt',
            'hasError',
            'errorMessage',
            'errorType',
            'isSuccess',
            'bankOptions',
            'allDocumentTypes',
            'existing_avatar',
            'pincodeSearch',
        ];

        if (
            Str::contains($propertyName, $skipValidation) ||
            Str::endsWith($propertyName, 'pincodeSearch')
        ) {
            return;
        }

        /* -------------------------------------------------
         | 3. Name sanitization (non-business)
         |--------------------------------------------------*/
        if (in_array($propertyName, ['FaNm', 'MiNm', 'LaNm']) && $this->Prty !== 'B') {
            $this->$propertyName = preg_replace('/[^a-zA-Z ]/', '', $this->$propertyName);
        }

        /* -------------------------------------------------
         | 4. Bank Attachments (temp_upload → newAttachments)
         |--------------------------------------------------*/
        if (preg_match('/bankAccounts\.(\d+)\.temp_upload/', $propertyName, $matches)) {
            $index = $matches[1];

            // 🔥 CLEAR PREVIOUS ERRORS FOR THIS BANK INDEX
            $this->resetErrorBag("bankAccounts.$index");
            $this->resetErrorBag("bankAccounts.$index.newAttachments");
            $this->resetErrorBag("bankAccounts.$index.newAttachments.*");

            // Move files from temp_upload → newAttachments
            $this->handleBankUpload($index);


            // ✅ Validate using TRAIT rules
            $this->validateOnly("bankAccounts.$index.newAttachments.*");

            return;
        }


        /* -------------------------------------------------
         | 5. Skip boolean flags (UX choice)
         |--------------------------------------------------*/
        if (Str::contains($propertyName, ['Has_WtAp', 'Has_Telg', 'Is_Prmy'])) {
            return;
        }

        /* -------------------------------------------------
         | 6. Cross-validation: Bank Name ↔ Account Type ↔ Account Number
         |--------------------------------------------------*/
        if (preg_match('/bankAccounts\.(\d+)\.(Bank_Name_UIN|Acnt_Type|Acnt_Numb)/', $propertyName, $matches)) {
            $index = $matches[1];

            // Validate the changed field
            $this->validateOnly($propertyName);

            // Always validate all three related fields together for consistency
            $this->validateOnly("bankAccounts.$index.Bank_Name_UIN");
            $this->validateOnly("bankAccounts.$index.Acnt_Type");
            $this->validateOnly("bankAccounts.$index.Acnt_Numb");

            return;
        }

        /* -------------------------------------------------
         | 7. Cross-validation: Document Dates (From ↔ To)
         |--------------------------------------------------*/
        if (
            str_starts_with($propertyName, 'documents.') &&
            str_ends_with($propertyName, '.Docu_Atch_Path')
        ) {
            $index = explode('.', $propertyName)[1];

            // Clear old error
            $this->resetErrorBag("documents.$index.Docu_Atch_Path");

            try {
                // Validate using trait rules
                $this->validateOnly($propertyName);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // ❌ Invalid → remove file reference
                $this->documents[$index]['Docu_Atch_Path'] = null;
                throw $e;
            }

            return;
        }


        /* -------------------------------------------------
         | 8. Default validation (trait-driven)
         |--------------------------------------------------*/
        $this->validateOnly($propertyName);
    }

    public function render()
    {
        return view('livewire.contacts.create-by-link')->layout('components.layouts.guest');
    }

    private function validateAndLoadLink(string $token): void
    {
        $link = Admn_Cnta_Link_Mast::where('Tokn', $token)->first();

        if (!$link) {
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

        if (!$link->Is_Actv) {
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

        if (collect($this->addresses)->where('Is_Prmy', true)->isEmpty() && !empty($this->addresses)) {
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

            if ($primaryAddress && !empty($primaryAddress['Admn_Cutr_Mast_UIN'])) {
                $country = Admn_Cutr_Mast::find($primaryAddress['Admn_Cutr_Mast_UIN']);

                if ($country && $country->Phon_Code) {
                    foreach ($this->phones as &$phone) {
                        $phone['Cutr_Code'] = $country->Phon_Code;
                    }
                    $this->dispatch('primary-country-changed', newPhoneCode: $country->Phon_Code);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing phone code: ' . $e->getMessage());
        }
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

        if (collect($this->references)->where('Is_Prmy', true)->isEmpty() && !empty($this->references)) {
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

    protected function handleBankUpload(int $index): void
    {
        // Ensure arrays exist
        $this->bankAccounts[$index]['newAttachments'] ??= [];

        // Clear previous errors for this bank index
        $this->resetErrorBag("bankAccounts.$index.newAttachments");

        foreach ($this->bankAccounts[$index]['temp_upload'] as $file) {

            // Temporarily push file
            $this->bankAccounts[$index]['newAttachments'][] = $file;

            $fileIndex = array_key_last($this->bankAccounts[$index]['newAttachments']);

            try {
                // Validate THIS file using TRAIT rules & messages
                $this->validateOnly(
                    "bankAccounts.$index.newAttachments.$fileIndex"
                );

            } catch (\Illuminate\Validation\ValidationException $e) {
                // Invalid remove file from ready list
                unset($this->bankAccounts[$index]['newAttachments'][$fileIndex]);

                // Reindex array after removal
                $this->bankAccounts[$index]['newAttachments'] =
                    array_values($this->bankAccounts[$index]['newAttachments']);

                // error bag
                throw $e;
            }
        }

        // Clear temp upload input
        $this->bankAccounts[$index]['temp_upload'] = [];
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
            if ($path && !is_object($path)) {
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

        $this->documents[$index]['is_dropdown_open'] = !$currentState;
    }

    public function selectDocumentType($docIndex, $typeId): void
    {
        if (!in_array($typeId, $this->documents[$docIndex]['selected_types'] ?? [])) {
            $this->documents[$docIndex]['selected_types'][] = $typeId;
        }
        $this->documents[$docIndex]['is_dropdown_open'] = false;
    }

    public function removeDocumentType($docIndex, $typeId): void
    {
        $this->documents[$docIndex]['selected_types'] = array_values(
            array_filter(
                $this->documents[$docIndex]['selected_types'] ?? [],
                fn($id) => $id != $typeId
            )
        );
    }

    public function removeDocumentAttachment($index): void
    {
        if (!isset($this->documents[$index])) {
            return;
        }

        $path = $this->documents[$index]['Docu_Atch_Path'] ?? null;

        if ($path && !is_object($path)) {
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

        if (!empty($extension)) {
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

        if (collect($this->$arrayName)->where('Is_Prmy', true)->isEmpty() && !empty($this->$arrayName)) {
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
            if (!$this->link) {
                throw new \Exception('Invalid link session.');
            }

            $this->link->refresh();

            if (!$this->isLinkValid()) {
                $this->setError('Link Invalid', 'This link is no longer valid.');

                return;
            }

            $this->validate($this->rules(), $this->messages());

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

                $this->insertByLinkTag($contactId, $userUIN);
                $this->insertEducations($contactId, $userUIN);
                $this->insertSkills($contactId, $userUIN);
                $this->insertWorkExperiences($contactId, $userUIN);

                $this->link->markAsUsed();
            });

            $this->isSuccess = true;
            $this->dispatch('save-success', ['message' => 'Your information has been submitted successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for link token: ' . ($this->link->Tokn ?? 'unknown'), [
                'errors' => $e->errors(),
            ]);
            $this->dispatch('scroll-to-errors');
            throw $e;
        } catch (\Exception $e) {

            Log::error('Error saving contact via link: ' . $e->getMessage(), [
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

        if (!$this->link->Is_Actv) {
            $this->setError('Link Inactive', 'This invitation link has been deactivated.', self::ERROR_TYPE_INACTIVE);

            return false;
        }

        return true;
    }

    // ========================================================================
    // MAIN RULES
    // ========================================================================
    public function rules()
    {
        // 1. Determine Limits
        $maxEmails = defined('static::MAX_EMAILS') ? static::MAX_EMAILS : 5;
        $maxPhones = defined('static::MAX_PHONES') ? static::MAX_PHONES : 5;
        $maxLandlines = defined('static::MAX_LANDLINES') ? static::MAX_LANDLINES : 5;
        $maxAddresses = defined('static::MAX_ADDRESSES') ? static::MAX_ADDRESSES : 5;
        $maxRefs = defined('static::MAX_REFERENCES') ? static::MAX_REFERENCES : 5;
        $maxBanks = defined('static::MAX_BANKS') ? static::MAX_BANKS : 5;
        $maxDocs = defined('static::MAX_DOCUMENTS') ? static::MAX_DOCUMENTS : 10;
        $maxEduc = defined('static::MAX_EDUCATIONS') ? static::MAX_EDUCATIONS : 10;
        $maxSkills = defined('static::MAX_SKILLS') ? static::MAX_SKILLS : 20;
        $maxWork = defined('static::MAX_WORK_EXPERIENCES') ? static::MAX_WORK_EXPERIENCES : 10;

        $rules = [
            'FaNm' => 'required|string|max:50',
            'Gend' => 'required|string',
            'Brth_Dt' => 'nullable|date|before:today',
            'MiNm' => 'nullable|string|max:50',
            'LaNm' => 'nullable|string|max:50',
            'Blood_Grp' => 'nullable|string',
            'Prfl_Pict' => 'nullable|image|mimes:jpg,png,webp|max:2048', // 2MB

            // Employment / Organization
            'Comp_Name' => 'nullable|string|max:50',
            'Comp_Dsig' => 'nullable|string|max:30',
            'Comp_LdLi' => 'nullable|numeric|digits_between:5,20',
            'Comp_Desp' => 'nullable|string|max:500',
            'Comp_Emai' => 'nullable|email|max:255', // 'email' rule handles regex and format
            'Comp_Web' => 'nullable|url|max:255',
            'Comp_Addr' => 'nullable|string|max:500',
            'Prfl_Name' => 'nullable|string|max:30',
            'Prfl_Addr' => 'nullable|string|max:500',

            // Web Presence (Replaced validateDomain with Regex)
            'Web' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'LnDn' => ['nullable', 'url', 'max:255', 'regex:/linkedin\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Twtr' => ['nullable', 'url', 'max:255', 'regex:/(twitter\.com|x\.com)/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'FcBk' => ['nullable', 'url', 'max:255', 'regex:/facebook\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Intg' => ['nullable', 'url', 'max:255', 'regex:/instagram\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Redt' => ['nullable', 'url', 'max:255', 'regex:/reddit\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Ytb' => ['nullable', 'url', 'max:255', 'regex:/(youtube\.com|youtu\.be)/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Yaho' => ['nullable', 'url', 'max:255', 'regex:/yahoo\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],

            // Collections
            'phones' => "nullable|array|max:$maxPhones",
            'phones.*.Cutr_Code' => 'nullable|numeric|digits_between:1,5',
            'phones.*.Phon_Type' => 'nullable|string',
            'phones.*.Phon_Numb' => [
                'nullable',
                'numeric',
                'distinct',
                // Inline closure replaces validatePhoneNumberFormat
                function ($attribute, $value, $fail) {
                    if (preg_match('/phones\.(\d+)\.Phon_Numb/', $attribute, $matches)) {
                        $index = $matches[1];
                        $phone = $this->phones[$index] ?? [];
                        $countryCode = $phone['Cutr_Code'] ?? '91';

                        // Assumes getAllCountriesCollectionProperty is available in the component
                        $country = $this->getAllCountriesCollectionProperty()->firstWhere('Phon_Code', $countryCode);

                        if (!$country) {
                            $fail('A valid country must be selected for this mobile number.');

                            return;
                        }

                        $requiredLength = (int) $country->MoNo_Digt;
                        $currentLength = strlen(preg_replace('/\D/', '', $value));

                        if ($currentLength !== $requiredLength) {
                            $fail("The mobile number must be exactly {$requiredLength} digits for {$country->Name}.");
                        }
                    }
                },
            ],
            'phones.*.Has_WtAp' => 'nullable|boolean',
            'phones.*.Has_Telg' => 'nullable|boolean',

            'landlines' => "nullable|array|max:$maxLandlines",
            'landlines.*.Cutr_Code' => 'nullable|numeric|digits_between:1,5',
            'landlines.*.Land_Numb' => 'nullable|numeric|digits_between:5,20|distinct',
            'landlines.*.Land_Type' => 'nullable|string',

            'emails' => "nullable|array|max:$maxEmails",
            'emails.*.Emai_Addr' => 'nullable|email|max:255|distinct',
            'emails.*.Emai_Type' => 'nullable',

            'addresses' => "nullable|array|max:$maxAddresses",
            'addresses.*.Addr' => 'nullable|string|max:255|distinct',
            'addresses.*.Loca' => 'nullable|string|max:50|distinct',
            'addresses.*.Lndm' => 'nullable|string|max:100|distinct',
            'addresses.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN',
            'addresses.*.Admn_Stat_Mast_UIN' => 'nullable|exists:admn_stat_mast,Admn_Stat_Mast_UIN',
            'addresses.*.Admn_Dist_Mast_UIN' => 'nullable|exists:admn_dist_mast,Admn_Dist_Mast_UIN',
            'addresses.*.Admn_PinCode_Mast_UIN' => 'nullable|exists:admn_pincode_mast,Admn_PinCode_Mast_UIN',

            'references' => "nullable|array|max:$maxRefs",
            'references.*.Refa_Name' => 'nullable|string|max:50|distinct',
            'references.*.Refa_Emai' => 'nullable|email|max:50|distinct',
            'references.*.Refa_Phon' => 'nullable|numeric|digits_between:5,20|distinct',
            'references.*.Refa_Rsip' => 'nullable|string|max:50',

            // Banks
            'bankAccounts' => "nullable|array|max:$maxBanks",
            'bankAccounts.*.Bank_Name_UIN' => 'nullable|exists:admn_bank_name,Bank_UIN|required_with:bankAccounts.*.Acnt_Numb',
            'bankAccounts.*.Acnt_Numb' => 'nullable|string|max:50|distinct|required_with:bankAccounts.*.Bank_Name_UIN',
            'bankAccounts.*.Bank_Brnc_Name' => 'nullable|string|max:50',
            'bankAccounts.*.Acnt_Type' => 'nullable|string|max:50|required_with:bankAccounts.*.Bank_Name_UIN',
            'bankAccounts.*.IFSC_Code' => 'nullable|string|max:11',
            'bankAccounts.*.Swift_Code' => 'nullable|string|max:11|min:8',
            'bankAccounts.*.newAttachments.*' => 'file|mimes:pdf,jpg,png,webp|max:100', // 100KB

            // Documents (Updated to remove custom closures)
            'documents' => "nullable|array|max:$maxDocs",
            'documents.*.selected_types' => 'nullable|array',
            'documents.*.selected_types.*' => 'exists:admn_docu_type_mast,Admn_Docu_Type_Mast_UIN',

            // Name: Required if types selected + Distinct check
            'documents.*.Docu_Name' => 'nullable|string|max:255|distinct|required_with:documents.*.selected_types',

            // Reg Number: Required if types selected
            'documents.*.Regn_Numb' => 'nullable|string|max:100|required_with:documents.*.selected_types',

            'documents.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN|required_with:documents.*.Docu_Name',
            'documents.*.Auth_Issd' => 'nullable|string|max:100',
            'documents.*.Vald_From' => 'nullable|date',
            // Replaced validateDateRange with 'after'
            'documents.*.Vald_Upto' => 'nullable|date|after:documents.*.Vald_From',
            // Replaced validateDocumentAttachment with native file rules
            'documents.*.Docu_Atch_Path' => 'nullable|file|mimes:pdf,jpg,png,webp|max:200',

            // Education, Skills, Work
            'educations' => "nullable|array|max:$maxEduc",
            'educations.*.Deg_Name' => 'nullable|string|max:100',
            'educations.*.Inst_Name' => 'nullable|string|max:255',
            'educations.*.Cmpt_Year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'educations.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN',

            'skills' => "nullable|array|max:$maxSkills",
            'skills.*.Skil_Type' => 'nullable|string|max:100',
            'skills.*.Skil_Type_1' => 'nullable|string|max:100',
            'skills.*.Skil_Name' => 'nullable|string|max:150',
            'skills.*.Profc_Lvl' => 'nullable|integer|min:1|max:5',

            'workExperiences' => "nullable|array|max:$maxWork",
            'workExperiences.*.Orga_Name' => 'nullable|string|max:255',
            'workExperiences.*.Dsgn' => 'nullable|string|max:150',
            'workExperiences.*.Prd_From' => 'nullable|date|before:today|required_with:workExperiences.*.Prd_To,workExperiences.*.Orga_Name',
            // Replaced custom date range logic with 'after'
            'workExperiences.*.Prd_To' => 'nullable|date|before_or_equal:today|after:workExperiences.*.Prd_From|required_with:workExperiences.*.Prd_From,workExperiences.*.Orga_Name',
            'workExperiences.*.Orga_Type' => 'nullable|string|max:100',
            'workExperiences.*.Job_Desp' => 'nullable|string|max:1000',
            'workExperiences.*.Work_Type' => 'nullable|string',
            'workExperiences.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN',

            'Note' => 'nullable|string|max:5000',
            'addresses.*.Admn_Addr_Type_Mast_UIN' => [
                'nullable',
                'distinct',
                'required_with:addresses.*.Addr,addresses.*.Loca,addresses.*.Lndm,addresses.*.Admn_PinCode_Mast_UIN',
            ],
            'Prfx_UIN' => 'nullable|integer|exists:admn_prfx_name_mast,Prfx_Name_UIN',
        ];

        return $rules;
    }

    // ========================================================================
    // MESSAGES
    // ========================================================================
    public function messages()
    {
        $commonMessages = [

            // Personal
            'Prfx_UIN.integer' => 'The prefix must be a valid ID.',
            'Prfx_UIN.exists' => 'The selected prefix is invalid.',

            'FaNm.required' => 'The first name is required.',
            'FaNm.string' => 'The first name must be a string.',
            'FaNm.max' => 'The first name may not be greater than :max characters.',
            'Gend.required' => 'The gender field is required.',
            'Brth_Dt.date' => 'The birth date must be a valid date.',
            'Brth_Dt.before' => 'The birth date must be before today.',
            'references.*.Refa_Name.distinct' => "This reference person's name has been submitted previously.",
            'references.*.Refa_Emai.distinct' => 'This email address has been submitted previously.',
            'references.*.Refa_Phon.distinct' => "This reference person's mobile has been submitted previously.",
            'MiNm.string' => 'The middle name must be a string.',
            'MiNm.max' => 'The middle name may not be greater than :max characters.',
            'LaNm.string' => 'The last name must be a string.',
            'LaNm.max' => 'The last name may not be greater than :max characters.',
            'Note.string' => 'The remarks must be a string.',
            'Note.max' => 'The remarks may not be greater than :max characters.',
            'Prfl_Pict.image' => 'The profile picture must be an image file.',
            'Prfl_Pict.mimes' => 'The profile picture must be a file of type: JPEG, JPG, PNG, or GIF.',
            'Prfl_Pict.max' => 'The profile picture may not be greater than 2 MB.',

            // Employment / Company
            'Comp_Name.string' => 'The company name must be a string.',
            'Comp_Name.max' => 'The company name must not exceed :max characters.',
            'Comp_Dsig.string' => 'The designation must be a string.',
            'Comp_Dsig.max' => 'The designation must not exceed :max characters.',
            'Comp_LdLi.numeric' => 'The company landline must contain only digits.',
            'Comp_LdLi.digits_between' => 'The company landline must be between :min and :max digits.',
            'Comp_Desp.string' => 'The company business description must be a string.',
            'Comp_Desp.max' => 'The company business description may not be greater than :max characters.',
            'Comp_Emai.email' => 'The company email must be a valid email address.',
            'Comp_Emai.max' => 'The company email may not be greater than :max characters.',
            'Comp_Web.url' => 'The company website must be a valid URL.',
            'Comp_Web.max' => 'The company website URL may not be greater than :max characters.',
            'Comp_Addr.string' => 'The company address must be a string.',
            'Comp_Addr.max' => 'The company address may not be greater than :max characters.',
            'Prfl_Name.string' => 'The profession/service name must be a string.',
            'Prfl_Name.max' => 'The profession/service name may not be greater than :max characters.',
            'Prfl_Addr.string' => 'The business address must be a string.',
            'Prfl_Addr.max' => 'The business address may not be greater than :max characters.',

            // Web Presence
            'Web.url' => 'The website URL must be a valid URL.',
            'Web.max' => 'The website URL may not be greater than :max characters.',

            'FcBk.url' => 'The Facebook URL must be a valid URL.',
            'FcBk.regex' => 'The Facebook URL must be a valid facebook.com address.',
            'FcBk.max' => 'The Facebook URL may not be greater than :max characters.',

            'Twtr.url' => 'The Twitter URL must be a valid URL.',
            'Twtr.regex' => 'The Twitter URL must be a valid twitter.com or x.com address.',
            'Twtr.max' => 'The Twitter URL may not be greater than :max characters.',

            'LnDn.url' => 'The LinkedIn URL must be a valid URL.',
            'LnDn.regex' => 'The LinkedIn URL must be a valid linkedin.com address.',
            'LnDn.max' => 'The LinkedIn URL may not be greater than :max characters.',

            'Intg.url' => 'The Instagram URL must be a valid URL.',
            'Intg.regex' => 'The Instagram URL must be a valid instagram.com address.',
            'Intg.max' => 'The Instagram URL may not be greater than :max characters.',

            'Yaho.url' => 'The Yahoo URL must be a valid URL.',
            'Yaho.regex' => 'The Yahoo URL must be a valid yahoo.com address.',
            'Yaho.max' => 'The Yahoo URL may not be greater than :max characters.',

            'Redt.url' => 'The Reddit URL must be a valid URL.',
            'Redt.regex' => 'The Reddit URL must be a valid reddit.com address.',
            'Redt.max' => 'The Reddit URL may not be greater than :max characters.',

            'Ytb.url' => 'The YouTube URL must be a valid URL.',
            'Ytb.regex' => 'The YouTube URL must be a valid youtube.com or youtu.be address.',
            'Ytb.max' => 'The YouTube URL may not be greater than :max characters.',

            // Phones
            'phones.array' => 'The mobile numbers field must be an array.',
            'phones.*.Phon_Numb.numeric' => 'Each mobile number must contain only digits.',
            'phones.*.Phon_Numb.distinct' => 'This mobile number has been submitted previously.',
            'phones.*.Cutr_Code.numeric' => 'Each country code must contain only digits.',
            'phones.*.Cutr_Code.digits_between' => 'Each country code may not be greater than :max digits.',

            // Landlines
            'landlines.array' => 'The landline numbers field must be an array.',
            'landlines.*.Land_Numb.numeric' => 'Each landline number must contain only digits.',
            'landlines.*.Land_Numb.digits_between' => 'Each landline number must be between :min and :max digits.',
            'landlines.*.Land_Numb.distinct' => 'This landline number has been submitted previously.',
            'landlines.*.Cutr_Code.numeric' => 'Each country code must contain only digits.',
            'landlines.*.Cutr_Code.digits_between' => 'Each country code may not be greater than :max digits.',

            // Emails
            'emails.array' => 'The emails field must be an array.',
            'emails.*.Emai_Addr.email' => 'Each email address must be a valid email format.',
            'emails.*.Emai_Addr.max' => 'Each email address may not be greater than :max characters.',
            'emails.*.Emai_Addr.distinct' => 'This email address has been submitted previously.',

            // Addresses
            'addresses.array' => 'The addresses field must be an array.',
            'addresses.max' => 'You can add a maximum of :max addresses.',
            'addresses.*.Addr.string' => 'The address line must be a string.',
            'addresses.*.Addr.max' => 'The address line may not be greater than :max characters.',
            'addresses.*.Loca.string' => 'The locality/street must be a string.',
            'addresses.*.Loca.max' => 'The locality/street may not be greater than :max characters.',
            'addresses.*.Lndm.string' => 'The landmark must be a string.',
            'addresses.*.Lndm.max' => 'The landmark may not be greater than :max characters.',
            'addresses.*.Admn_Addr_Type_Mast_UIN.exists' => 'The selected address type is invalid.',
            'addresses.*.Admn_Cutr_Mast_UIN.exists' => 'The selected country is invalid.',
            'addresses.*.Admn_Stat_Mast_UIN.exists' => 'The selected state is invalid.',
            'addresses.*.Admn_Dist_Mast_UIN.exists' => 'The selected district is invalid.',
            'addresses.*.Admn_PinCode_Mast_UIN.exists' => 'The selected pincode is invalid.',
            'addresses.*.Admn_Addr_Type_Mast_UIN.distinct' => 'Address type has been already selected previously',
            'addresses.*.Admn_Addr_Type_Mast_UIN.required_with' => 'The address type is required when address details are filled.',

            // References
            'references.array' => 'The references field must be an array.',
            'references.max' => 'You can add a maximum of :max references.',
            'references.*.Refa_Name.string' => 'Each name must be a string.',
            'references.*.Refa_Name.max' => 'Each name may not be greater than :max characters.',
            'references.*.Refa_Emai.email' => 'Each email must be a valid email address.',
            'references.*.Refa_Emai.max' => 'Each email may not be greater than :max characters.',
            'references.*.Refa_Phon.numeric' => 'Each mobile number must contain only digits.',
            'references.*.Refa_Phon.digits_between' => 'Each mobile number must be between :min and :max digits.',
            'references.*.Refa_Rsip.string' => 'Each relationship/designation must be a string.',
            'references.*.Refa_Rsip.max' => 'Each relationship/designation may not be greater than :max characters.',

            // Tags
            'selectedTags.array' => 'The selected tags must be an array.',
            'selectedTags.*.exists' => 'One or more selected tags are invalid.',

            // Bank Accounts
            'bankAccounts.array' => 'The bank accounts field must be an array.',
            'bankAccounts.max' => 'You can add a maximum of :max bank accounts.',
            'bankAccounts.*.Bank_Name_UIN.exists' => 'The selected bank is invalid.',
            'bankAccounts.*.Bank_Brnc_Name.string' => 'Branch name must be a string.',
            'bankAccounts.*.Bank_Brnc_Name.max' => 'Branch name may not exceed :max characters.',
            'bankAccounts.*.Bank_Name_UIN.required_with' => 'The bank name is required when an account number is entered.',
            'bankAccounts.*.Acnt_Numb.required_with' => 'The account number is required when a bank is selected.',
            'bankAccounts.*.Acnt_Numb.string' => 'Account number must be a string.',
            'bankAccounts.*.Acnt_Numb.max' => 'Account number may not exceed :max characters.',
            'bankAccounts.*.Acnt_Numb.distinct' => 'Account number has been entered previously',
            'bankAccounts.*.Acnt_Type.required_with' => 'Account Type is required when a bank is selected.',
            'bankAccounts.*.Acnt_Type.string' => 'Account type must be a string.',
            'bankAccounts.*.Acnt_Type.max' => 'Account type may not exceed :max characters.',
            'bankAccounts.*.IFSC_Code.string' => 'Indian Finance System code (IFSC) must be a string.',
            'bankAccounts.*.IFSC_Code.max' => 'Indian Finance System code (IFSC) may not exceed :max characters.',
            'bankAccounts.*.Swift_Code.string' => 'SWIFT code must be a string.',
            'bankAccounts.*.Swift_Code.max' => 'SWIFT code may not exceed :max characters.',
            'bankAccounts.*.Swift_Code.min' => 'SWIFT code must be at least :min characters.',
            'bankAccounts.*.newAttachments.*.mimes' => 'Document must be a PDF, JPG, PNG or WEBP file.',
            'bankAccounts.*.newAttachments.*.max' => 'Document size must not exceed :max KB.',

            // Documents
            'documents.array' => 'The documents field must be an array.',
            'documents.max' => 'You can add a maximum of :max documents.',
            'documents.*.selected_types.array' => 'Document types must be an array.',
            'documents.*.selected_types.*.exists' => 'One of the selected document types is invalid.',
            'documents.*.Docu_Name.string' => 'Document name must be a string.',
            'documents.*.Docu_Name.max' => 'Document name may not exceed :max characters.',
            'documents.*.Docu_Name.distinct' => 'This document name has been entered previously.',
            'documents.*.Docu_Name.required_with' => 'The document name is required when document type is selected.',
            'documents.*.Regn_Numb.string' => 'Registration number must be a string.',
            'documents.*.Regn_Numb.max' => 'Registration number may not exceed :max characters.',
            'documents.*.Regn_Numb.required_with' => 'The registration number is required when document type is selected.',
            'documents.*.Admn_Cutr_Mast_UIN.exists' => 'The selected country is invalid.',
            'documents.*.Admn_Cutr_Mast_UIN.required_with' => 'The country is required when document name is provided.',
            'documents.*.Auth_Issd.string' => 'Authority issued must be a string.',
            'documents.*.Auth_Issd.max' => 'Authority issued may not exceed :max characters.',
            'documents.*.Vald_From.date' => 'Valid from must be a valid date.',
            'documents.*.Vald_Upto.date' => 'Valid upto must be a valid date.',
            'documents.*.Vald_Upto.after' => 'Valid upto must be after valid from date.',
            'documents.*.Docu_Atch_Path.file' => 'Document attachment must be a file.',
            'documents.*.Docu_Atch_Path.mimes' => 'Document attachment must be PDF, JPG, PNG, or WEBP.',
            'documents.*.Docu_Atch_Path.max' => 'Document attachment may not be greater than 200 KB.',

            // Education
            'educations.array' => 'The educations field must be an array.',
            'educations.max' => 'You can add a maximum of :max educations.',
            'educations.*.Deg_Name.string' => 'Degree name must be a string.',
            'educations.*.Deg_Name.max' => 'Degree name may not exceed :max characters.',
            'educations.*.Inst_Name.string' => 'Institution name must be a string.',
            'educations.*.Inst_Name.max' => 'Institution name may not exceed :max characters.',
            'educations.*.Cmpt_Year.integer' => 'Completion year must be an integer.',
            'educations.*.Cmpt_Year.min' => 'Completion year must be at least :min.',
            'educations.*.Cmpt_Year.max' => 'Completion year may not be greater than :max.',
            'educations.*.Admn_Cutr_Mast_UIN.exists' => 'The selected country for education is invalid.',

            // Skills
            'skills.array' => 'The skills field must be an array.',
            'skills.max' => 'You can add a maximum of :max skills.',
            'skills.*.Skil_Type.string' => 'Skill type must be a string.',
            'skills.*.Skil_Type.max' => 'Skill type may not exceed :max characters.',
            'skills.*.Skil_Type_1.string' => 'Skill subtype must be a string.',
            'skills.*.Skil_Type_1.max' => 'Skill subtype may not exceed :max characters.',
            'skills.*.Skil_Name.string' => 'Skill name must be a string.',
            'skills.*.Skil_Name.max' => 'Skill name may not exceed :max characters.',
            'skills.*.Profc_Lvl.integer' => 'Proficiency level must be an integer.',
            'skills.*.Profc_Lvl.min' => 'Proficiency level must be at least :min.',
            'skills.*.Profc_Lvl.max' => 'Proficiency level may not be greater than :max.',

            // Work Experience
            'workExperiences.array' => 'The work experiences field must be an array.',
            'workExperiences.max' => 'You can add a maximum of :max work experiences.',
            'workExperiences.*.Orga_Name.string' => 'Organization name must be a string.',
            'workExperiences.*.Orga_Name.max' => 'Organization name may not exceed :max characters.',
            'workExperiences.*.Dsgn.string' => 'Designation must be a string.',
            'workExperiences.*.Dsgn.max' => 'Designation may not exceed :max characters.',
            'workExperiences.*.Prd_From.date' => 'Period from must be a valid date.',
            'workExperiences.*.Prd_From.required_with' => 'The From Date is required',
            'workExperiences.*.Prd_From.before' => 'The From Date must be before today.',
            'workExperiences.*.Prd_To.date' => 'Period to must be a valid date.',
            'workExperiences.*.Prd_To.required_with' => 'The To Date is required',
            'workExperiences.*.Prd_To.before_or_equal' => "To Date must be before today's Date.",
            'workExperiences.*.Prd_To.after' => 'The To Date must be after the From Date.',
            'workExperiences.*.Orga_Type.string' => 'Organization type must be a string.',
            'workExperiences.*.Orga_Type.max' => 'Organization type may not exceed :max characters.',
            'workExperiences.*.Job_Desp.string' => 'Job description must be a string.',
            'workExperiences.*.Job_Desp.max' => 'Job description may not exceed :max characters.',
            'workExperiences.*.Work_Type.in' => 'The selected work type is invalid.',
            'workExperiences.*.Admn_Cutr_Mast_UIN.exists' => 'The selected country for work experience is invalid.',
        ];

        return $commonMessages;
    }

    // ========================================================================
    // HELPER FUNCTIONS
    // ========================================================================

    public function validateUniqueUrl($attribute, $value, $fail)
    {
        if (empty($value)) {
            return;
        }
        $valNorm = rtrim(strtolower($value), '/');
        $fields = ['Web', 'LnDn', 'Twtr', 'FcBk', 'Intg', 'Yaho', 'Redt', 'Ytb'];

        foreach ($fields as $field) {
            $otherVal = $this->{$field} ?? null;
            if ($otherVal && rtrim(strtolower($otherVal), '/') === $valNorm) {
                // Ensure we aren't comparing the field to itself (if called from updated hook context)
                if ($attribute && Str::contains($attribute, $field)) {
                    continue;
                }

                if ($value !== $this->$field) {
                    $fail('This URL is duplicated in ' . ucfirst($field));

                    return;
                }
            }
        }
    }

    private function insertMainContact($userUIN, $orgaUIN): int
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
            'Admn_Orga_Mast_UIN' => $orgaUIN,
            'Is_Actv' => self::STATUS_ACTIVE,
            'Is_Vf' => self::STATUS_UNVERIFIED,
            'Prty' => 'I', // Assuming by link usually means Individual for now, adjust if needed
            'CrOn' => now(),
            'MoOn' => now(),
            'CrBy' => $userUIN,
        ];

        DB::table('admn_user_mast')->insert($insertData);

        Log::info("Contact created via link with UIN: {$contactId}");

        return $contactId;
    }

    private function processProfilePicture(): ?string
    {
        if (!$this->Prfl_Pict) {
            return null;
        }

        try {
            Log::info('Processing profile picture upload...');

            $extension = $this->Prfl_Pict->getClientOriginalExtension();
            $filename = $this->generateAttachmentFileName($extension);
            $filePath = $this->Prfl_Pict->storeAs('Attachment/Profile', $filename, 'public');

            if (!$filePath) {
                throw new \Exception('File storage returned false/null');
            }

            Log::info('File stored as: ' . $filePath);

            try {
                $fullPath = Storage::disk('public')->path($filePath);

                if (file_exists($fullPath) && class_exists('Intervention\Image\Facades\Image')) {
                    Image::make($fullPath)->save();
                }
            } catch (\Exception $e) {
                Log::warning('Image processing failed (but file upload succeeded): ' . $e->getMessage());
            }

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Error storing profile picture: ' . $e->getMessage());

            return null;
        }
    }

    private function insertEmails(int $contactId, int $userUIN): void
    {
        $validEmails = collect($this->emails)->filter(
            fn($email) => !empty(trim($email['Emai_Addr'] ?? ''))
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
            fn($phone) => !empty(trim($phone['Phon_Numb'] ?? ''))
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
            fn($landline) => !empty(trim($landline['Land_Numb'] ?? ''))
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
            fn($address) => !empty(trim($address['Addr'] ?? '')) || !empty($address['Admn_PinCode_Mast_UIN'])
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
            fn($reference) => !empty(trim($reference['Refa_Name'] ?? '')) || !empty(trim($reference['Refa_Emai'] ?? '')) || !empty(trim($reference['Refa_Phon'] ?? ''))
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

    private function insertBankAccounts($contactId, $userUIN, $orgaUIN)
    {
        $validBanks = collect($this->bankAccounts)->filter(
            fn($bank) => !empty($bank['Bank_Name_UIN']) && !empty(trim($bank['Acnt_Numb'] ?? ''))
        );

        if ($validBanks->isEmpty()) {
            return;
        }

        $bankUINs = $this->generateBatchUINs('admn_user_bank_mast', 'Admn_User_Bank_Mast_UIN', $validBanks->count());

        $totalAttachments = $validBanks->sum(fn($bank) => count($bank['newAttachments'] ?? []));
        $attachmentUINs = $totalAttachments > 0 ? $this->generateBatchUINs('admn_bank_attachments', 'Admn_Bank_Attachment_UIN', $totalAttachments) : [];

        $attachmentUinIndex = 0;

        foreach ($validBanks->values() as $index => $bank) {
            $currentBankUIN = $bankUINs[$index];

            DB::table('admn_user_bank_mast')->insert([
                'Admn_User_Bank_Mast_UIN' => $currentBankUIN,
                'Admn_User_Mast_UIN' => $contactId,
                'Admn_Orga_Mast_UIN' => $orgaUIN,
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

            if (!empty($bank['newAttachments'])) {
                foreach ($bank['newAttachments'] as $file) {
                    $this->storeBankAttachment(
                        $currentBankUIN,
                        $file,
                        $attachmentUINs[$attachmentUinIndex++],
                        $orgaUIN,
                        $contactId,
                        $userUIN
                    );
                }
            }
        }
    }

    private function storeBankAttachment($bankUIN, $file, $attachmentUIN, $orgaUIN, $contactId, $userUIN)
    {
        try {
            // Validation already handled by trait rules
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
            Log::error('Error storing bank attachment: ' . $e->getMessage());
            session()->flash('error', 'Error uploading bank attachment: ' . $e->getMessage());
        }
    }

    private function insertDocuments($contactId, $userUIN, $orgaUIN)
    {
        $validDocuments = collect($this->documents)->filter(
            fn($doc) => !empty($doc['selected_types']) && !empty(trim($doc['Regn_Numb'] ?? ''))
        );

        if ($validDocuments->isEmpty()) {
            return;
        }

        $totalDocRecords = $validDocuments->sum(
            fn($doc) => count(is_array($doc['selected_types'] ?? []) ? $doc['selected_types'] : [])
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
                    'Admn_Orga_Mast_UIN' => $orgaUIN,
                    'Admn_Cutr_Mast_UIN' => $doc['Admn_Cutr_Mast_UIN'] ?: null,
                    'Admn_Docu_Type_Mast_UIN' => $typeId,
                    'Docu_Name' => trim($doc['Docu_Name']) ?: null,
                    'Auth_Issd' => trim($doc['Auth_Issd']) ?: null,
                    'Regn_Numb' => trim($doc['Regn_Numb']),
                    'Prmy' => $doc['Prmy'] ?? false,

                    // ✅ UPDATED DATE LOGIC
                    'Vald_From' => $this->formatDateForDatabase($doc['Vald_From']),
                    'Vald_Upto' => $this->formatDateForDatabase($doc['Vald_Upto']),

                    'Docu_Atch_Path' => $docAttachmentPath,
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

    private function storeDocumentFile($doc)
    {
        try {
            if (empty($doc['Docu_Atch_Path'])) {
                return null;
            }

            if (is_object($doc['Docu_Atch_Path'])) {
                $file = $doc['Docu_Atch_Path'];

                // Validation already handled by trait rules
                $extension = $file->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $storagePath = $file->storeAs('Attachment/Document', $filename, 'public');

                Log::info("Document stored successfully: {$storagePath}");

                return $storagePath;
            }

            return $doc['Docu_Atch_Path'];
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            session()->flash('error', 'Error uploading document: ' . $e->getMessage());

            return null;
        }
    }

    public function clearDocumentFile($index)
    {
        if (isset($this->documents[$index])) {
            $this->documents[$index]['Docu_Atch_Path'] = null;
            $this->resetErrorBag("documents.$index.Docu_Atch_Path");
        }
    }

    private function insertEducations(int $contactId, int $userUIN): void
    {
        $validItems = collect($this->educations)->filter(
            fn($item) => !empty(trim($item['Deg_Name'] ?? '')) && !empty(trim($item['Inst_Name'] ?? ''))
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

            return !empty(trim($skill['Skil_Name'] ?? ''));
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
            fn($item) => !empty(trim($item['Orga_Name'] ?? '')) && !empty(trim($item['Dsgn'] ?? ''))
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

        if (!$byLinkTag) {
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

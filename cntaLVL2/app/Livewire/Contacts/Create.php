<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\FormatsDates;
use App\Livewire\Traits\GeneratesUINs;
use App\Livewire\Traits\HasAddressCascade;
use App\Livewire\Traits\HasMaxConstants;
use App\Livewire\Traits\HasSkillData;
use App\Livewire\Traits\HasWorkTypes;
use App\Livewire\Traits\LoadsReferenceData;
use App\Livewire\Traits\WithComments;
use App\Livewire\Traits\WithContactValidation;
use App\Livewire\Traits\WithDocumentNames;
use App\Models\Admn_Bank_Name;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Docu_Type_Mast;
use App\Models\Admn_Grup_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Tag_Mast;
use App\Models\AdmnUserEducMast;
use App\Models\AdmnUserSkilMast;
use App\Models\AdmnUserWorkMast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use FormatsDates;
    use GeneratesUINs;
    use HasAddressCascade;
    use HasMaxConstants, HasSkillData, HasWorkTypes, WithComments,WithContactValidation, WithDocumentNames, WithFileUploads;
    use LoadsReferenceData;

    // ============================================
    // BASIC PROPERTIES
    // ============================================
    public $contactId;

    public $contact;

    public $MoOn;

    // Note Sidebar
    public bool $showCreateModal = false;

    public string $newNoteContent = '';

    public ?string $activeCommentTab = 'PAN';

    public ?int $verticalId = 5;

    // ============================================
    // PERSONAL DETAILS PROPERTIES
    // ============================================
    public $Prfx_UIN = '';

    public $FaNm = '';

    public $MiNm = '';

    public $LaNm = '';

    public $Gend = '';

    public $Blood_Grp = '';

    public $Brth_Dt;

    public $Anvy_Dt;

    public $Deth_Dt;

    public $Prfl_Pict;

    public $existing_avatar;

    // ============================================
    // EMPLOYMENT PROPERTIES
    // ============================================
    public $Note = '';

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

    // ============================================
    // WEB PRESENCE PROPERTIES
    // ============================================
    public $Web = '';

    public $FcBk = '';

    public $Twtr = '';

    public $LnDn = '';

    public $Intg = '';

    public $Yaho = '';

    public $Redt = '';

    public $Ytb = '';
    // ============================================
    // PARTY TYPE
    // ============================================

    public $Prty = 'I';

    public array $partyOptions = [
        'I' => 'Individual/Personal',
        'B' => 'Business/Organization',
    ];

    // ============================================
    // COLLECTION PROPERTIES
    // ============================================

    public $skillTypes = [];

    public $skillSubtypes = [];

    public $skills = [];

    public array $emails = [];

    public array $phones = [];

    public array $landlines = [];

    public array $educations = [];

    public array $workTypes = [];

    public array $workExperiences = [];

    public array $contactNotes = [];

    public ?string $newNote = null;

    public array $addresses = [];

    public array $references = [];

    public array $bankAccounts = [];

    public array $documents = [];

    public array $selectedTags = [];

    public array $availableGroups = [];

    // ============================================
    // REFERENCE DATA PROPERTIES
    // ============================================
    public $allTags = [];

    public $allCountries = [];

    public $allPrefixes = [];

    public $addressTypes = [];

    public $BaddressTypes = [];

    public $bankOptions = [];

    public $allDocumentTypes = [];

    public ?int $assignedGroupId = null;

    // ============================================
    // MOUNT
    // ============================================

    public function mount()
    {
        try {
            $this->Prty = 'I';
            $this->loadCommonReferenceData();
            $this->loadAvailableGroups();
            $this->initializeCollections();
            $this->initializeWithDocumentNames();
            $this->skillTypes = $this->getSkillTypes();
            $this->skillSubtypes = $this->getSkillSubtypes();
            // load work-type options from trait
            $this->workTypes = $this->getWorkTypes();
            $this->noteCreatedBy = session('authenticated_user_uin');
            $this->noteCreatedAt = now();
            if (empty($this->skills)) {
                $this->skills = [];
            }
        } catch (\Exception $e) {
            Log::error('Mount Error: '.$e->getMessage());
            session()->flash('error', 'Error loading form data: '.$e->getMessage());
        }
    }

    // ============================================
    // LOAD AVAILABLE GROUPS
    // ============================================

    private function loadAvailableGroups()
    {
        try {
            $orgUIN = session('selected_Orga_UIN');

            if (! $orgUIN) {
                Log::warning('No organization selected when loading available groups');
                $this->availableGroups = [];

                return;
            }

            $this->availableGroups = Admn_Grup_Mast::where('Admn_Orga_Mast_UIN', $orgUIN)
                ->where('Is_Actv', self::STATUS_ACTIVE)
                ->orderBy('Name')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading available groups: '.$e->getMessage());
            $this->availableGroups = [];
        }
    }

  

    // ============================================
    // INITIALIZE COLLECTIONS
    // ============================================
    private function initializeCollections()
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

    // ============================================
    // EMAIL METHODS
    // ============================================
    public function addEmail()
    {
        if (count($this->emails) < self::MAX_EMAILS) {
            $this->emails[] = [
                'Emai_Addr' => '',
                'Emai_Type' => 'Self Generated',
                'Is_Prmy' => empty($this->emails),
            ];
        }
    }

    public function removeEmail($index)
    {
        $this->removeItem('emails', $index);
    }

    public function setPrimaryEmail($index)
    {
        $this->setPrimaryItem('emails', $index);
    }

    // ============================================
    // PHONE METHODS
    // ============================================
    public function addPhone()
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

    public function removePhone($index)
    {
        $this->removeItem('phones', $index);
    }

    public function setPrimaryPhone($index)
    {
        $this->setPrimaryItem('phones', $index);
    }

    // ============================================
    // LANDLINE METHODS
    // ============================================
    public function addLandline()
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

    public function removeLandline($index)
    {
        $this->removeItem('landlines', $index);
    }

    public function setPrimaryLandline($index)
    {
        $this->setPrimaryItem('landlines', $index);
    }

    // ============================================
    // ADDRESS METHODS
    // ============================================
    public function addAddress()
    {
        if (count($this->addresses) >= self::MAX_ADDRESSES) {
            return;
        }

        $india = $this
            ->getAllCountriesCollectionProperty()
            ->firstWhere('Admn_Cutr_Mast_UIN', self::COUNTRY_INDIA_UIN);

        $defaultId = $india ? $india->Admn_Cutr_Mast_UIN : null;

        $this->addresses[] = $this->hydrateAddressFields([
            'Addr' => '',
            'Loca' => '',
            'Lndm' => '',
            'Admn_Addr_Type_Mast_UIN' => null,
            'Is_Prmy' => empty($this->addresses),
            'Admn_Cutr_Mast_UIN' => $defaultId,
            'Admn_Stat_Mast_UIN' => null,
            'Admn_Dist_Mast_UIN' => null,
            'Admn_PinCode_Mast_UIN' => null,
        ]);
    }

    public function removeAddress($index)
    {
        $this->resetErrorBag("addresses.$index");
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);

        if (collect($this->addresses)->where('Is_Prmy', true)->isEmpty() && ! empty($this->addresses)) {
            $this->addresses[0]['Is_Prmy'] = true;
        }
    }

    public function setPrimaryAddress($index)
    {
        $this->setPrimaryItem('addresses', $index);
        $this->syncPhoneCodeWithPrimaryAddress();
    }

    private function syncPhoneCodeWithPrimaryAddress()
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

    // ============================================
    // REFERENCE METHODS
    // ============================================
    public function addReference()
    {
        if (count($this->references) < self::MAX_REFERENCES) {
            // Check if this is the first reference, if so, make it primary
            $isFirst = empty($this->references);

            $this->references[] = [
                'Refa_Name' => '',
                'Refa_Phon' => '',
                'Refa_Emai' => '',
                'Refa_Rsip' => '',
                'Is_Prmy' => $isFirst, // Set true if first
            ];
        }
    }

    public function removeReference($index)
    {
        // Remove item
        unset($this->references[$index]);
        $this->references = array_values($this->references);

        // If the primary was removed, make the first available one primary
        if (collect($this->references)->where('Is_Prmy', true)->isEmpty() && ! empty($this->references)) {
            $this->references[0]['Is_Prmy'] = true;
        }
    }

    // NEW METHOD: Set Primary Reference
    public function setPrimaryReference($selectedIndex)
    {
        foreach ($this->references as $index => &$ref) {
            $ref['Is_Prmy'] = ($index === $selectedIndex);
        }
    }

    // ============================================
    // BANK ACCOUNT METHODS
    // ============================================
    public function addBank()
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

    public function removeBank($index)
    {
        $this->removeItem('bankAccounts', $index);
    }

    public function setPrimaryBank(int $index)
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

    public function removeNewAttachment($bankIndex, $attachmentIndex)
    {
        if (isset($this->bankAccounts[$bankIndex]['newAttachments'][$attachmentIndex])) {
            array_splice($this->bankAccounts[$bankIndex]['newAttachments'], $attachmentIndex, 1);
        }
    }

    public function validateBankAttachment($index)
    {
        if (! isset($this->bankAccounts[$index])) {
            return;
        }

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
                $this->bankAccounts[$index]['temp_upload'] = [];

                return;
            }

            if ($file->getSize() > self::MAX_BANK_FILE_SIZE) {
                $fileSizeKB = round($file->getSize() / 1024, 1);
                $this->addError(
                    "bankAccounts.{$index}.newAttachments.*",
                    "Bank attachment '{$file->getClientOriginalName()}' exceeds the 100KB limit. File size: {$fileSizeKB}KB."
                );
                $this->bankAccounts[$index]['temp_upload'] = [];

                return;
            }
        }
    }

    // ============================================
    // DOCUMENT METHODS
    // ============================================
    public function addDocument()
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

    public function setPrimaryDocument($index)
    {
        foreach ($this->documents as $key => &$doc) {
            $doc['Prmy'] = ($key === $index);
        }
    }

    public function removeDocument($index)
    {
        if (isset($this->documents[$index]['Docu_Atch_Path'])) {
            $path = $this->documents[$index]['Docu_Atch_Path'];
            if ($path && ! is_object($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
    }

    public function clearDocumentFile($index)
    {
        if (isset($this->documents[$index])) {
            $this->documents[$index]['Docu_Atch_Path'] = null;
            $this->resetErrorBag("documents.$index.Docu_Atch_Path");
        }
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

        if (! ($file instanceof \Illuminate\Http\UploadedFile)) {
            return;
        }

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

        if (! in_array($file->getMimeType(), $allowedMimes)) {
            $this->addError(
                "documents.{$index}.Docu_Atch_Path",
                'Document attachment must be PDF, JPG, PNG, or WEBP.'
            );
            $this->documents[$index]['Docu_Atch_Path'] = null;

            return;
        }

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

    public function selectDocumentType($docIndex, $typeId)
    {
        if (! in_array($typeId, $this->documents[$docIndex]['selected_types'] ?? [])) {
            $this->documents[$docIndex]['selected_types'][] = $typeId;
        }
        $this->documents[$docIndex]['is_dropdown_open'] = false;
    }

    public function removeDocumentType($docIndex, $typeId)
    {
        $this->documents[$docIndex]['selected_types'] = array_values(
            array_filter(
                $this->documents[$docIndex]['selected_types'] ?? [],
                fn ($id) => $id != $typeId
            )
        );
    }

    public function removeDocumentAttachment($index)
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

    // ============================================
    // EDUCATION METHODS
    // ============================================
    public function addEducation()
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

    public function removeEducation($index)
    {
        if (isset($this->educations[$index])) {
            unset($this->educations[$index]);
            $this->educations = array_values($this->educations);
        }
    }

    // ============================================
    // SKILLS METHODS
    // ============================================
    public function addSkill()
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

    public function removeSkill($index)
    {
        if (isset($this->skills[$index])) {
            unset($this->skills[$index]);
            $this->skills = array_values($this->skills);
        }
    }

    // ============================================
    // WORK EXPERIENCE METHODS
    // ============================================
    public function addWorkExperience()
    {
        if (count($this->workExperiences) < self::MAX_WORK_EXPERIENCES) {
            $this->workExperiences[] = [
                'Orga_Name' => '',
                'Dsgn' => '',
                'Prd_From' => '',
                'Prd_To' => '',
                'Orga_Type' => '',
                'Job_Desp' => '',
                'Work_Type' => 'Full',
                'Admn_Cutr_Mast_UIN' => self::COUNTRY_INDIA_UIN,
            ];
        }
    }

    public function removeWorkExperience($index)
    {
        if (isset($this->workExperiences[$index])) {
            unset($this->workExperiences[$index]);
            $this->workExperiences = array_values($this->workExperiences);
        }
    }

    // ============================================
    // NOTES/SIDEBAR METHODS
    // ============================================

    public function openCreateNoteModal(): void
    {
        $this->newNoteContent = '';
        $this->activeCommentTab = 'PAN';
        $this->showCreateModal = true;
    }

    public function closeCreateNoteModal(): void
    {
        $this->showCreateModal = false;
        // $this->newNoteContent = '';
    }

    public function addCommentToNote(string $comment): void
    {
        $trimmedComment = trim($comment);
        if ($trimmedComment === '') {
            return;
        }

        $this->newNoteContent = trim($this->newNoteContent);
        $this->newNoteContent = $this->newNoteContent
            ? "{$this->newNoteContent}\n{$trimmedComment}"
            : $trimmedComment;
    }

    // ============================================
    // PROFILE PICTURE METHODS
    // ============================================
    public function removeProfilePicture()
    {
        $this->Prfl_Pict = null;
        $this->resetErrorBag('Prfl_Pict');
    }

    // ============================================
    // UTILITY METHODS
    // ============================================
    public function updated($propertyName)
    {
        // 1. Define properties to skip validation on update
        $skipValidation = [
            'Prty',
            'addressTypes',
            'allTags',
            'allCountries',
            'allPrefixes',
            'bankOptions',
            'allDocumentTypes',
            'contactId',
            'MoOn',
            'Empl_Type',
            'showNoteSidebar',
        ];

        if (Str::contains($propertyName, $skipValidation)) {
            return;
        }

        if (in_array($propertyName, ['FaNm', 'MiNm', 'LaNm'])) {
            $this->$propertyName = preg_replace('/[^a-zA-Z ]/', '', $this->$propertyName);
        }

        // 2. Handle File Uploads
        if (preg_match('/bankAccounts\.(\d+)\.temp_upload/', $propertyName, $matches)) {
            $this->handleBankUpload($matches[1]);

            return;
        }

        if (preg_match('/documents\.(\d+)\.Docu_Atch_Path/', $propertyName, $matches)) {
            $this->validateDocumentFile($matches[1]);

            return;
        }

        // 3. Skip flags or search fields
        if (Str::contains($propertyName, ['Has_WtAp', 'Has_Telg', 'Is_Prmy'])) {
            return;
        }

        if (Str::endsWith($propertyName, 'pincodeSearch')) {
            return;
        }

        // 4. CROSS-VALIDATION FOR BANK ACCOUNTS
        if (preg_match('/bankAccounts\.(\d+)\.(Bank_Name_UIN|Acnt_Numb)/', $propertyName, $matches)) {
            $index = $matches[1];

            $this->validateOnly($propertyName, $this->rules(), $this->messages());

            if (str_contains($propertyName, 'Bank_Name_UIN')) {
                $this->validateOnly("bankAccounts.{$index}.Acnt_Numb", $this->rules(), $this->messages());
            } elseif (str_contains($propertyName, 'Acnt_Numb')) {
                $this->validateOnly("bankAccounts.{$index}.Bank_Name_UIN", $this->rules(), $this->messages());
            }

            return;
        }

        if (preg_match('/documents\.(\d+)\.(Vald_From|Vald_Upto)/', $propertyName, $matches)) {
            $index = $matches[1];

            // 1. Validate the field you just changed
            $this->validateOnly($propertyName, $this->rules(), $this->messages());

            // 2. Force validation on the counterpart field
            // If 'From' changed, re-check 'Upto'. If 'Upto' changed, re-check 'From'.
            if (str_contains($propertyName, 'Vald_From')) {
                $this->validateOnly("documents.{$index}.Vald_Upto", $this->rules(), $this->messages());
            } elseif (str_contains($propertyName, 'Vald_Upto')) {
                $this->validateOnly("documents.{$index}.Vald_From", $this->rules(), $this->messages());
            }

            return;
        }

        // 5. Default Validation for all other fields
        // ✅ FIX: Remove try-catch. Let ValidationException bubble up to Livewire.
        $this->validateOnly($propertyName, $this->rules(), $this->messages());
    }

    private function removeItem($arrayName, $index)
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

    private function setPrimaryItem($arrayName, $index, $field = 'Is_Prmy')
    {
        foreach ($this->$arrayName as $idx => &$item) {
            $item[$field] = ($idx == $index);
        }
    }

    private function generateAttachmentFileName($extension = ''): string
    {
        $orgUIN = session('selected_Orga_UIN');
        $timestamp = now()->format('Ymd_His');
        $filename = "{$orgUIN}_{$timestamp}";

        if (! empty($extension)) {
            $filename .= ".{$extension}";
        }

        return $filename;
    }

    // ============================================
    // SAVE METHOD
    // ============================================
    public function save()
    {
        try {
            $this->validate();
            $this->validateCustomLogic();

            $userUIN = session('authenticated_user_uin');
            $orgaUIN = session('selected_Orga_UIN');

            if (! $userUIN) {
                throw new \Exception('User not authenticated.');
            }

            if (! $orgaUIN) {
                throw new \Exception('Organization not selected. Please select an organization first.');
            }

            DB::transaction(function () use ($userUIN, $orgaUIN) {
                $contactId = $this->insertMainContact($userUIN, $orgaUIN);
                $this->insertEmails($contactId, $userUIN);
                $this->insertPhones($contactId, $userUIN);
                $this->insertLandlines($contactId, $userUIN);
                $this->insertAddresses($contactId, $userUIN);
                $this->insertReferences($contactId, $userUIN);
                $this->insertTags($contactId, $userUIN);
                $this->insertBankAccounts($contactId, $userUIN, $orgaUIN);
                $this->insertDocuments($contactId, $userUIN, $orgaUIN);
                $this->insertEducations($contactId, $userUIN);
                $this->insertSkills($contactId, $userUIN);
                $this->insertWorkExperiences($contactId, $userUIN);
                $this->insertContactNotes($contactId, $userUIN);
                $this->attachPendingNoteIfExists($contactId, $userUIN, $orgaUIN);
            });

            session()->flash('success', 'Contact has been created successfully.');

            return redirect()->route('contacts.index');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            $this->dispatch('scroll-to-errors');
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error saving contact: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());
            session()->flash('error', 'An error occurred while saving the contact: '.$e->getMessage());
            $this->dispatch('scroll-to-errors');
        }
    }

    // ============================================
    // INSERT METHODS
    // ============================================
    private function insertMainContact($userUIN, $orgaUIN)
    {
        $contactId = $this->generateUniqueUIN('admn_user_mast', 'Admn_User_Mast_UIN');
        $avatarPath = null;

        if ($this->Prfl_Pict) {
            try {
                if ($this->Prfl_Pict->getSize() > self::MAX_PROFILE_FILE_SIZE) {
                    session()->flash('error', 'Profile picture size must not exceed 2 MB.');

                    return;
                }

                $extension = $this->Prfl_Pict->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $filePath = $this->Prfl_Pict->storeAs('Attachment/Profile', $filename, 'public');
                $fullPath = Storage::disk('public')->path($filePath);

                if (file_exists($fullPath) && class_exists('Intervention\Image\ImageManagerStatic')) {
                    Image::make($fullPath)->fit(512, 512)->save();
                }

                $avatarPath = $filePath;
            } catch (\Exception $e) {
                Log::error('Error processing profile picture: '.$e->getMessage());
                session()->flash('error', 'Error uploading profile picture: '.$e->getMessage());
            }
        }

        $insertData = [
            'Admn_User_Mast_UIN' => $contactId,
            'Prfx_UIN' => $this->Prfx_UIN ?: null,
            'FaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->FaNm)),
            'MiNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->MiNm)) ?: null,
            'LaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->LaNm)) ?: null,
            'Gend' => $this->Gend ?: null,
            'Blood_Grp' => $this->Blood_Grp ?: null,

            // ✅ UPDATED DATE LOGIC
            'Brth_Dt' => $this->formatDateForDatabase($this->Brth_Dt),
            'Anvy_Dt' => $this->formatDateForDatabase($this->Anvy_Dt),
            'Deth_Dt' => $this->formatDateForDatabase($this->Deth_Dt),

            'Prfl_Pict' => $avatarPath,
            'Note' => trim($this->Note) ?: null,
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
            'Admn_Grup_Mast_UIN' => $this->assignedGroupId ?: null,
            'Is_Actv' => self::STATUS_ACTIVE,
            'Is_Vf' => self::STATUS_VERIFIED,
            'Prty' => trim($this->Prty) ?: null,
            'CrOn' => now(),
            'MoOn' => now(),
            'CrBy' => $userUIN,
        ];

        DB::table('admn_user_mast')->insert($insertData);

        return $contactId;
    }

    // Save a note temporarily

    public function saveNewNote(): void
    {
        $note = trim($this->newNoteContent ?? '');
        if ($note === '') {
            $this->dispatch('toast-error', message: 'Note cannot be empty!');

            return;
        }

        session()->put('pending_contact_note', $note);
        $this->dispatch('toast-success', message: 'Note saved. It will attach once the contact is created.');
        $this->newNoteContent = '';
        $this->closeCreateNoteModal();
    }

    private function insertEmails($contactId, $userUIN)
    {
        $validEmails = collect($this->emails)->filter(
            fn ($email) => ! empty(trim(strtolower($email['Emai_Addr']) ?? ''))
        );

        if ($validEmails->isEmpty()) {
            return;
        }

        $emailUINs = $this->generateBatchUINs('admn_cnta_emai_mast', 'Admn_Cnta_Emai_Mast_UIN', $validEmails->count());

        foreach ($validEmails as $index => $email) {
            DB::table('admn_cnta_emai_mast')->insert([
                'Admn_Cnta_Emai_Mast_UIN' => $emailUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Emai_Addr' => strtolower(trim($email['Emai_Addr'])),
                'Emai_Type' => $email['Emai_Type'] ?? 'Self Generated',
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

    private function insertPhones($contactId, $userUIN)
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

    private function insertLandlines($contactId, $userUIN)
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

    private function insertAddresses($contactId, $userUIN)
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

    private function insertReferences($contactId, $userUIN)
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
                'Is_Prmy' => $reference['Is_Prmy'] ?? false, // ADDED THIS LINE
                'CrOn' => now(),
                'MoOn' => now(),
                'CrBy' => $userUIN,
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertTags($contactId, $userUIN)
    {
        $validTags = collect($this->selectedTags)->filter(fn ($tagId) => ! empty($tagId));

        if ($validTags->isEmpty()) {
            return;
        }

        $tagUINs = $this->generateBatchUINs('admn_cnta_tag_mast', 'Admn_Cnta_Tag_Mast_UIN', $validTags->count());

        foreach ($validTags as $index => $tagId) {
            DB::table('admn_cnta_tag_mast')->insert([
                'Admn_Cnta_Tag_Mast_UIN' => $tagUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Admn_Tag_Mast_UIN' => $tagId,
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

            if (! empty($bank['newAttachments'])) {
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

    private function insertDocuments($contactId, $userUIN, $orgaUIN)
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

                if ($file->getSize() > self::MAX_DOCUMENT_FILE_SIZE) {
                    Log::warning("Document exceeds 100KB: {$file->getClientOriginalName()}");
                    session()->flash('error', "Document '{$file->getClientOriginalName()}' exceeds 100KB limit.");

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

                Log::info("Document stored successfully: {$storagePath}");

                return $storagePath;
            }

            return $doc['Docu_Atch_Path'];
        } catch (\Exception $e) {
            Log::error('Error uploading document: '.$e->getMessage());
            session()->flash('error', 'Error uploading document: '.$e->getMessage());

            return null;
        }
    }

    private function insertEducations($contactId, $userUIN)
    {
        $validEducations = collect($this->educations)->filter(
            fn ($edu) => ! empty(trim($edu['Deg_Name'] ?? '')) && ! empty(trim($edu['Inst_Name'] ?? ''))
        );

        if ($validEducations->isEmpty()) {
            return;
        }

        $eduUINs = $this->generateBatchUINs('admn_user_educ_mast', 'Admn_User_Educ_Mast_UIN', $validEducations->count());

        foreach ($validEducations->values() as $index => $edu) {
            AdmnUserEducMast::create([
                'Admn_User_Educ_Mast_UIN' => $eduUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Deg_Name' => trim($edu['Deg_Name']),
                'Inst_Name' => trim($edu['Inst_Name']),
                'Cmpt_Year' => (int) $edu['Cmpt_Year'],
                'Admn_Cutr_Mast_UIN' => $edu['Admn_Cutr_Mast_UIN'] ?: null,
                'CrBy' => $userUIN,
                'CrOn' => now(),
                'MoOn' => now(),
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertSkills($contactId, $userUIN)
    {
        $validSkills = collect($this->skills)->filter(function ($skill) {
            if (empty($skill['Skil_Type']) || empty($skill['Skil_Type_1'])) {
                return false;
            }
            if ($skill['Skil_Type_1'] !== 'Other') {
                return true; // Skil_Type_1 is the name
            }

            return ! empty(trim($skill['Skil_Name'] ?? '')); // Skil_Name must be filled for 'Other'
        });

        if ($validSkills->isEmpty()) {
            return;
        }

        $skilUINs = $this->generateBatchUINs('admn_user_skil_mast', 'Admn_User_Skil_Mast_UIN', $validSkills->count());

        foreach ($validSkills->values() as $index => $skill) {
            $skillName = ($skill['Skil_Type_1'] !== 'Other') ? $skill['Skil_Type_1'] : trim($skill['Skil_Name']);

            AdmnUserSkilMast::create([
                'Admn_User_Skil_Mast_UIN' => $skilUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Skil_Type' => trim($skill['Skil_Type']) ?: null,
                'Skil_Type_1' => trim($skill['Skil_Type_1']) ?: null,
                'Skil_Name' => $skillName,
                'Profc_Lvl' => $skill['Profc_Lvl'] ?: null,
                'CrBy' => $userUIN,
                'CrOn' => now(),
                'MoOn' => now(),
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function insertWorkExperiences($contactId, $userUIN)
    {
        $validExperiences = collect($this->workExperiences)->filter(
            fn ($work) => ! empty(trim($work['Orga_Name'] ?? '')) && ! empty($work['Prd_From'] ?? '')
        );

        if ($validExperiences->isEmpty()) {
            return;
        }

        $workUINs = $this->generateBatchUINs('admn_user_work_mast', 'Admn_User_Work_Mast_UIN', $validExperiences->count());

        foreach ($validExperiences->values() as $index => $work) {
            AdmnUserWorkMast::create([
                'Admn_User_Work_Mast_UIN' => $workUINs[$index],
                'Admn_User_Mast_UIN' => $contactId,
                'Orga_Name' => trim($work['Orga_Name']),
                'Dsgn' => trim($work['Dsgn']) ?: null,

                // ✅ UPDATED DATE LOGIC
                'Prd_From' => $this->formatDateForDatabase($work['Prd_From']),
                'Prd_To' => $this->formatDateForDatabase($work['Prd_To']),

                'Orga_Type' => trim($work['Orga_Type']) ?: null,
                'Job_Desp' => trim($work['Job_Desp']) ?: null,
                'Work_Type' => $work['Work_Type'] ?: 'Full',
                'Admn_Cutr_Mast_UIN' => $work['Admn_Cutr_Mast_UIN'] ?: null,
                'CrBy' => $userUIN,
                'CrOn' => now(),
                'MoOn' => now(),
            ]);

            if ($index > 0) {
                usleep(50000);
            }
        }
    }

    private function attachPendingNoteIfExists(int $contactId, string $userUIN, int $orgaUIN): void
    {
        $pending = session('pending_contact_note');
        if (! $pending) {
            return;
        }

        $noteUIN = $this->generateUniqueUIN('admn_cnta_note_mast', 'Admn_Cnta_Note_Mast_UIN');
        DB::table('admn_cnta_note_mast')->insert([
            'Admn_Cnta_Note_Mast_UIN' => $noteUIN,
            'Admn_User_Mast_UIN' => $contactId,
            'Admn_Orga_Mast_UIN' => $orgaUIN,
            'Note_Detl' => trim($pending),
            'CrBy' => $userUIN,
            'CrOn' => now(),
        ]);

        session()->forget('pending_contact_note');
    }

    private function insertContactNotes($contactId, $userUIN)
    {
        if (! empty(trim($this->newNoteContent))) {
            $noteUIN = $this->generateUniqueUIN('admn_cnta_note_mast', 'Admn_Cnta_Note_Mast_UIN');
            DB::table('admn_cnta_note_mast')->insert([
                'Admn_Cnta_Note_Mast_UIN' => $noteUIN,
                'Admn_User_Mast_UIN' => $contactId,
                'Admn_Orga_Mast_UIN' => session('selected_Orga_UIN'),
                'Note_Detl' => trim($this->newNoteContent),
                'CrBy' => $userUIN,
                'CrOn' => now(),
                'MoBy' => null,
                'MoOn' => null,
            ]);
        }
    }

    // ============================================
    // CLEANUP & UTILITY METHODS
    // ============================================
    private function cleanDataForDatabase($data)
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

    // ============================================
    // CANCEL & HELPERS
    // ============================================
    public function cancel()
    {
        return redirect()->route('contacts.index');
    }

    public function getDocumentNameOptions()
    {
        return $this->getDocumentNameOptions();
    }

    public function addNote()
    {
        if (count($this->contactNotes) < self::MAX_NOTES) {
            $this->contactNotes[] = [
                'Note_Detl' => '',
                'created_at' => now(),
                'temp_id' => uniqid(),
            ];
        }
    }

    public function deleteNote($index)
    {
        if (isset($this->contactNotes[$index])) {
            $this->contactNotes[$index]['pending_delete'] = true;
            $this->contactNotes[$index]['delete_time'] = time();
            $this->dispatch('note-marked-for-deletion', noteIndex: $index);
        }
    }

    public function undoDeleteNote($index)
    {
        if (isset($this->contactNotes[$index])) {
            unset($this->contactNotes[$index]['pending_delete']);
            unset($this->contactNotes[$index]['delete_time']);
        }
    }

    // ============================================
    // RENDER
    // ============================================

    public function render()
    {
        return view('livewire.contacts.create', [
            'availableGroups' => $this->availableGroups,
        ])->layout('components.layouts.app');
    }
}

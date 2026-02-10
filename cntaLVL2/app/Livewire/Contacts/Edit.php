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
use App\Models\Admn_Docu_Mast;
use App\Models\Admn_Docu_Type_Mast;
use App\Models\Admn_Grup_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Tag_Mast;
use App\Models\Admn_User_Bank_Mast;
use App\Models\AdmnBankAttachment;
use App\Models\AdmnCntaNoteMast;
use App\Models\AdmnUserEducMast;
use App\Models\AdmnUserSkilMast;
use App\Models\AdmnUserWorkMast;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use FormatsDates;
    use GeneratesUINs;
    use HasAddressCascade;
    use HasMaxConstants, HasSkillData, HasWorkTypes, WithComments, WithContactValidation,WithDocumentNames,WithFileUploads;
    use LoadsReferenceData;

    public $contactId;

    public $contact;

    public $MoOn;

    public $Prty = 'I';

    public array $partyOptions = [
        'I' => 'Individual/Personal',
        'B' => 'Business/Organization',
    ];

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

    public $Note = '';

    public ?int $assignedGroupId = null;

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

    public $Web = '';

    public $FcBk = '';

    public $Twtr = '';

    public $LnDn = '';

    public $Intg = '';

    public $Yaho = '';

    public $Redt = '';

    public $Ytb = '';

    public bool $showNoteSidebar = false;

    public array $existingNotes = [];

    public ?string $newNoteContent = null;

    public string $activeCommentTab = 'PAN';

    public ?int $currentUserId = null;

    public ?int $organizationUIN = null;

    public int $verticalId = 5;

    public $isVerified = false;

    public $allTags = [];

    public $allCountries = [];

    public $allPrefixes = [];

    public $addressTypes = [];

    public $BaddressTypes = [];

    public $bankOptions = [];

    public $allDocumentTypes = [];

    public array $emails = [];

    public array $phones = [];

    public array $landlines = [];

    public array $addresses = [];

    public array $references = [];

    public array $bankAccounts = [];

    public array $documents = [];

    public array $selectedTags = [];

    public array $educations = [];

    public array $skills = [];

    public $skillTypes = [];

    public $skillSubtypes = [];

    public array $workTypes = [];

    public array $workExperiences = [];

    public function mount($contact)
    {
        $this->initializeContactId($contact);
        try {
            $this->loadCommonReferenceData();
            $this->loadContactDetails();
            $this->initializeWithDocumentNames();
            $this->skillTypes = $this->getSkillTypes();
            $this->skillSubtypes = $this->getSkillSubtypes();
            // load work-type options from trait
            $this->workTypes = $this->getWorkTypes();
            $this->currentUserId = session('authenticated_user_uin');
            $this->organizationUIN = session('selected_Orga_UIN');
            $this->verticalId = 5;
            $this->loadContactNotesHistory();
        } catch (\Exception $e) {
            Log::error('Mount Error: '.$e->getMessage());
            session()->flash('error', 'Error loading data: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.contacts.edit', [
            'availableGroups' => $this->getAvailableGroups(),
            'currentAssignedGroup' => $this->assignedGroupId,
        ])->layout('components.layouts.app');
    }

    public function loadContactNotesHistory(): void
    {
        if (! isset($this->contactId) || ! isset($this->currentUserId)) {
            return;
        }
        try {
            $this->existingNotes = AdmnCntaNoteMast::forContact($this->contactId)
                ->whereNull('DelOn')
                ->orderByRaw('IsPinned DESC, CrOn DESC')
                ->get()
                ->map(function ($note) {
                    return [
                        'id' => $note->Admn_Cnta_Note_Mast_UIN,
                        'content' => $note->Note_Detl,
                        'createdBy' => $note->getCreatorName(),
                        'createdByUIN' => $note->CrBy,
                        'createdOn' => $note->CrOn,
                        'isPinned' => (bool) $note->IsPinned,
                        'canManage' => $note->canBeManagedBy($this->currentUserId),
                    ];
                })
                ->toArray();
        } catch (\Throwable $e) {
            Log::error('Load Notes Error: '.$e->getMessage());
            $this->existingNotes = [];
        }
    }

    public function openNoteSidebar(): void
    {
        $this->showNoteSidebar = true;
        $this->activeCommentTab = 'PAN';
        $this->newNoteContent = '';
        $this->loadContactNotesHistory();
    }

    public function closeNoteSidebar(): void
    {
        $this->showNoteSidebar = false;
        $this->newNoteContent = '';
    }

    public function toggleNoteSidebar(): void
    {
        $this->showNoteSidebar ? $this->closeNoteSidebar() : $this->openNoteSidebar();
    }

    public function saveNote(): void
    {
        $noteContent = trim($this->newNoteContent ?? '');
        if ($noteContent === '') {
            $this->dispatch('toast-error', message: 'Note cannot be empty!');

            return;
        }
        try {
            $userUIN = session('authenticated_user_uin');
            // ✅ REPLACED: $maxUIN + 1 → generateUniqueUIN()
            $noteUIN = $this->generateUniqueUIN('admn_cnta_note_mast', 'Admn_Cnta_Note_Mast_UIN');

            AdmnCntaNoteMast::create([
                'Admn_Cnta_Note_Mast_UIN' => $noteUIN,
                'Admn_User_Mast_UIN' => $this->contactId,
                'Admn_Orga_Mast_UIN' => $this->organizationUIN,
                'Vertical_ID' => $this->verticalId ?? 5,
                'Note_Detl' => $noteContent,
                'CrOn' => now(),
                'CrBy' => $userUIN,
            ]);
            $this->dispatch('toast-success', message: 'Note saved successfully!');
            $this->newNoteContent = '';
            $this->loadContactNotesHistory();
        } catch (\Throwable $e) {
            Log::error('Note Save Error: '.$e->getMessage());
            $this->dispatch('toast-error', message: 'Failed to save note.');
        }
    }

    public function addCommentToNote($comment): void
    {
        $trimmedComment = trim($comment);
        if ($trimmedComment === '') {
            return;
        }
        $this->newNoteContent = trim($this->newNoteContent ?? '');
        $this->newNoteContent = $this->newNoteContent ? "{$this->newNoteContent}\n{$trimmedComment}" : $trimmedComment;
    }

    public function pinNote($noteId): void
    {
        try {
            $note = AdmnCntaNoteMast::findOrFail($noteId);
            $userUIN = session('authenticated_user_uin');
            $note->update([
                'IsPinned' => true,
                'PinnedOn' => now(),
                'PinnedBy' => $userUIN,
            ]);
            $this->dispatch('toast-success', message: 'Note pinned!');
            $this->loadContactNotesHistory();
        } catch (\Throwable $e) {
            Log::error('Pin Note Error: '.$e->getMessage());
            $this->dispatch('toast-error', message: 'Failed to pin note!');
        }
    }

    public function unpinNote($noteId): void
    {
        try {
            $note = AdmnCntaNoteMast::findOrFail($noteId);
            $note->update([
                'IsPinned' => false,
                'PinnedOn' => null,
                'PinnedBy' => null,
            ]);
            $this->dispatch('toast-success', message: 'Note unpinned!');
            $this->loadContactNotesHistory();
        } catch (\Throwable $e) {
            Log::error('Unpin Note Error: '.$e->getMessage());
            $this->dispatch('toast-error', message: 'Failed to unpin note!');
        }
    }

    public function deleteNote($noteId): void
    {
        try {
            $userUIN = session('authenticated_user_uin');
            $note = AdmnCntaNoteMast::findOrFail($noteId);
            $createdAt = Carbon::parse($note->CrOn);
            if ($note->CrBy !== $userUIN || $createdAt->diffInHours(now()) > 48) {
                $this->dispatch('toast-error', message: 'You cannot delete this note now.');

                return;
            }
            $note->update([
                'DelOn' => now(),
                'DelBy' => $userUIN,
            ]);
            $this->dispatch('toast-success', message: 'Note deleted!');
            $this->loadContactNotesHistory();
        } catch (\Throwable $e) {
            Log::error('Delete Note Error: '.$e->getMessage());
            $this->dispatch('toast-error', message: 'Failed to delete note!');
        }
    }

    public function canDeleteNote(array $note): bool
    {
        if (! isset($note['createdByUIN'], $note['createdOn'])) {
            return false;
        }
        $currentUser = session('authenticated_user_uin');
        if ($note['createdByUIN'] !== $currentUser) {
            return false;
        }
        $createdAt = Carbon::parse($note['createdOn']);

        return $createdAt->diffInHours(now()) <= 48;
    }

    private function initializeContactId($contact)
    {
        if (is_object($contact)) {
            $this->contact = $contact;
            $this->contactId = $contact->Admn_User_Mast_UIN;
            $this->MoOn = $contact->MoOn ?? null;
            $this->Prty = $contact->Prty ?? 'I';
        } else {
            $this->contactId = $contact;
            $this->contact = null;
        }
    }


    private function loadContactDetails()
    {
        $contact = $this->contact ?? DB::table('admn_user_mast')
            ->where('Admn_User_Mast_UIN', $this->contactId)
            ->first();
        if (! $contact) {
            return redirect()->route('contacts.index');
        }
        $this->mapContactFields($contact);
        $this->loadEmails();
        $this->loadPhones();
        $this->loadLandlines();
        $this->loadAddresses();
        $this->loadReferences();
        $this->loadSelectedTags();
        $this->loadBankAccounts();
        $this->loadDocuments();
        $this->loadEducations();
        $this->loadSkills();
        $this->loadWorkExperiences();
    }

    private function loadEducations()
    {
        $this->educations = AdmnUserEducMast::forContact($this->contactId)
            ->get()
            ->map(fn ($item) => [
                'Admn_User_Educ_Mast_UIN' => $item->Admn_User_Educ_Mast_UIN,
                'Deg_Name' => $item->Deg_Name,
                'Inst_Name' => $item->Inst_Name,
                'Cmpt_Year' => $item->Cmpt_Year,
                'Admn_Cutr_Mast_UIN' => $item->Admn_Cutr_Mast_UIN,
            ])
            ->toArray();
        if (empty($this->educations)) {
            $this->addEducation();
        }
    }

    private function loadSkills()
    {
        $this->skills = AdmnUserSkilMast::forContact($this->contactId)
            ->get()
            ->map(fn ($item) => [
                'Admn_User_Skil_Mast_UIN' => $item->Admn_User_Skil_Mast_UIN,
                'Skil_Type' => $item->Skil_Type,
                'Skil_Type_1' => $item->Skil_Type_1,
                'Skil_Name' => $item->Skil_Name,
                'Profc_Lvl' => $item->Profc_Lvl,
            ])
            ->toArray();
        if (empty($this->skills)) {
            $this->addSkill();
        }
    }

    private function loadWorkExperiences()
    {
        $this->workExperiences = AdmnUserWorkMast::forContact($this->contactId)
            ->get()
            ->map(fn ($item) => [
                'Admn_User_Work_Mast_UIN' => $item->Admn_User_Work_Mast_UIN,
                'Orga_Name' => $item->Orga_Name,
                'Dsgn' => $item->Dsgn,
                'Prd_From' => optional($item->Prd_From)->format('Y-m-d'),
                'Prd_To' => optional($item->Prd_To)->format('Y-m-d'),
                'Orga_Type' => $item->Orga_Type,
                'Job_Desp' => $item->Job_Desp,
                'Work_Type' => $item->Work_Type,
                'Admn_Cutr_Mast_UIN' => $item->Admn_Cutr_Mast_UIN,
            ])
            ->toArray();
        if (empty($this->workExperiences)) {
            $this->addWorkExperience();
        }
    }

    private function mapContactFields($contact)
    {
        $fields = [
            'Prfx_UIN', 'FaNm', 'MiNm', 'LaNm', 'Gend', 'Blood_Grp',
            'Brth_Dt', 'Anvy_Dt', 'Deth_Dt', 'Note', 'MoOn', 'Comp_Name',
            'Comp_Dsig', 'Comp_LdLi', 'Comp_Desp', 'Comp_Emai', 'Comp_Web',
            'Comp_Addr', 'Prfl_Name', 'Prfl_Addr', 'Web', 'FcBk', 'Twtr',
            'LnDn', 'Intg', 'Yaho', 'Redt', 'Ytb',
        ];
        foreach ($fields as $field) {
            $this->$field = $contact->$field ?? null;
        }
        $this->existing_avatar = $contact->Prfl_Pict ?? null;
        $this->Prty = $contact->Prty ?? 'I';
        $this->assignedGroupId = $contact->Admn_Grup_Mast_UIN ? (int) $contact->Admn_Grup_Mast_UIN : null;
        $this->isVerified = ($contact->Is_Vf ?? null) === self::STATUS_VERIFIED;
    }

    private function loadEmails()
    {
        $this->emails = $this->loadRelatedData(
            'admn_cnta_emai_mast',
            'Admn_Cnta_Emai_Mast_UIN',
            fn ($item) => [
                'Emai_Addr' => $item->Emai_Addr,
                'Emai_Type' => $item->Emai_Type,
                'Is_Prmy' => (bool) $item->Is_Prmy,
            ]
        );
        if (empty($this->emails)) {
            $this->addEmail();
        }
    }

    public function addEmail()
    {
        if (count($this->emails) < self::MAX_EMAILS) {
            $this->emails[] = [
                'Emai_Addr' => '',
                'Emai_Type' => 'self generated',
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

    private function loadPhones()
    {
        $this->phones = $this->loadRelatedData(
            'admn_cnta_phon_mast',
            'Admn_Cnta_Phon_Mast_UIN',
            fn ($item) => [
                'Phon_Numb' => $item->Phon_Numb,
                'Phon_Type' => $item->Phon_Type,
                'Cutr_Code' => $item->Cutr_Code,
                'Has_WtAp' => (bool) $item->Has_WtAp,
                'Has_Telg' => (bool) $item->Has_Telg,
                'Is_Prmy' => (bool) $item->Is_Prmy,
            ]
        );
        if (empty($this->phones)) {
            $this->addPhone();
        }
    }

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

    private function loadLandlines()
    {
        $this->landlines = $this->loadRelatedData(
            'admn_cnta_land_mast',
            'Admn_Cnta_Land_Mast_UIN',
            fn ($item) => [
                'Land_Numb' => $item->Land_Numb,
                'Land_Type' => $item->Land_Type,
                'Cutr_Code' => $item->Cutr_Code,
                'Is_Prmy' => (bool) $item->Is_Prmy,
            ]
        );
        if (empty($this->landlines)) {
            $this->addLandline();
        }
    }

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

    public function addEducation()
    {
        if (count($this->educations) < self::MAX_EDUCATIONS) {
            $this->educations[] = [
                'Deg_Name' => '',
                'Inst_Name' => '',
                'Cmpt_Year' => null,
                'Admn_Cutr_Mast_UIN' => self::COUNTRY_INDIA_UIN,
            ];
        }
    }

    public function removeEducation($index)
    {
        if (count($this->educations) <= 1) {
            return;
        }
        $this->resetErrorBag("educations.$index");
        unset($this->educations[$index]);
        $this->educations = array_values($this->educations);
    }

    public function addSkill()
    {
        if (count($this->skills) < self::MAX_SKILLS) {
            $this->skills[] = [
                'Skil_Type' => '',
                'Skil_Type_1' => '',
                'Skil_Name' => '',
                'Profc_Lvl' => null,
            ];
        }
    }

    public function removeSkill($index)
    {
        if (count($this->skills) <= 1) {
            return;
        }
        $this->resetErrorBag("skills.$index");
        unset($this->skills[$index]);
        $this->skills = array_values($this->skills);
    }

    public function addWorkExperience()
    {
        if (count($this->workExperiences) < self::MAX_WORK_EXPERIENCES) {
            $this->workExperiences[] = [
                'Orga_Name' => '',
                'Dsgn' => '',
                'Prd_From' => null,
                'Prd_To' => null,
                'Orga_Type' => '',
                'Job_Desp' => '',
                'Work_Type' => '',
                'Admn_Cutr_Mast_UIN' => self::COUNTRY_INDIA_UIN,
            ];
        }
    }

    public function removeWorkExperience($index)
    {
        if (count($this->workExperiences) <= 1) {
            return;
        }
        $this->resetErrorBag("workExperiences.$index");
        unset($this->workExperiences[$index]);
        $this->workExperiences = array_values($this->workExperiences);
    }

    private function loadAddresses()
    {
        $this->addresses = DB::table('admn_cnta_addr_mast as a')
            ->leftJoin('admn_pincode_mast as p', 'a.Admn_PinCode_Mast_UIN', '=', 'p.Admn_PinCode_Mast_UIN')
            ->where('a.Admn_User_Mast_UIN', $this->contactId)
            ->select('a.*', 'p.Code as pincode_value')
            ->orderBy('a.Is_Prmy', 'desc')
            ->get()
            ->map(fn ($addr) => $this->hydrateAddressFields((array) $addr, $addr->Admn_Cnta_Addr_Mast_UIN))
            ->toArray();
        if (empty($this->addresses)) {
            $this->addAddress();
        }
    }

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
                $country = Admn_Cutr_Mast::where('Stau_UIN', 100201) // status Active
                    ->where('Admn_Cutr_Mast_UIN', $primaryAddress['Admn_Cutr_Mast_UIN'])
                    ->first();

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

    private function loadReferences()
    {
        $this->references = $this->loadRelatedData(
            'admn_cnta_refa_mast',
            'Admn_Cnta_Refa_Mast_UIN',
            fn ($item) => [
                'Refa_Name' => $item->Refa_Name,
                'Refa_Phon' => $item->Refa_Phon,
                'Refa_Emai' => $item->Refa_Emai,
                'Refa_Rsip' => $item->Refa_Rsip,

                // ✅ Change this line to cast to (bool):
                'Is_Prmy' => (bool) $item->Is_Prmy,
            ]
        );
        if (empty($this->references)) {
            $this->addReference();
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

    private function loadBankAccounts()
    {
        $this->bankAccounts = Admn_User_Bank_Mast::with('attachments')
            ->where('Admn_User_Mast_UIN', $this->contactId)
            ->get()
            ->map(fn ($bank) => [
                'Admn_User_Bank_Mast_UIN' => $bank->Admn_User_Bank_Mast_UIN,
                'Bank_Name_UIN' => $bank->Bank_Name_UIN,
                'Bank_Brnc_Name' => $bank->Bank_Brnc_Name,
                'Acnt_Type' => $bank->Acnt_Type,
                'Acnt_Numb' => $bank->Acnt_Numb,
                'IFSC_Code' => $bank->IFSC_Code,
                'Swift_Code' => $bank->Swift_Code,
                'Prmy' => (bool) $bank->Prmy,
                'existing_attachments' => $bank
                    ->attachments
                    ->map(fn ($a) => [
                        'Admn_Bank_Attachment_UIN' => $a->Admn_Bank_Attachment_UIN,
                        'Atch_Path' => $a->Atch_Path,
                        'Orgn_Name' => $a->Orgn_Name,
                    ])
                    ->toArray(),
                'newAttachments' => [],
                'temp_upload' => [],
            ])
            ->toArray();
        if (empty($this->bankAccounts)) {
            $this->addBank();
        }
    }

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
        $this->resetErrorBag("bankAccounts.{$index}.newAttachments.*");
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

    public function deleteExistingAttachment($bankIndex, $attachmentId)
    {
        try {
            $attachment = AdmnBankAttachment::find($attachmentId);
            if ($attachment) {
                Storage::disk('public')->delete($attachment->Atch_Path);
                $attachment->delete();
                $this->bankAccounts[$bankIndex]['existing_attachments'] = array_values(
                    array_filter(
                        $this->bankAccounts[$bankIndex]['existing_attachments'],
                        fn ($a) => $a['Admn_Bank_Attachment_UIN'] != $attachmentId
                    )
                );
                session()->flash('success', 'Attachment deleted successfully.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting attachment: '.$e->getMessage());
            session()->flash('error', 'Failed to delete attachment.');
        }
    }

    /**
     * Load documents from database
     */
    private function loadDocuments(): void
    {
        $existing = Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)
            ->orderBy('Regn_Numb')
            ->orderBy('Docu_Atch_Path')
            ->get();

        if ($existing->isEmpty()) {
            $this->documents = [];
            // REMOVED: $this->addDocument(); // Do not auto-add a blank document either, unless desired
            // If you want to auto-add a blank one, keep it, but ensure 'Prmy' is false in addDocument
            $this->addDocument();

            return;
        }

        $grouped = $existing->groupBy('Regn_Numb');

        $this->documents = $grouped->map(function ($group) {
            $withAttachment = $group->firstWhere('Docu_Atch_Path', '!=', null);
            $first = $withAttachment ?? $group->first();

            return [
                'Admn_Docu_Mast_UIN' => $first->Admn_Docu_Mast_UIN,
                'Docu_Name' => $first->Docu_Name ?? '',
                'Regn_Numb' => $first->Regn_Numb ?? '',
                'selected_types' => $group->pluck('Admn_Docu_Type_Mast_UIN')->toArray(),
                'Admn_Cutr_Mast_UIN' => $first->Admn_Cutr_Mast_UIN ?? null,
                'Auth_Issd' => $first->Auth_Issd ?? '',
                'Vald_From' => optional($first->Vald_From)->format('Y-m-d'),
                'Vald_Upto' => optional($first->Vald_Upto)->format('Y-m-d'),
                'Docu_Atch_Path' => $first->Docu_Atch_Path,
                'existing_file_path' => $first->Docu_Atch_Path,
                'is_dropdown_open' => false,
                'Prmy' => (bool) ($first->Prmy ?? false),
            ];
        })->values()->toArray();

    }

    /**
     * Add a new document entry
     */
    public function addDocument(): void
    {
        if (count($this->documents) >= self::MAX_DOCUMENTS) {
            return;
        }

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
            'Prmy' => false, // CHANGE: Always false by default
        ];
    }

    // In app/Livewire/Contacts/Edit.php

    public function setPrimaryDocument($index)
    {
        // 1. Validate index exists
        if (! isset($this->documents[$index])) {
            return;
        }

        // 2. Update Local State (Visual Feedback)
        foreach ($this->documents as $key => &$doc) {
            $doc['Prmy'] = ($key === $index);
        }

        // 3. Perform Live Database Update
        $targetDoc = $this->documents[$index];
        $regnNumb = $targetDoc['Regn_Numb'] ?? null;
        $userUIN = $this->contactId;

        // We can only update the DB if the document has been saved (has a Registration Number)
        if (! empty($regnNumb) && $userUIN) {
            try {
                DB::transaction(function () use ($userUIN, $regnNumb) {
                    // Step A: Set 'Prmy' to 0 for ALL documents belonging to this user
                    Admn_Docu_Mast::where('Admn_User_Mast_UIN', $userUIN)
                        ->update(['Prmy' => 0]);

                    // Step B: Set 'Prmy' to 1 for the specific Registration Number group
                    Admn_Docu_Mast::where('Admn_User_Mast_UIN', $userUIN)
                        ->where('Regn_Numb', $regnNumb)
                        ->update(['Prmy' => 1]);
                });

            } catch (\Exception $e) {
                Log::error('Live Primary Update Failed: '.$e->getMessage());
                $this->dispatch('toast-error', message: 'Failed to update database.');
            }
        } else {
            // Fallback for new/unsaved documents (they exist in array but not in DB yet)
            $this->dispatch('toast-info', message: 'Set as primary. Click Update to persist.');
        }
    }

    public function removeDocument($index)
    {
        if (! isset($this->documents[$index])) {
            return;
        }

        $document = $this->documents[$index];

        try {
            // Delete existing file from storage
            $filePath = $document['existing_file_path'] ?? null;
            if ($filePath && is_string($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            // Delete from database if it exists
            if (! empty($document['Regn_Numb'])) {
                Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)
                    ->where('Regn_Numb', $document['Regn_Numb'])
                    ->delete();
            }

            // Remove from local array
            unset($this->documents[$index]);
            $this->documents = array_values($this->documents);

            // REMOVED: The logic that checked if primary was missing and set documents[0] to true.

            Log::info("Document removed from database: Regn_Numb={$document['Regn_Numb']}");

        } catch (\Exception $e) {
            Log::error('Error removing document: '.$e->getMessage());
            throw $e;
        }
    }

    public function toggleDocumentDropdown($index)
    {
        $currentState = $this->documents[$index]['is_dropdown_open'] ?? false;
        foreach ($this->documents as &$doc) {
            $doc['is_dropdown_open'] = false;
        }
        $this->documents[$index]['is_dropdown_open'] = ! $currentState;
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
        $this->documents[$index]['existing_file_path'] = null;
        $this->resetErrorBag("documents.$index.Docu_Atch_Path");
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
        $this->resetErrorBag("documents.{$index}.Docu_Atch_Path");
        if (! isset($this->documents[$index])) {
            return;
        }
        $file = $this->documents[$index]['Docu_Atch_Path'] ?? null;
        if (! $file) {
            return;
        }
        if (is_string($file)) {
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

    private function loadSelectedTags()
    {
        $this->selectedTags = DB::table('admn_cnta_tag_mast as ct')
            ->join('admn_tag_mast as t', 't.Admn_Tag_Mast_UIN', '=', 'ct.Admn_Tag_Mast_UIN')
            ->where('ct.Admn_User_Mast_UIN', $this->contactId)
            ->where('t.stau', 100201)  // ✅ Only Active Tags
            ->pluck('ct.Admn_Tag_Mast_UIN')
            ->toArray();
    }

    public function removeProfilePicture()
    {
        if ($this->existing_avatar) {
            Storage::disk('public')->delete($this->existing_avatar);
            $this->existing_avatar = null;
        }
        $this->Prfl_Pict = null;
        $this->resetErrorBag('Prfl_Pict');
    }

    private function loadRelatedData($table, $pk, $mapper)
    {
        return DB::table($table)
            ->where('Admn_User_Mast_UIN', $this->contactId)
            ->orderBy(Str::contains($table, ['emai', 'phon', 'addr']) ? 'Is_Prmy' : $pk, 'desc')
            ->get()
            ->map(fn ($item) => array_merge(['id' => $item->$pk], $mapper($item)))
            ->toArray();
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
        foreach ($this->$arrayName as $key => &$item) {
            $item[$field] = ($key === $index);
        }
    }

    private function getAvailableGroups()
    {
        $orgUIN = session('selected_Orga_UIN');

        return Admn_Grup_Mast::where('Admn_Orga_Mast_UIN', $orgUIN)
            ->orderBy('Name')
            ->get();
    }

    private function generateAttachmentFileName($extension = ''): string
    {
        $orgUIN = session('selected_Orga_UIN');
        $contactUIN = $this->contactId;
        $timestamp = now()->format('Ymd_His');
        $filename = "{$orgUIN}_{$contactUIN}_{$timestamp}";
        if (! empty($extension)) {
            $filename .= ".{$extension}";
        }

        return $filename;
    }

    public function save()
    {
        $this->validate();
        DB::beginTransaction();
        try {
            $userUIN = session('authenticated_user_uin');
            $orgUIN = session('selected_Orga_UIN');

            $contactData = [
                'Prty' => trim($this->Prty) ?: null,
                'Prfx_UIN' => $this->Prfx_UIN,
                'FaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->FaNm)),
                'MiNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->MiNm)) ?: null,
                'LaNm' => preg_replace('/[^a-zA-Z ]/', '', trim($this->LaNm)) ?: null,
                'Gend' => $this->Gend,
                'Blood_Grp' => $this->Blood_Grp,

                // ✅ UPDATED DATE LOGIC
                'Brth_Dt' => $this->formatDateForDatabase($this->Brth_Dt),
                'Anvy_Dt' => $this->formatDateForDatabase($this->Anvy_Dt),
                'Deth_Dt' => $this->formatDateForDatabase($this->Deth_Dt),

                'Note' => $this->Note,
                'Comp_Name' => $this->Comp_Name,
                'Comp_Dsig' => $this->Comp_Dsig,
                'Comp_LdLi' => $this->Comp_LdLi,
                'Comp_Desp' => $this->Comp_Desp,
                'Comp_Emai' => trim(strtolower($this->Comp_Emai)),
                'Comp_Web' => $this->Comp_Web,
                'Comp_Addr' => $this->Comp_Addr,
                'Prfl_Name' => $this->Prfl_Name,
                'Prfl_Addr' => $this->Prfl_Addr,
                'Web' => $this->Web,
                'FcBk' => $this->FcBk,
                'Twtr' => $this->Twtr,
                'LnDn' => $this->LnDn,
                'Intg' => $this->Intg,
                'Yaho' => $this->Yaho,
                'Redt' => $this->Redt,
                'Ytb' => $this->Ytb,
                'Admn_Grup_Mast_UIN' => $this->assignedGroupId,
                'Is_Vf' => self::STATUS_VERIFIED,
                'MoOn' => now(),
                'MoBy' => $userUIN,
            ];
            if ($this->Prfl_Pict) {
                try {
                    if ($this->Prfl_Pict->getSize() > self::MAX_PROFILE_FILE_SIZE) {
                        session()->flash('error', 'Profile picture size must not exceed 2 MB.');
                    } else {
                        if ($this->existing_avatar) {
                            Storage::disk('public')->delete($this->existing_avatar);
                        }
                        $extension = $this->Prfl_Pict->getClientOriginalExtension();
                        $filename = $this->generateAttachmentFileName($extension);
                        $filePath = $this->Prfl_Pict->storeAs('Attachment/Profile', $filename, 'public');
                        $fullPath = Storage::disk('public')->path($filePath);

                        if (file_exists($fullPath) && class_exists('Intervention\Image\ImageManagerStatic')) {
                            Image::make($fullPath)->fit(512, 512)->save();
                        }
                        $contactData['Prfl_Pict'] = $filePath;
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing profile picture: '.$e->getMessage());
                    session()->flash('error', 'Error uploading profile picture: '.$e->getMessage());
                }
            } elseif ($this->existing_avatar) {
                $contactData['Prfl_Pict'] = $this->existing_avatar;
            } else {
                $contactData['Prfl_Pict'] = null;
            }
            DB::table('admn_user_mast')
                ->where('Admn_User_Mast_UIN', $this->contactId)
                ->update($contactData);
            $this->syncEmails($userUIN, $orgUIN);
            $this->syncPhones($userUIN, $orgUIN);
            $this->syncLandlines($userUIN, $orgUIN);
            $this->syncAddresses($userUIN, $orgUIN);
            $this->syncReferences($userUIN, $orgUIN);
            $this->syncTags($userUIN, $orgUIN);
            $this->syncBankAccounts($userUIN, $orgUIN);
            $this->syncDocuments($userUIN);
            $this->syncEducations($userUIN, $orgUIN);
            $this->syncSkills($userUIN, $orgUIN);
            $this->syncWorkExperiences($userUIN, $orgUIN);
            DB::commit();
            session()->flash('success', 'Contact updated successfully!');

            return redirect()->route('contacts.index', $this->contactId);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contact Update Error: '.$e->getMessage());
            session()->flash('error', 'Failed to update contact: '.$e->getMessage());
        }
    }

    private function syncEmails($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_emai_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->emails as $email) {
            if (! empty($email['Emai_Addr'])) {
                // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
                $emailUIN = $this->generateUniqueUIN('admn_cnta_emai_mast', 'Admn_Cnta_Emai_Mast_UIN');

                DB::table('admn_cnta_emai_mast')->insert([
                    'Admn_Cnta_Emai_Mast_UIN' => $emailUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Emai_Addr' => trim(strtolower($email['Emai_Addr'])),
                    'Emai_Type' => $email['Emai_Type'] ?? 'self generated',
                    'Is_Prmy' => (bool) ($email['Is_Prmy'] ?? false),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncPhones($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_phon_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->phones as $phone) {
            if (! empty($phone['Phon_Numb'])) {
                // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
                $phoneUIN = $this->generateUniqueUIN('admn_cnta_phon_mast', 'Admn_Cnta_Phon_Mast_UIN');

                DB::table('admn_cnta_phon_mast')->insert([
                    'Admn_Cnta_Phon_Mast_UIN' => $phoneUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Phon_Numb' => $phone['Phon_Numb'],
                    'Phon_Type' => $phone['Phon_Type'] ?? 'self',
                    'Cutr_Code' => $phone['Cutr_Code'] ?? self::PHONE_CODE_INDIA,
                    'Has_WtAp' => (bool) ($phone['Has_WtAp'] ?? false),
                    'Has_Telg' => (bool) ($phone['Has_Telg'] ?? false),
                    'Is_Prmy' => (bool) ($phone['Is_Prmy'] ?? false),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncLandlines($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_land_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->landlines as $landline) {
            if (! empty($landline['Land_Numb'])) {
                // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
                $landlineUIN = $this->generateUniqueUIN('admn_cnta_land_mast', 'Admn_Cnta_Land_Mast_UIN');

                DB::table('admn_cnta_land_mast')->insert([
                    'Admn_Cnta_Land_Mast_UIN' => $landlineUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Admn_Orga_Mast_UIN' => $orgUIN,
                    'Land_Numb' => $landline['Land_Numb'],
                    'Land_Type' => $landline['Land_Type'] ?? 'home',
                    'Cutr_Code' => $landline['Cutr_Code'] ?? self::PHONE_CODE_INDIA,
                    'Is_Prmy' => (bool) ($landline['Is_Prmy'] ?? false),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncAddresses($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_addr_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->addresses as $address) {
            if (! empty($address['Addr']) || ! empty($address['Admn_PinCode_Mast_UIN'])) {
                // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
                $addressUIN = $this->generateUniqueUIN('admn_cnta_addr_mast', 'Admn_Cnta_Addr_Mast_UIN');

                DB::table('admn_cnta_addr_mast')->insert([
                    'Admn_Cnta_Addr_Mast_UIN' => $addressUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Addr' => $address['Addr'] ?? null,
                    'Loca' => $address['Loca'] ?? null,
                    'Lndm' => $address['Lndm'] ?? null,
                    'Admn_Addr_Type_Mast_UIN' => $address['Admn_Addr_Type_Mast_UIN'] ?? null,
                    'Admn_Cutr_Mast_UIN' => $address['Admn_Cutr_Mast_UIN'] ?? null,
                    'Admn_Stat_Mast_UIN' => $address['Admn_Stat_Mast_UIN'] ?? null,
                    'Admn_Dist_Mast_UIN' => $address['Admn_Dist_Mast_UIN'] ?? null,
                    'Admn_PinCode_Mast_UIN' => $address['Admn_PinCode_Mast_UIN'] ?? null,
                    'Is_Prmy' => (bool) ($address['Is_Prmy'] ?? false),
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncReferences($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_refa_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->references as $reference) {
            if (! empty($reference['Refa_Name'])) {
                $referenceUIN = $this->generateUniqueUIN('admn_cnta_refa_mast', 'Admn_Cnta_Refa_Mast_UIN');

                DB::table('admn_cnta_refa_mast')->insert([
                    'Admn_Cnta_Refa_Mast_UIN' => $referenceUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Refa_Name' => $reference['Refa_Name'],
                    'Refa_Phon' => $reference['Refa_Phon'] ?? null,
                    'Refa_Emai' => trim(strtolower($reference['Refa_Emai'])) ?? null,
                    'Refa_Rsip' => $reference['Refa_Rsip'] ?? null,

                    // ✅ ADD THIS LINE:
                    'Is_Prmy' => (bool) ($reference['Is_Prmy'] ?? false),

                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncTags($userUIN, $orgUIN)
    {
        DB::table('admn_cnta_tag_mast')->where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->selectedTags as $tagId) {
            // ✅ REPLACED: Manual ID generation → generateUniqueUIN()
            $tagMapUIN = $this->generateUniqueUIN('admn_cnta_tag_mast', 'Admn_Cnta_Tag_Mast_UIN');

            DB::table('admn_cnta_tag_mast')->insert([
                'Admn_Cnta_Tag_Mast_UIN' => $tagMapUIN,
                'Admn_User_Mast_UIN' => $this->contactId,
                'Admn_Tag_Mast_UIN' => $tagId,
                'CrOn' => now(),
                'CrBy' => $userUIN,
            ]);
        }
    }

    private function syncBankAccounts($userUIN, $orgUIN)
    {
        $existingBankIds = Admn_User_Bank_Mast::where('Admn_User_Mast_UIN', $this->contactId)
            ->pluck('Admn_User_Bank_Mast_UIN')
            ->toArray();

        foreach ($this->bankAccounts as $bank) {
            if (empty($bank['Acnt_Numb'])) {
                continue;
            }

            $bankId = $bank['Admn_User_Bank_Mast_UIN'] ?? null;

            $bankData = [
                'Bank_Name_UIN' => ! empty($bank['Bank_Name_UIN']) ? $bank['Bank_Name_UIN'] : null,
                'Bank_Brnc_Name' => $bank['Bank_Brnc_Name'],
                'Acnt_Type' => $bank['Acnt_Type'],
                'Acnt_Numb' => $bank['Acnt_Numb'],
                'IFSC_Code' => $bank['IFSC_Code'],
                'Swift_Code' => $bank['Swift_Code'],
                'Prmy' => (bool) ($bank['Prmy'] ?? false),
            ];

            if ($bankId && in_array($bankId, $existingBankIds)) {
                Admn_User_Bank_Mast::where('Admn_User_Bank_Mast_UIN', $bankId)->update($bankData + [
                    'MoOn' => now(),
                    'MoBy' => $userUIN,
                ]);

                $existingBankIds = array_diff($existingBankIds, [$bankId]);
            } else {
                $newBankId = $this->generateUniqueUIN('admn_user_bank_mast', 'Admn_User_Bank_Mast_UIN');
                Admn_User_Bank_Mast::create($bankData + [
                    'Admn_User_Bank_Mast_UIN' => $newBankId,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Admn_Orga_Mast_UIN' => $orgUIN,
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
                $bankId = $newBankId;
            }

            if (! empty($bank['newAttachments'])) {
                foreach ($bank['newAttachments'] as $file) {
                    $attachmentUIN = $this->generateUniqueUIN('admn_bank_attachments', 'Admn_Bank_Attachment_UIN');
                    $extension = $file->getClientOriginalExtension();
                    $filename = $this->generateAttachmentFileName($extension);
                    $path = $file->storeAs('Attachment/Document', $filename, 'public');
                    AdmnBankAttachment::create([
                        'Admn_Bank_Attachment_UIN' => $attachmentUIN,
                        'Admn_User_Bank_Mast_UIN' => $bankId,
                        'Atch_Path' => $path,
                        'Orgn_Name' => $file->getClientOriginalName(),
                        'CrOn' => now(),
                        'CrBy' => $userUIN,
                    ]);
                }
            }
        }

        if (! empty($existingBankIds)) {
            Admn_User_Bank_Mast::whereIn('Admn_User_Bank_Mast_UIN', $existingBankIds)->delete();
        }
    }

    private function syncDocuments($userUIN)
    {
        try {
            DB::beginTransaction();

            $validDocs = collect($this->documents)->filter(
                fn ($d) => ! empty($d['selected_types']) && ! empty(trim($d['Regn_Numb'] ?? ''))
            );

            $existing = Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)->get();

            if ($validDocs->isEmpty()) {
                // Delete all if no valid docs
                foreach ($existing as $doc) {
                    if ($doc->Docu_Atch_Path) {
                        Storage::disk('public')->delete($doc->Docu_Atch_Path);
                    }
                }
                Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)->delete();
                DB::commit();

                return;
            }

            $keptRegns = $validDocs->pluck('Regn_Numb')->unique()->toArray();
            $toDelete = $existing->whereNotIn('Regn_Numb', $keptRegns);

            if ($toDelete->isNotEmpty()) {
                foreach ($toDelete as $doc) {
                    if ($doc->Docu_Atch_Path && Storage::disk('public')->exists($doc->Docu_Atch_Path)) {
                        Storage::disk('public')->delete($doc->Docu_Atch_Path);
                    }
                }
                Admn_Docu_Mast::destroy($toDelete->pluck('Admn_Docu_Mast_UIN'));
            }

            foreach ($validDocs as $doc) {
                $path = $this->storeDocumentFile($doc, $existing);
                $group = $existing->where('Regn_Numb', $doc['Regn_Numb']);
                $removedTypes = $group->whereNotIn('Admn_Docu_Type_Mast_UIN', $doc['selected_types']);

                if ($removedTypes->isNotEmpty()) {
                    Admn_Docu_Mast::destroy($removedTypes->pluck('Admn_Docu_Mast_UIN'));
                }

                foreach ($doc['selected_types'] as $typeId) {
                    $record = $group->firstWhere('Admn_Docu_Type_Mast_UIN', $typeId);

                    $data = [
                        'Docu_Name' => $doc['Docu_Name'] ?? '',
                        'Prmy' => $doc['Prmy'] ? 1 : 0,
                        'Admn_Cutr_Mast_UIN' => $doc['Admn_Cutr_Mast_UIN'] ?? null,
                        'Auth_Issd' => $doc['Auth_Issd'] ?? '',
                        'Vald_From' => $this->formatDateForDatabase($doc['Vald_From']),
                        'Vald_Upto' => $this->formatDateForDatabase($doc['Vald_Upto']),
                        'Docu_Atch_Path' => $path,
                        'MoOn' => now(),
                        'MoBy' => $userUIN,
                    ];

                    if ($record) {
                        $record->update($data);
                    } else {
                        $newId = $this->generateUniqueUIN('admn_docu_mast', 'Admn_Docu_Mast_UIN');

                        DB::table('admn_docu_mast')->insert(array_merge($data, [
                            'Admn_Docu_Mast_UIN' => $newId,
                            'Admn_User_Mast_UIN' => $this->contactId,
                            'Admn_Orga_Mast_UIN' => session('selected_Orga_UIN'),
                            'Admn_Docu_Type_Mast_UIN' => $typeId,
                            'Regn_Numb' => $doc['Regn_Numb'],
                            'Stau' => self::STATUS_ACTIVE,
                            'CrOn' => now(),
                            'CrBy' => $userUIN,
                        ]));
                        usleep(50000);
                    }
                }
            }

            DB::commit();
            Log::info("Documents synced successfully for user: {$this->contactId}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing documents: '.$e->getMessage());
            throw $e;
        }
    }

    private function storeDocumentFile($doc)
    {
        try {
            if (empty($doc['Docu_Atch_Path'])) {
                return null;
            }
            if (is_string($doc['Docu_Atch_Path'])) {
                return $doc['Docu_Atch_Path'];
            }
            if (is_object($doc['Docu_Atch_Path'])) {
                $file = $doc['Docu_Atch_Path'];
                if ($file->getSize() > self::MAX_DOCUMENT_FILE_SIZE) {
                    Log::warning("Document exceeds 100KB: {$file->getClientOriginalName()}");
                    throw new \Exception("Document '{$file->getClientOriginalName()}' exceeds 100KB limit.");
                }
                $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
                if (! in_array($file->getMimeType(), $allowedMimes)) {
                    Log::warning("Invalid document type: {$file->getMimeType()}");
                    throw new \Exception('Invalid document type. Allowed: PDF, JPG, PNG, WEBP.');
                }
                $extension = $file->getClientOriginalExtension();
                $filename = $this->generateAttachmentFileName($extension);
                $storagePath = $file->storeAs('Attachment/Document', $filename, 'public');
                Log::info("Document stored successfully: {$storagePath}");

                return $storagePath;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error uploading document: '.$e->getMessage());
            throw $e;
        }
    }

    private function syncEducations($userUIN, $orgUIN)
    {
        AdmnUserEducMast::where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->educations as $education) {
            if (! empty($education['Deg_Name']) || ! empty($education['Inst_Name'])) {
                // ✅ REPLACED: Model create will use auto-increment, but we'll generate UIN explicitly
                $educUIN = $this->generateUniqueUIN('admn_user_educ_mast', 'Admn_User_Educ_Mast_UIN');

                AdmnUserEducMast::create([
                    'Admn_User_Educ_Mast_UIN' => $educUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Deg_Name' => $education['Deg_Name'],
                    'Inst_Name' => $education['Inst_Name'],
                    'Cmpt_Year' => $education['Cmpt_Year'],
                    'Admn_Cutr_Mast_UIN' => $education['Admn_Cutr_Mast_UIN'],
                    'CrOn' => now(),
                    'CrBy' => $userUIN,
                ]);
            }
        }
    }

    private function syncSkills($userUIN, $orgUIN)
    {
        AdmnUserSkilMast::where('Admn_User_Mast_UIN', $this->contactId)->delete();
        foreach ($this->skills as $skill) {
            if (empty($skill['Skil_Type']) || empty($skill['Skil_Type_1'])) {
                continue;
            }
            $skillName = $skill['Skil_Name'] ?? null;

            // if ($skill['Skil_Type_1'] !== 'Other') {
            //     $skillName = $skill['Skil_Type_1'];
            // }

            if (empty($skillName)) {
                continue;
            }
            $skillUIN = $this->generateUniqueUIN('admn_user_skil_mast', 'Admn_User_Skil_Mast_UIN');
            AdmnUserSkilMast::create([
                'Admn_User_Skil_Mast_UIN' => $skillUIN,
                'Admn_User_Mast_UIN' => $this->contactId,
                'Skil_Type' => $skill['Skil_Type'],
                'Skil_Type_1' => $skill['Skil_Type_1'],
                'Skil_Name' => $skillName,
                'Profc_Lvl' => $skill['Profc_Lvl'],
                'CrOn' => now(),
                'CrBy' => $userUIN,
            ]);
        }
    }

    private function syncWorkExperiences($userUIN, $orgUIN)
    {
        AdmnUserWorkMast::where('Admn_User_Mast_UIN', $this->contactId)->delete();

        foreach ($this->workExperiences as $work) {
            if (! empty($work['Orga_Name']) || ! empty($work['Dsgn'])) {
                $workUIN = $this->generateUniqueUIN('admn_user_work_mast', 'Admn_User_Work_Mast_UIN');

                AdmnUserWorkMast::create([
                    'Admn_User_Work_Mast_UIN' => $workUIN,
                    'Admn_User_Mast_UIN' => $this->contactId,
                    'Orga_Name' => trim($work['Orga_Name']) ?: null,
                    'Dsgn' => trim($work['Dsgn']) ?: null,

                    // ✅ UPDATED DATE LOGIC (Replaced verbose try/catch block)
                    'Prd_From' => $this->formatDateForDatabase($work['Prd_From']),
                    'Prd_To' => $this->formatDateForDatabase($work['Prd_To']),

                    'Orga_Type' => trim($work['Orga_Type']) ?: null,
                    'Job_Desp' => trim($work['Job_Desp']) ?: null,
                    'Work_Type' => $work['Work_Type'] ?: 'Full',
                    'Admn_Cutr_Mast_UIN' => $work['Admn_Cutr_Mast_UIN'] ?: null,
                    'CrOn' => now(),
                    'MoOn' => now(),
                    'CrBy' => $userUIN,
                    'MoBy' => $userUIN,
                ]);
            }
        }
    }

    public function updated($propertyName)
    {
        // 1. Skip validation for UI flags or reference data
        $skipValidation = [
            'Prty', 'addressTypes', 'allTags', 'allCountries', 'allPrefixes',
            'bankOptions', 'allDocumentTypes', 'contactId', 'MoOn', 'Empl_Type',
            'showNoteSidebar', 'pincodeSearch',
        ];

        if (Str::contains($propertyName, $skipValidation) || Str::endsWith($propertyName, 'pincodeSearch')) {
            return;
        }

        if (in_array($propertyName, ['FaNm', 'MiNm', 'LaNm'])) {
            $this->$propertyName = preg_replace('/[^a-zA-Z ]/', '', $this->$propertyName);
        }
        // 2. Handle Bank File Uploads (State Change)
        // We still need to call this to move the file from temp_upload to newAttachments array
        if (preg_match('/bankAccounts\.(\d+)\.temp_upload/', $propertyName, $matches)) {
            $this->handleBankUpload($matches[1]);

            return;
        }

        // 3. Skip Flags (optional, usually good to skip real-time validation for checkboxes)
        if (Str::contains($propertyName, ['Has_WtAp', 'Has_Telg', 'Is_Prmy'])) {
            return;
        }

        // 4. Cross-Validation: Bank Name <-> Account Number
        // We force validation on the *other* field to ensure 'required_with' errors appear/disappear instantly
        if (preg_match('/bankAccounts\.(\d+)\.(Bank_Name_UIN|Acnt_Numb)/', $propertyName, $matches)) {
            $index = $matches[1];

            // Validate the field that changed
            $this->validateOnly($propertyName);

            // Re-validate the partner field
            if (str_contains($propertyName, 'Bank_Name_UIN')) {
                $this->validateOnly("bankAccounts.{$index}.Acnt_Numb");
            } elseif (str_contains($propertyName, 'Acnt_Numb')) {
                $this->validateOnly("bankAccounts.{$index}.Bank_Name_UIN");
            }

            return;
        }

        // 5. Cross-Validation: Document Dates (From <-> To)
        // Ensures the "End date must be after start date" error triggers regardless of which one you edit
        if (preg_match('/documents\.(\d+)\.(Vald_From|Vald_Upto)/', $propertyName, $matches)) {
            $index = $matches[1];

            $this->validateOnly($propertyName);

            if (str_contains($propertyName, 'Vald_From')) {
                $this->validateOnly("documents.{$index}.Vald_Upto");
            } elseif (str_contains($propertyName, 'Vald_Upto')) {
                $this->validateOnly("documents.{$index}.Vald_From");
            }

            return;
        }

        // 6. Default Validation
        // This picks up rules from WithContactValidation trait automatically
        $this->validateOnly($propertyName);
    }
}

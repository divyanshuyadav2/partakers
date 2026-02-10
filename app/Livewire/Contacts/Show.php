<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\HasMaxConstants;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Docu_Mast;
use App\Models\Admn_Docu_Type_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Tag_Mast;
use App\Models\Admn_User_Mast;
use App\Models\Admn_Cnta_Refa_Mast;
use App\Models\AdmnUserEducMast;
use App\Models\AdmnUserSkilMast;
use App\Models\AdmnUserWorkMast;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class Show extends Component
{
    use HasMaxConstants;

    public $contact;
    public $contactId;

    public ?string $prefixName = null;

    public $phones = [];

    public $emails = [];

    public $landlines = [];

    public $addresses = [];

    public $bankAccounts = [];

    public $documents = [];

    public $allTags = [];

    public $allCountries = [];

    public $allPrefixes = [];

    public $addressTypes = [];

    public $allDocumentTypes = [];

    // New features for display
    public $educations = [];

    public $skills = [];

    public $workExperiences = [];

    public $contactNotes = [];

    public function mount($contact)
    {
        if ($contact instanceof Admn_User_Mast) {
            $this->contact = $contact;
            $this->contactId = $contact->Admn_User_Mast_UIN;
        } else {
            $this->contactId = $contact;
            $this->loadContact();
        }
        $this->loadEducations();
        $this->loadSkills();
        $this->loadWorkExperiences();
        $this->loadContactNotes();

        $this->loadPhones();
        $this->loadEmails();
        $this->loadLandlines();
        $this->loadAddresses();
        $this->loadBankAccounts();
        $this->loadDocuments();
        $this->loadLookupData();
        $this->loadDynamicProperties();
    }

    private function loadContact()
    {
        try {
            $this->contact = Admn_User_Mast::with([
                'referencePersons' => function ($query) {
                    $query->orderBy('Is_Prmy', 'desc');
                },
                'tags',
                'group'
            ])
                ->where('Is_Actv', self::STATUS_ACTIVE)
                ->findOrFail($this->contactId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // ... error handling
        }
    }

    private function loadPhones()
    {
        try {
            $this->phones = DB::table('admn_cnta_phon_mast')
                ->where('Admn_User_Mast_UIN', $this->contactId)
                ->orderBy('Is_Prmy', 'desc')
                ->get()
                ->map(fn ($item) => [
                    'Phon_Numb' => $item->Phon_Numb,
                    'Phon_Type' => $item->Phon_Type,
                    'Cutr_Code' => $item->Cutr_Code,
                    'Has_WtAp' => (bool) $item->Has_WtAp,
                    'Has_Telg' => (bool) $item->Has_Telg,
                    'Is_Prmy' => (bool) $item->Is_Prmy,
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading phones: '.$e->getMessage());
            $this->phones = [];
        }
    }

    private function loadEmails()
    {
        try {
            $this->emails = DB::table('admn_cnta_emai_mast')
                ->where('Admn_User_Mast_UIN', $this->contactId)
                ->orderBy('Is_Prmy', 'desc')
                ->get()
                ->map(fn ($item) => [
                    'Emai_Addr' => $item->Emai_Addr,
                    'Emai_Type' => $item->Emai_Type,
                    'Is_Prmy' => (bool) $item->Is_Prmy,
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading emails: '.$e->getMessage());
            $this->emails = [];
        }
    }

    private function loadLandlines()
    {
        try {
            $this->landlines = DB::table('admn_cnta_land_mast')
                ->where('Admn_User_Mast_UIN', $this->contactId)
                ->orderBy('Is_Prmy', 'desc')
                ->get()
                ->map(fn ($item) => [
                    'Land_Numb' => $item->Land_Numb,
                    'Land_Type' => $item->Land_Type,
                    'Cutr_Code' => $item->Cutr_Code,
                    'Is_Prmy' => (bool) $item->Is_Prmy,
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading landlines: '.$e->getMessage());
            $this->landlines = [];
        }
    }

    private function loadAddresses()
    {
        try {
            $this->addresses = DB::table('admn_cnta_addr_mast as a')
                ->leftJoin('admn_pincode_mast as p', 'a.Admn_PinCode_Mast_UIN', '=', 'p.Admn_PinCode_Mast_UIN')
                ->leftJoin('admn_dist_mast as d', 'a.Admn_Dist_Mast_UIN', '=', 'd.Admn_Dist_Mast_UIN')
                ->leftJoin('admn_stat_mast as s', 'a.Admn_Stat_Mast_UIN', '=', 's.Admn_Stat_Mast_UIN')
                ->leftJoin('admn_cutr_mast as c', 'a.Admn_Cutr_Mast_UIN', '=', 'c.Admn_Cutr_Mast_UIN')
                ->where('a.Admn_User_Mast_UIN', $this->contactId)
                ->select(
                    'a.Addr',
                    'a.Loca',
                    'a.Lndm',
                    'a.Is_Prmy',
                    'a.Admn_Addr_Type_Mast_UIN',
                    'p.Code as pincode',
                    'd.Name as district_name',
                    's.Name as state_name',
                    'c.Name as country_name'
                )
                ->orderBy('a.Is_Prmy', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading addresses: '.$e->getMessage());
            $this->addresses = [];
        }
    }

    private function loadBankAccounts()
    {
        try {
            if (! $this->contact) {
                Log::warning('loadBankAccounts: Contact not found');
                $this->bankAccounts = [];

                return;
            }

            $this->bankAccounts = DB::table('admn_user_bank_mast as b')
                ->leftJoin('admn_bank_name as bn', 'b.Bank_Name_UIN', '=', 'bn.Bank_UIN')
                ->where('b.Admn_User_Mast_UIN', $this->contactId)
                ->where('b.Stau', 100201)
                ->select(
                    'b.Admn_User_Bank_Mast_UIN',
                    'b.Bank_Name_UIN',
                    'bn.Bank_Name',
                    'b.Bank_Brnc_Name',
                    'b.Acnt_Type',
                    'b.Acnt_Numb',
                    'b.IFSC_Code',
                    'b.Swift_Code',
                    'b.Prmy'
                )
                ->orderBy('b.Prmy', 'desc')
                ->get()
                ->toArray();

            // Load attachments for each bank account
            foreach ($this->bankAccounts as &$bank) {
                $bank->attachments = DB::table('admn_bank_attachments')
                    ->where('Admn_User_Bank_Mast_UIN', $bank->Admn_User_Bank_Mast_UIN)
                    ->get()
                    ->toArray();
            }

            Log::info('Bank Accounts Loaded:', [
                'contact_id' => $this->contactId,
                'count' => count($this->bankAccounts),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading bank accounts: '.$e->getMessage());
            $this->bankAccounts = [];
        }
    }

    private function loadDocuments()
    {
        try {
            $this->documents = Admn_Docu_Mast::where('Admn_User_Mast_UIN', $this->contactId)
                ->where('Stau', 100201)
                ->orderBy('Prmy', 'desc')
                ->get()
                ->groupBy('Regn_Numb')
                ->map(function ($docGroup) {

                    $firstDoc = $docGroup->first();

                    return [
                        'Docu_Name' => $firstDoc->Docu_Name,
                        'Admn_Docu_Mast_UIN' => $firstDoc->Admn_Docu_Mast_UIN,
                        'Regn_Numb' => $firstDoc->Regn_Numb,
                        'selected_types' => $docGroup->pluck('Admn_Docu_Type_Mast_UIN')->toArray(),
                        'Admn_Cutr_Mast_UIN' => $firstDoc->Admn_Cutr_Mast_UIN,
                        'Auth_Issd' => $firstDoc->Auth_Issd,
                        'Vald_From' => $firstDoc->Vald_From ? $firstDoc->Vald_From->format('Y-m-d') : '',
                        'Vald_Upto' => $firstDoc->Vald_Upto ? $firstDoc->Vald_Upto->format('Y-m-d') : '',
                        'Docu_Atch_Path' => $firstDoc->Docu_Atch_Path,
                        'existing_docs' => $docGroup->pluck('Admn_Docu_Mast_UIN')->toArray(),
                        'Prmy' => (bool) ($firstDoc->Prmy ?? false),
                        'is_dropdown_open' => false,
                    ];
                })
                ->sortByDesc('Prmy')
                ->values()
                ->toArray();

            $this->allDocumentTypes = Admn_Docu_Type_Mast::where('Stau', 100201)->orderBy('Docu_Name')->get();

            Log::info('Documents Loaded:', [
                'contact_id' => $this->contactId,
                'count' => count($this->documents),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading documents: '.$e->getMessage());
            $this->documents = [];
        }
    }

    private function loadDynamicProperties()
    {
        if ($this->contact->Prfx_UIN && ! empty($this->allPrefixes)) {
            $prefix = $this->allPrefixes->firstWhere('Prfx_Name_UIN', $this->contact->Prfx_UIN);
            $this->prefixName = $prefix ? $prefix->Prfx_Name : null;
        }
    }

    private function loadLookupData()
    {
        try {
            $organizationUIN = session('selected_Orga_UIN');
            $this->allTags = Admn_Tag_Mast::query()
                ->where(function ($query) use ($organizationUIN) {
                    $query->where('CrBy', 103);
                    if ($organizationUIN) {
                        $query->orWhere('Admn_Orga_Mast_UIN', $organizationUIN);
                    }
                })
                ->where('Admn_Tag_Mast_UIN', '!=', 12)
                ->where('stau', 100201)  // Only Active Tags
                ->orderBy('Name')
                ->get();
            $this->allPrefixes = $this->loadPrefixes();
            $this->allCountries = Admn_Cutr_Mast::query()
                ->where(function ($q) {
                    $q
                        ->where('Admn_Cutr_Mast_UIN', '<', 100)
                        ->orWhere('Admn_Cutr_Mast_UIN', '>', 110)
                        ->orWhere('Admn_Cutr_Mast_UIN', 101107);
                })
                ->orderBy('Name')
                ->get();
            $this->addressTypes = DB::table('admn_addr_type_mast')
                ->select('Admn_Addr_Type_Mast_UIN', 'Name')
                ->orderBy('Name', 'asc')
                ->get()
                ->map(function ($type) {
                    $type->Name = Str::title($type->Name);

                    return $type;
                });
            $this->allDocumentTypes = Admn_Docu_Type_Mast::where('Stau', 100201)->get();
        } catch (\Exception $e) {
            Log::error('Error loading lookup data: '.$e->getMessage());
        }
    }

    private function loadPrefixes()
    {
        try {
            return Admn_Prfx_Name_Mast::active()->orderBy('Prfx_Name')->get();
        } catch (\Exception $e) {
            return Admn_Prfx_Name_Mast::orderBy('Prfx_Name')->get();
        }
    }

    public function formatDate($date, $format = 'd M Y')
    {
        if (! $date) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return $date;
        }
    }

    public function getDocumentTypeName($typeId)
    {
        $docType = $this->allDocumentTypes->firstWhere('Admn_Docu_Type_Mast_UIN', $typeId);

        return $docType ? $docType->Docu_Name : 'Unknown';
    }

    public function getCountryName($countryUIN)
    {
        if ($countryUIN && $this->allCountries) {
            $country = $this->allCountries->firstWhere('Admn_Cutr_Mast_UIN', $countryUIN);

            return $country ? $country->Name : 'Unknown';
        }

        return 'Unknown';
    }

    public function getAddressTypeName($addressTypeId)
    {
        if ($addressTypeId && $this->addressTypes) {
            if (is_numeric($addressTypeId)) {
                $type = $this->addressTypes->firstWhere('Admn_Addr_Type_Mast_UIN', $addressTypeId);

                return $type ? $type->Name : 'Address';
            }

            return Str::title($addressTypeId);
        }

        return 'Address';
    }

    public function getSocialLinks()
    {
        return $this->contact->getSocialLinks();
    }

    public array $socialHexColors = [
        'website' => '#3B82F6',
        'facebook' => '#1877F2',
        'twitter' => '#000000',
        'linkedin' => '#0A66C2',
        'instagram' => '#E4405F',
        'reddit' => '#FF4500',
        'youtube' => '#FF0000',
        'yahoo' => '#7B0099',
    ];

    public function hasSocialLinks()
    {
        return ! empty($this->contact->getSocialLinks());
    }

    private function loadEducations()
    {
        $this->educations = AdmnUserEducMast::where('Admn_User_Mast_UIN', $this->contactId)
            ->get()
            ->toArray();
    }


    private function loadSkills()
    {
        $this->skills = AdmnUserSkilMast::forContact($this->contactId)
            ->get()
            ->toArray();
    }

    private function loadWorkExperiences()
    {
        $this->workExperiences = AdmnUserWorkMast::forContact($this->contactId)
            ->get()
            ->toArray(); // <--- This converts data to an Array, not an Object
    }

    private function loadContactNotes(): void
    {
        try {
            $this->contactNotes = \App\Models\AdmnCntaNoteMast::forContact($this->contactId)
                ->whereNull('DelOn')
                ->orderByRaw('IsPinned DESC, CrOn DESC')
                ->get()
                ->map(function ($note) {
                    // Lookup user by User_UIN (assuming CrBy column stores user UIN)
                    $user = \App\Models\User::where('User_UIN', $note->CrBy)->first();

                    // Lookup vertical name by Vertical_ID
                    $vertical = \DB::table('tbl_mast_vert')
                        ->select('vertical')
                        ->where('id', $note->Vertical_ID)
                        ->first();

                    return [
                        'id' => $note->Admn_Cnta_Note_Mast_UIN,
                        'Admn_User_Cnta_Nots_UIN' => $note->Admn_Cnta_Note_Mast_UIN,
                        'Note_Detl' => $note->Note_Detl,
                        'CrOn' => $note->CrOn instanceof \Carbon\Carbon ? $note->CrOn : \Carbon\Carbon::parse($note->CrOn),
                        'MoOn' => $note->MoOn
                            ? ($note->MoOn instanceof \Carbon\Carbon ? $note->MoOn : \Carbon\Carbon::parse($note->MoOn))
                            : null,
                        'User_Name' => $user?->User_Name ?? 'Unknown User',
                        'Vertical_Name' => $vertical?->vertical ?? 'Unknown Vertical',
                        'isPinned' => (bool) $note->IsPinned,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('Error loading contact notes: '.$e->getMessage());
            $this->contactNotes = [];
        }
    }

    public function render()
    {
        return view('livewire.contacts.show')->layout('components.layouts.app');
    }
}

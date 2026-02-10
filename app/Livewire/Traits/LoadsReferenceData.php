<?php

namespace App\Livewire\Traits;

use App\Models\Admn_Bank_Name;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Docu_Type_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Tag_Mast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


trait LoadsReferenceData
{

    /**
     * Load active tags (System + Organization specific)
     */
    /**
     * Load active tags (System + Organization specific)
     * If $contactId is provided and the contact has tag 12, include it.
     */
    protected function loadTags($contactId = null)
    {
        $orgUIN = session('selected_Orga_UIN');

        $query = Admn_Tag_Mast::where(
            fn ($q) => $q
                ->where('CrBy', 103) // System Created Tags
                ->when($orgUIN, fn ($query) => $query->orWhere('Admn_Orga_Mast_UIN', $orgUIN))
        )
            ->where('stau', self::STATUS_ACTIVE);

        // By default, exclude tag 12
        $includeTag12 = false;
        if ($contactId) {
            // Check if contact has tag 12
            $hasTag12 = \DB::table('admn_cnta_tag_mast')
                ->where('Admn_User_Mast_UIN', $contactId)
                ->where('Admn_Tag_Mast_UIN', 12)
                ->exists();
            if ($hasTag12) {
                $includeTag12 = true;
            }
        }
        if (! $includeTag12) {
            $query->where('Admn_Tag_Mast_UIN', '!=', 12);
        }

        return $query->orderBy('Name')->get();
    }

    /**
     * Load prefixes (Mr, Mrs, Dr, etc.)
     */
    protected function loadPrefixes()
    {
        try {
            return Admn_Prfx_Name_Mast::where('Stau_UIN', self::STATUS_ACTIVE)
                ->orderBy('Prfx_Name')
                ->get();
        } catch (\Throwable $e) {
            // fallback safety
            return Admn_Prfx_Name_Mast::orderBy('Prfx_Name')->get();
        }
    }

    /**
     * Load countries (Active only)
     */
    protected function loadCountries()
    {
        return Admn_Cutr_Mast::select(
            'Admn_Cutr_Mast_UIN',
            'Name',
            'Code',
            'Phon_Code',
            'MoNo_Digt'
        )
            ->where('Stau_UIN', self::STATUS_ACTIVE)
            ->orderBy('Name')
            ->get();
    }

    /**
     * Load address types (Individual)
     */
    protected function loadAddressTypes()
    {
        return DB::table('admn_addr_type_mast')
            ->select('Admn_Addr_Type_Mast_UIN', 'Name')
            ->where('Addr_Type', 'i')
            ->orderBy('Name')
            ->get()
            ->map(fn ($type) => tap($type, fn ($t) => $t->Name = Str::title($t->Name))
            );
    }

    /**
     * Load address types (Business)
     */
    protected function loadBusinessAddressTypes()
    {
        return DB::table('admn_addr_type_mast')
            ->select('Admn_Addr_Type_Mast_UIN', 'Name')
            ->where('Addr_Type', 'b')
            ->orderBy('Name')
            ->get()
            ->map(fn ($type) => tap($type, fn ($t) => $t->Name = Str::title($t->Name))
            );
    }

    /**
     * Load bank options
     */
    protected function loadBankOptions()
    {
        return Admn_Bank_Name::select('Bank_UIN', 'Bank_Name')
            ->where('Stau_UIN', self::STATUS_ACTIVE)
            ->orderBy('Bank_Name')
            ->get();
    }

    /**
     * Load active document types
     */
    protected function loadDocumentTypes()
    {
        return Admn_Docu_Type_Mast::where('Stau', self::STATUS_ACTIVE)
            ->orderBy('Docu_Name')
            ->get();
    }

    /**
     * Unified loader for common reference data
     * (used in mount())
     */
    protected function loadCommonReferenceData(): void
    {
        $this->allTags = $this->loadTags();
        $this->allPrefixes = $this->loadPrefixes();
        $this->allCountries = $this->loadCountries();
        $this->addressTypes = $this->loadAddressTypes();
        $this->BaddressTypes = $this->loadBusinessAddressTypes();
        $this->bankOptions = $this->loadBankOptions();
        $this->allDocumentTypes = $this->loadDocumentTypes();
    }

    /**
     * Legacy method for backward compatibility
     * (can be removed if not used elsewhere)
     */
    protected function loadReferenceData(): void
    {
        $this->loadCommonReferenceData();
    }
}

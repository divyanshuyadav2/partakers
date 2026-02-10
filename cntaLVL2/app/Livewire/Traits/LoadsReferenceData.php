<?php

namespace App\Livewire\Traits;

use App\Models\{
    Admn_Prfx_Name_Mast,
    Admn_Cutr_Mast,
    Admn_Bank_Name,
    Admn_Docu_Type_Mast
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait LoadsReferenceData
{
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
     * Load countries (with India priority logic preserved)
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
            ->where(
                fn ($q) => $q
                    ->where('Admn_Cutr_Mast_UIN', '<', 100)
                    ->orWhere('Admn_Cutr_Mast_UIN', '>', 110)
                    ->orWhere('Admn_Cutr_Mast_UIN', self::COUNTRY_INDIA_UIN)
            )
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
            ->map(fn ($type) =>
                tap($type, fn ($t) => $t->Name = Str::title($t->Name))
            );
    }

    /**
     * Load bank options
     */
    protected function loadBankOptions()
    {
        return Admn_Bank_Name::select('Bank_UIN', 'Bank_Name')
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
        $this->allPrefixes       = $this->loadPrefixes();
        $this->allCountries      = $this->loadCountries();
        $this->addressTypes      = $this->loadAddressTypes();
        $this->bankOptions       = $this->loadBankOptions();
        $this->allDocumentTypes  = $this->loadDocumentTypes();
    }
}

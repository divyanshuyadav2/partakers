<?php

namespace App\Livewire\Traits;

use App\Models\{
    Admn_Cutr_Mast,
    Admn_Stat_Mast,
    Admn_Dist_Mast,
    Admn_PinCode_Mast
};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

trait HasAddressCascade
{
    /* -----------------------------------------------------------------
     |  Address change handler (Country → State → District → Pincode)
     |-----------------------------------------------------------------*/

    public function updatedAddresses($value, $key): void
    {
        [$index, $field] = explode('.', $key) + [null, null];

        if (!isset($this->addresses[$index])) {
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
                $addr['pincodesForDropdown']  = [];

                if (!empty($addr['Is_Prmy']) && method_exists($this, 'syncPhoneCodeWithPrimaryAddress')) {
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

                $query = Admn_PinCode_Mast::where('Code', 'like', $value . '%')
                    ->where('Stau_UIN', 100201); // status Active

                if (!empty($addr['Admn_Dist_Mast_UIN'])) {
                    $query->where('Admn_Dist_Mast_UIN', $addr['Admn_Dist_Mast_UIN']);
                } elseif (!empty($addr['Admn_Stat_Mast_UIN'])) {
                    $query->whereHas('district', fn ($q) =>
                        $q->where('Admn_Stat_Mast_UIN', $addr['Admn_Stat_Mast_UIN'])
                    );
                } elseif (!empty($addr['Admn_Cutr_Mast_UIN'])) {
                    $query->whereHas('district.state', fn ($q) =>
                        $q->where('Admn_Cutr_Mast_UIN', $addr['Admn_Cutr_Mast_UIN'])
                    );
                }

                $addr['pincodeResults'] = $query->take(10)->get()->toArray();
                break;
        }
    }

    /* -----------------------------------------------------------------
     |  Pincode selection
     |-----------------------------------------------------------------*/

    public function selectPincode($index, $pincodeUIN): void
    {
        $pincode = Admn_PinCode_Mast::with('district.state.country')
            ->where('Stau_UIN', 100201) // status Active
            ->where('Admn_PinCode_Mast_UIN', $pincodeUIN)
            ->first();

        if (!$pincode || !isset($this->addresses[$index])) {
            return;
        }

        $addr = &$this->addresses[$index];

        $addr['pincodeSearch']         = $pincode->Code;
        $addr['Admn_PinCode_Mast_UIN'] = $pincode->Admn_PinCode_Mast_UIN;
        $addr['Admn_Dist_Mast_UIN']    = $pincode->district?->Admn_Dist_Mast_UIN;
        $addr['Admn_Stat_Mast_UIN']    = $pincode->district?->state?->Admn_Stat_Mast_UIN;
        $addr['Admn_Cutr_Mast_UIN']    = $pincode->district?->state?->country?->Admn_Cutr_Mast_UIN;

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

    /* -----------------------------------------------------------------
     |  Address hydration (Edit / default add)
     |-----------------------------------------------------------------*/

    private function hydrateAddressFields($data, $id = null): array
    {
        $countryId = $data['Admn_Cutr_Mast_UIN'] ?? null;
        $stateId   = $data['Admn_Stat_Mast_UIN'] ?? null;
        $distId    = $data['Admn_Dist_Mast_UIN'] ?? null;

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

    /* -----------------------------------------------------------------
     |  Countries collection helper
     |-----------------------------------------------------------------*/

    public function getAllCountriesCollectionProperty(): Collection
    {
        return collect($this->allCountries ?? []);
    }
}

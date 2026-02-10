<?php

namespace App\Livewire\Organizations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Select Organization')]
class Select extends Component
{
    public ?int $selectedOrgaUIN = null;

    public function mount()
    {
        if (!session('api_authenticated')) {
            return redirect()->route('api.auth.form');
        }

        $this->selectedOrgaUIN = session('selected_Orga_UIN');
    }

    #[Computed]
    public function userOrganizations()
    {
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN) {
            Log::warning('No authenticated user UIN found in session');
            return collect();
        }

        // checking user exists in admn_user_logi_mast
        $userExists = DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->exists();

        if (!$userExists) {
            Log::error('User not found in admn_user_logi_mast', ['User_UIN' => $userUIN]);
            return collect();
        }

        // Get user associations from user_orga_rela table
        $userAssociations = DB::table('admn_user_orga_rela')
            ->where('User_UIN', $userUIN)
            ->where('Stau_UIN', 100201)  // Only active associations
            ->select('User_Assc_UIN', 'Orga_UIN', 'User_UIN')
            ->get();

        Log::info('User organizations query', [
            'User_UIN' => $userUIN,
            'associations_found' => $userAssociations->count(),
            'associations' => $userAssociations->toArray()
        ]);

        if ($userAssociations->isEmpty()) {
            Log::warning('No associations found for user', ['User_UIN' => $userUIN]);
            return collect();
        }

        // Get unique organization UIDs
        $orgaUIDs = $userAssociations->pluck('Orga_UIN')->unique()->values();

        // Get organization names from admn_orga_mast
        $organizations = DB::table('admn_orga_mast')
            ->whereIn('Orga_UIN', $orgaUIDs)
            ->select('Orga_UIN', 'Orga_Name')
            ->get();

        Log::info('Organizations retrieved for user', [
            'User_UIN' => $userUIN,
            'organizations_count' => $organizations->count(),
            'organizations' => $organizations->toArray()
        ]);

        return $organizations;
    }

    #[Computed]
    public function currentUser()
    {
        $userUIN = session('authenticated_user_uin');

        if (!$userUIN) {
            return null;
        }

        return DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->select('User_UIN', 'User_Name', 'Prmy_Emai')
            ->first();
    }

    public function updatedSelectedOrgaUIN($value)
    {
        if ($value) {
            $this->selectOrganization($value);
        }
    }

    public function selectOrganization(int $orgaUIN): void
    {
        $userUIN = session('authenticated_user_uin');

        // Verify user exists in admn_user_logi_mast
        $userExists = DB::table('admn_user_logi_mast')
            ->where('User_UIN', $userUIN)
            ->exists();

        if (!$userExists) {
            Log::error('User verification failed during organization selection', ['User_UIN' => $userUIN]);
            session()->flash('error', 'User verification failed');
            return;
        }

        //  Verify user has access to this organization through user_orga_rela
        $hasAccess = DB::table('admn_user_orga_rela')
            ->where('User_UIN', $userUIN)
            ->where('Orga_UIN', $orgaUIN)
            ->where('Stau_UIN', 100201)  // Only active associations
            ->exists();

        if (!$hasAccess) {
            Log::warning('Access denied to organization', [
                'User_UIN' => $userUIN,
                'Orga_UIN' => $orgaUIN
            ]);
            session()->flash('error', 'Access denied to selected organization');
            return;
        }

        //  Get organization details from admn_orga_mast
        $organization = DB::table('admn_orga_mast')
            ->where('Orga_UIN', $orgaUIN)
            ->select('Orga_UIN', 'Orga_Name')
            ->first();

        if (!$organization) {
            Log::error('Organization not found in admn_orga_mast', ['Orga_UIN' => $orgaUIN]);
            session()->flash('error', 'Organization details not found');
            return;
        }

        // Store selected organization in session
        session(['selected_Orga_UIN' => $orgaUIN]);
        $this->selectedOrgaUIN = $orgaUIN;

        $this->redirect(route('contacts.index'));
    }
}

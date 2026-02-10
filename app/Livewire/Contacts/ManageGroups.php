<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\HasMaxConstants;
use App\Models\Admn_Grup_Mast;
use App\Models\Admn_User_Mast;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ManageGroups extends Component
{
    use HasMaxConstants;
    public bool $showManageGroupsModal = false;
    public bool $showCreateEditModal = false;
    public bool $showAssignModal = false;
    public bool $showViewAssignedModal = false;
    // ============================================
    // CREATE/EDIT FORM PROPERTIES
    // ============================================
    public ?int $editingGroupId = null;
    public string $name = '';
    public ?int $parent_grup_uin = null;
    // ============================================
    // ASSIGN CONTACTS PROPERTIES
    // ============================================
    public ?Admn_Grup_Mast $groupForAssignment = null;
    public array $selectedContacts = [];
    public string $searchUnassigned = '';
    // ============================================
    // VIEW ASSIGNED CONTACTS PROPERTIES
    // ============================================
    public ?Admn_Grup_Mast $groupForViewing = null;
    public string $searchAssigned = '';
    public array $contactsToUnassign = [];

    // Note: $allGroups and $parentGroupOptions removed from public properties
    // to prevent hydration issues. They are now #[Computed] methods below.

    /**
     * Entry point: Opens the main "Manage Groups" modal
     */
    #[On('openGroupManager')]
    public function openManageGroupsModal()
    {
        // No need to manually call loadGroups(), the computed property handles it
        $this->showManageGroupsModal = true;
    }

    /**
     * Computed Property: Loads all groups with counts.
     * Because this is computed, Livewire ensures 'users_count' is
     * NEVER lost during re-renders.
     */
    #[Computed]
    public function allGroups()
    {
        $organizationUin = session('selected_Orga_UIN');

        return Admn_Grup_Mast::where('Admn_Orga_Mast_UIN', $organizationUin)
            ->with(['parent', 'users'])
            ->withCount('users')  // This count will now persist correctly
            ->orderBy('Name')
            ->get();
    }

    /**
     * Computed Property: Loads parent group options
     */
    #[Computed]
    public function parentGroupOptions()
    {
        $organizationUin = session('selected_Orga_UIN');

        return Admn_Grup_Mast::where('Admn_Orga_Mast_UIN', $organizationUin)
            ->orWhere('Admn_Grup_Mast_UIN', 110)  // The default 'Start' group
            ->orderBy('Name')
            ->get();
    }

    /**
     * Refresh triggers a re-render, which automatically re-runs the computed properties
     */
    public function refreshGroupsCount()
    {
        unset($this->allGroups);  // Clear computed cache to force refresh
    }

    // ============================================
    // CREATE/EDIT GROUP METHODS
    // ============================================

    public function openCreateModal()
    {
        $this->resetForm();
        $this->parent_grup_uin = 110;
        $this->showCreateEditModal = true;
    }

    public function openEditModal(int $groupId)
    {
        // Direct lookup is fine here as we need specific fields
        $group = Admn_Grup_Mast::findOrFail($groupId);
        $this->editingGroupId = $group->Admn_Grup_Mast_UIN;
        $this->name = $group->Name;
        $this->parent_grup_uin = $group->Parent_Grup_UIN;
        $this->showCreateEditModal = true;
    }

    public function saveGroup()
    {
        $organizationUin = session('selected_Orga_UIN');
        $authUin = session('authenticated_user_uin');

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:15',
                Rule::notIn(['Start', 'start']),
                Rule::unique('admn_grup_mast', 'Name')
                    ->where(function ($query) use ($organizationUin) {
                        return $query->where('Admn_Orga_Mast_UIN', $organizationUin);
                    })
                    ->ignore($this->editingGroupId, 'Admn_Grup_Mast_UIN'),
            ],
            'parent_grup_uin' => 'required|exists:admn_grup_mast,Admn_Grup_Mast_UIN',
        ]);

        $data = [
            'Name' => $this->name,
            'Parent_Grup_UIN' => $this->parent_grup_uin,
            'Admn_Orga_Mast_UIN' => $organizationUin,
            'MoBy' => $authUin,
        ];

        if ($this->editingGroupId) {
            Admn_Grup_Mast::find($this->editingGroupId)->update($data);
            session()->flash('message', 'Group updated successfully.');
        } else {
            $data['Admn_Grup_Mast_UIN'] = (Admn_Grup_Mast::max('Admn_Grup_Mast_UIN') ?? 110) + 1;
            $data['CrBy'] = $authUin;
            Admn_Grup_Mast::create($data);
            session()->flash('message', 'Group created successfully.');
        }

        // Clear computed property cache to update the list immediately
        unset($this->allGroups);
        unset($this->parentGroupOptions);

        $this->closeAllModals();
        $this->dispatch('refreshContacts');
    }

    public function canDeleteGroup(int $groupId): bool
    {
        // We query directly here to ensure check is accurate against DB
        $group = Admn_Grup_Mast::find($groupId);

        if (!$group)
            return false;
        if ($group->users()->count() > 0)
            return false;
        if (Admn_Grup_Mast::where('Parent_Grup_UIN', $groupId)->exists())
            return false;

        return true;
    }

    public function confirmDelete(int $groupId)
    {
        Admn_Grup_Mast::where('Admn_Grup_Mast_UIN', $groupId)->delete();
        session()->flash('message', 'Group deleted successfully.');

        // Clear computed cache
        unset($this->allGroups);
        unset($this->parentGroupOptions);
    }

    // ============================================
    // ASSIGN CONTACTS METHODS
    // ============================================

    public function openAssignModal(int $groupId)
    {
        $this->groupForAssignment = Admn_Grup_Mast::findOrFail($groupId);
        $this->selectedContacts = [];
        $this->searchUnassigned = '';
        $this->showAssignModal = true;
    }

    public function assignContacts()
    {
        if (empty($this->selectedContacts) || !$this->groupForAssignment) {
            return;
        }

        Admn_User_Mast::whereIn('Admn_User_Mast_UIN', $this->selectedContacts)
            ->update(['Admn_Grup_Mast_UIN' => $this->groupForAssignment->Admn_Grup_Mast_UIN]);

        $contactCount = count($this->selectedContacts);
        $this->showAssignModal = false;

        // Clear computed cache so counts update on the main list
        unset($this->allGroups);

        $this->dispatch('refreshContacts');
        session()->flash('message', "{$contactCount} contact(s) assigned to \"{$this->groupForAssignment->Name}\" successfully.");
    }

    #[Computed]
    public function unassignedContacts()
    {
        $organizationUin = session('selected_Orga_UIN');

        return Admn_User_Mast::where('Admn_Orga_Mast_UIN', $organizationUin)
            ->where('Is_Actv', self::STATUS_ACTIVE)
            ->whereNull('Admn_Grup_Mast_UIN')
            ->when($this->searchUnassigned, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery
                        ->where('FaNm', 'like', '%' . $this->searchUnassigned . '%')
                        ->orWhere('LaNm', 'like', '%' . $this->searchUnassigned . '%')
                        ->orWhere('Comp_Name', 'like', '%' . $this->searchUnassigned . '%');
                });
            })
            ->with(['phones' => fn($q) => $q->orderBy('Is_Prmy', 'desc')])
            ->orderBy('FaNm')
            ->take(100)
            ->get();
    }

    // ============================================
    // VIEW ASSIGNED CONTACTS METHODS
    // ============================================

    public function openViewAssignedModal(int $groupId)
    {
        $this->groupForViewing = Admn_Grup_Mast::findOrFail($groupId);
        $this->searchAssigned = '';
        $this->contactsToUnassign = [];
        $this->showViewAssignedModal = true;
    }

    #[Computed]
    public function assignedContacts()
    {
        if (!$this->groupForViewing) {
            return collect();
        }

        return Admn_User_Mast::where('Admn_Grup_Mast_UIN', $this->groupForViewing->Admn_Grup_Mast_UIN)
            ->where('Is_Actv', self::STATUS_ACTIVE)
            ->when($this->searchAssigned, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery
                        ->where('FaNm', 'like', '%' . $this->searchAssigned . '%')
                        ->orWhere('LaNm', 'like', '%' . $this->searchAssigned . '%')
                        ->orWhere('Comp_Name', 'like', '%' . $this->searchAssigned . '%');
                });
            })
            ->with([
                'phones' => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
                'emails' => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
            ])
            ->orderBy('FaNm')
            ->get();
    }

    public function unassignContacts()
    {
        if (empty($this->contactsToUnassign) || !$this->groupForViewing) {
            return;
        }

        $contactCount = count($this->contactsToUnassign);

        Admn_User_Mast::whereIn('Admn_User_Mast_UIN', $this->contactsToUnassign)
            ->update(['Admn_Grup_Mast_UIN' => null]);

        $this->contactsToUnassign = [];

        // Clear computed cache so counts update on the main list
        unset($this->allGroups);

        $this->dispatch('refreshContacts');
        session()->flash('message', "{$contactCount} contact(s) unassigned from \"{$this->groupForViewing->Name}\" successfully.");
    }

    // ============================================
    // MODAL MANAGEMENT METHODS
    // ============================================

    public function closeModal()
    {
        $this->showCreateEditModal = false;
        $this->showAssignModal = false;
        $this->showViewAssignedModal = false;
    }

    public function closeAllModals()
    {
        $this->showManageGroupsModal = false;
        $this->showCreateEditModal = false;
        $this->showAssignModal = false;
        $this->showViewAssignedModal = false;
        $this->resetForm();
        $this->selectedContacts = [];
        $this->searchUnassigned = '';
        $this->contactsToUnassign = [];
        $this->searchAssigned = '';
    }

    private function resetForm()
    {
        $this->editingGroupId = null;
        $this->name = '';
        $this->parent_grup_uin = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.contacts.manage-groups');
    }
}

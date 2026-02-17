<?php

namespace App\Livewire\Contacts;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\AdmnCntaNoteComnt;

class ManageIssues extends Component
{
    public bool $showIssueModal = false;
    public bool $showCreateEditModal = false;
    public bool $showEditCategoryModal = false;

    public ?int $editingId = null;

    public string $parent = 'new';        // For category selection
    public string $newParent = '';         // For new category name
    public string $note = '';              // For comment text
    public string $originalCategory = ''; // Store original category for edit

    // For Edit Category Modal
    public string $selectedCategory = '';
    public string $newCategoryName = '';

    // ============================================================
    // SYSTEM DETECTION HELPER
    // A record is system-generated if:
    //   - Orga_UIN IS NULL  → no organization assigned
    //   - CrBy = 101        → created by system user
    // ============================================================

    /**
     * Entry point: Opens the main "Manage Issues" modal
     */
    #[On('openIssueManager')]
    public function openModal(): void
    {
        $this->showIssueModal = true;
    }

    // ============================================================
    // COMPUTED PROPERTIES
    // ============================================================

    /**
     * All active issues - ONLY organization-created (NOT system)
     * Excludes: Orga_UIN = NULL  OR  CrBy = 101
     */
    #[Computed]
    public function allIssues()
    {
        $org = session('selected_Orga_UIN');

        return AdmnCntaNoteComnt::where('Stau_UIN', 100201)
            ->where('Orga_UIN', $org)
            ->where('CrBy', '!=', 101)
            ->orderBy('Category')
            ->orderBy('Comnt_Text')
            ->get();
    }

    /**
     * Unique categories for create/edit dropdown
     * Shows BOTH system and organization categories
     */
    #[Computed]
    public function parents()
    {
        $org = session('selected_Orga_UIN');

        return AdmnCntaNoteComnt::where('Stau_UIN', 100201)
            ->where(function ($q) use ($org) {
                $q->whereNull('Orga_UIN')     // System: no org
                  ->orWhere('CrBy', 101)       // System: created by system user
                  ->orWhere('Orga_UIN', $org); // Organization created
            })
            ->pluck('Category')
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    /**
     * Categories created ONLY by organization (used in Edit Category dropdown)
     * Excludes any category that has even ONE system note
     */
    #[Computed]
    public function organizationCategories()
    {
        $org = session('selected_Orga_UIN');

        // Collect all system category names (Orga_UIN NULL or CrBy = 101)
        $systemCategories = AdmnCntaNoteComnt::where('Stau_UIN', 100201)
            ->where(function ($q) {
                $q->whereNull('Orga_UIN')
                  ->orWhere('CrBy', 101);
            })
            ->pluck('Category')
            ->unique()
            ->toArray();

        // Return org categories that are NOT in system category list
        return AdmnCntaNoteComnt::where('Stau_UIN', 100201)
            ->where('Orga_UIN', $org)
            ->where('CrBy', '!=', 101)
            ->whereNotIn('Category', $systemCategories)
            ->pluck('Category')
            ->unique()
            ->filter()
            ->sort()
            ->values();
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Check if a category is truly organization-created (not system).
     * A category is system if ANY of its notes have Orga_UIN = NULL or CrBy = 101.
     */
    public function isCategoryEditable(string $categoryName): bool
    {
        $org = session('selected_Orga_UIN');

        // If ANY note in this category is system-generated → not editable
        $isSystemCategory = AdmnCntaNoteComnt::where('Stau_UIN', 100201)
            ->where('Category', $categoryName)
            ->where(function ($q) {
                $q->whereNull('Orga_UIN')
                  ->orWhere('CrBy', 101);
            })
            ->exists();

        if ($isSystemCategory) {
            return false;
        }

        // Must have at least one org-created note
        return AdmnCntaNoteComnt::where('Stau_UIN', 100201)
            ->where('Category', $categoryName)
            ->where('Orga_UIN', $org)
            ->where('CrBy', '!=', 101)
            ->exists();
    }

    /**
     * Check if a category name already exists (system or org).
     *
     * @param string      $categoryName       The name to check
     * @param int|null    $excludeId          Exclude a specific record UIN
     * @param string|null $excludeCategoryName Exclude a specific category name (for rename)
     */
    private function categoryExists(
        string $categoryName,
        ?int $excludeId = null,
        ?string $excludeCategoryName = null
    ): bool {
        $org = session('selected_Orga_UIN');

        $query = AdmnCntaNoteComnt::where('Stau_UIN', 100201)
            ->where('Category', trim($categoryName))
            ->where(function ($q) use ($org) {
                $q->whereNull('Orga_UIN')
                  ->orWhere('CrBy', 101)
                  ->orWhere('Orga_UIN', $org);
            });

        if ($excludeId) {
            $query->where('Admn_Cnta_Note_Comnt_UIN', '!=', $excludeId);
        }

        if ($excludeCategoryName) {
            $query->where('Category', '!=', $excludeCategoryName);
        }

        return $query->exists();
    }

    /**
     * Can edit if created by this organization AND not by system (CrBy != 101)
     */
    public function canEdit($issue): bool
    {
        $org = session('selected_Orga_UIN');

        return !is_null($issue->Orga_UIN)
            && $issue->Orga_UIN == $org
            && $issue->CrBy != 101;
    }

    /**
     * Can delete if created by this organization AND not by system (CrBy != 101)
     */
    public function canDelete($issue): bool
    {
        $org = session('selected_Orga_UIN');

        if (is_null($issue->Orga_UIN) || $issue->Orga_UIN != $org) {
            return false;
        }

        if ($issue->CrBy == 101) {
            return false;
        }

        return true;
    }

    // ============================================================
    // OPEN MODALS
    // ============================================================

    public function openCreate(): void
    {
        $this->resetForm();
        $this->parent = 'new';
        $this->showCreateEditModal = true;
    }

    public function openEdit(int $id): void
    {
        $issue = AdmnCntaNoteComnt::findOrFail($id);
        $org   = session('selected_Orga_UIN');

        if (!$this->canEdit($issue)) {
            session()->flash('error', 'You cannot edit this note.');
            return;
        }

        $this->editingId        = $issue->Admn_Cnta_Note_Comnt_UIN;
        $this->parent           = $issue->Category;
        $this->originalCategory = $issue->Category;
        $this->note             = $issue->Comnt_Text;

        $this->showCreateEditModal = true;
    }

    public function openEditCategory(): void
    {
        $this->resetCategoryForm();
        $this->showEditCategoryModal = true;
    }

    // ============================================================
    // SAVE METHODS
    // ============================================================

    /**
     * Save (Create or Update) a note/comment
     */
    public function save(): void
    {
        $org  = session('selected_Orga_UIN');
        $auth = session('authenticated_user_uin');

        // Determine final category
        $finalCategory = $this->parent === 'new'
            ? trim($this->newParent)
            : $this->parent;

        // --- Validation ---
        if (empty($finalCategory)) {
            session()->flash('error', 'Please select or enter a category.');
            return;
        }

        if (empty(trim($this->note))) {
            session()->flash('error', 'Comment is required.');
            return;
        }

        // If user chose "+ New Category", check for duplicates
        if ($this->parent === 'new') {
            if ($this->categoryExists($finalCategory, $this->editingId)) {
                session()->flash('error', 'Category "' . $finalCategory . '" already exists. Please select from dropdown or enter a different name.');
                return;
            }
        }

        // --- Update ---
        if ($this->editingId) {
            $issue = AdmnCntaNoteComnt::findOrFail($this->editingId);

            if (!$this->canEdit($issue)) {
                session()->flash('error', 'You cannot edit this issue.');
                return;
            }

            // Extra check if switching to a new category name
            if ($this->parent === 'new' && $this->categoryExists($finalCategory, $this->editingId)) {
                session()->flash('error', 'Category "' . $finalCategory . '" already exists. Please select from dropdown or enter a different name.');
                return;
            }

            $issue->update([
                'Category'   => $finalCategory,
                'Comnt_Text' => trim($this->note),
                'MoOn'       => now(),
            ]);

            session()->flash('message', 'Note updated successfully.');

        // --- Create ---
        } else {
            $maxUin = AdmnCntaNoteComnt::max('Admn_Cnta_Note_Comnt_UIN') ?? 0;
            $newUin = ($maxUin < 500000000000) ? 500000000000 : $maxUin + 1;

            AdmnCntaNoteComnt::create([
                'Admn_Cnta_Note_Comnt_UIN' => $newUin,
                'Category'                 => $finalCategory,
                'Comnt_Text'               => trim($this->note),
                'Stau_UIN'                 => 100201,
                'Orga_UIN'                 => $org,
                'CrBy'                     => $auth, // Org user — NOT 101
                'CrOn'                     => now(),
                'MoOn'                     => now(),
            ]);

            session()->flash('message', 'Note created successfully.');
        }

        $this->clearCache();
        $this->dispatch('refreshQuickComments');
        $this->closeModal();
    }

    /**
     * Rename a category (all notes under it get updated)
     */
    public function saveCategory(): void
    {
        $org = session('selected_Orga_UIN');

        // --- Validation ---
        if (empty($this->selectedCategory)) {
            session()->flash('category_error', 'Please select a category to rename.');
            return;
        }

        if (empty(trim($this->newCategoryName))) {
            session()->flash('category_error', 'New category name cannot be empty.');
            return;
        }

        if (trim($this->newCategoryName) === $this->selectedCategory) {
            session()->flash('category_error', 'New category name is the same as the current name.');
            return;
        }

        // Check duplicate — exclude the current category name from the check
        if ($this->categoryExists($this->newCategoryName, null, $this->selectedCategory)) {
            session()->flash('category_error', 'Category "' . $this->newCategoryName . '" already exists. Please choose a different name.');
            return;
        }

        // Make sure it's truly an org category (not system)
        if (!$this->isCategoryEditable($this->selectedCategory)) {
            session()->flash('category_error', 'You cannot rename system categories.');
            return;
        }

        // Update all matching org notes
        $updated = AdmnCntaNoteComnt::where('Stau_UIN', 100201)
            ->where('Orga_UIN', $org)
            ->where('CrBy', '!=', 101)          // Extra safety: never touch system rows
            ->where('Category', $this->selectedCategory)
            ->update([
                'Category' => trim($this->newCategoryName),
                'MoOn'     => now(),
            ]);

        $this->clearCache();
        $this->dispatch('refreshQuickComments');

        session()->flash('message', "Category renamed successfully. {$updated} note(s) updated.");
        $this->closeCategoryModal();
    }

    /**
     * Delete a note (soft delete → set status inactive)
     */
    public function delete(int $id): void
    {
        $issue = AdmnCntaNoteComnt::findOrFail($id);

        if (!$this->canDelete($issue)) {
            session()->flash('error', 'You cannot delete this note. It may be system-created or not owned by your organization.');
            return;
        }

        $issue->update(['Stau_UIN' => 100202]);

        $this->clearCache();
        $this->dispatch('refreshQuickComments');

        session()->flash('message', 'Note deleted successfully.');
    }

    // ============================================================
    // CLOSE MODALS
    // ============================================================

    public function closeModal(): void
    {
        $this->showCreateEditModal = false;
        $this->resetForm();
    }

    public function closeCategoryModal(): void
    {
        $this->showEditCategoryModal = false;
        $this->resetCategoryForm();
    }

    public function closeAllModals(): void
    {
        $this->showIssueModal        = false;
        $this->showCreateEditModal   = false;
        $this->showEditCategoryModal = false;
        $this->resetForm();
        $this->resetCategoryForm();
    }

    // ============================================================
    // RESET HELPERS
    // ============================================================

    private function resetForm(): void
    {
        $this->editingId        = null;
        $this->parent           = 'new';
        $this->newParent        = '';
        $this->note             = '';
        $this->originalCategory = '';
    }

    private function resetCategoryForm(): void
    {
        $this->selectedCategory = '';
        $this->newCategoryName  = '';
    }

    /**
     * Clear all computed property caches
     */
    private function clearCache(): void
    {
        unset($this->allIssues);
        unset($this->parents);
        unset($this->organizationCategories);
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        return view('livewire.contacts.manage-issues');
    }
}
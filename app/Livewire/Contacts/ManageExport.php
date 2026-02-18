<?php

namespace App\Livewire\Contacts;

use Livewire\Attributes\On;
use Livewire\Component;

// NOTE: All model imports (Admn_User_Mast, Admn_Tag_Mast, etc.)
// are used in ContactExportController, NOT here.
// ManageExport only handles the modal UI + column selection.

class ManageExport extends Component
{
    public bool $showExportModal = false;

    public array $columns = [
        'name_prefix'     => ['label' => 'Name Prefix',           'checked' => true],
        'gender'          => ['label' => 'Gender',                'checked' => true],
        'first_name'      => ['label' => 'First Name',            'checked' => true],
        'middle_name'     => ['label' => 'Middle Name',           'checked' => false],
        'last_name'       => ['label' => 'Last Name',             'checked' => true],
        'birthday'        => ['label' => 'Birthday (DD/MM/YYYY)', 'checked' => false],
        'self_employed'   => ['label' => 'Self Employed',         'checked' => false],
        'company_name'    => ['label' => 'Company Name',          'checked' => true],
        'designation'     => ['label' => 'Designation',           'checked' => true],
        'phone1_label'    => ['label' => 'Phone 1 Label',         'checked' => true],
        'phone1_code'     => ['label' => 'Phone 1 Country Code',  'checked' => false],
        'phone1_number'   => ['label' => 'Phone 1 Number',        'checked' => true],
        'phone2_label'    => ['label' => 'Phone 2 Label',         'checked' => false],
        'phone2_code'     => ['label' => 'Phone 2 Country Code',  'checked' => false],
        'phone2_number'   => ['label' => 'Phone 2 Number',        'checked' => false],
        'phone3_label'    => ['label' => 'Phone 3 Label',         'checked' => false],
        'phone3_code'     => ['label' => 'Phone 3 Country Code',  'checked' => false],
        'phone3_number'   => ['label' => 'Phone 3 Number',        'checked' => false],
        'email1'          => ['label' => 'Email 1',               'checked' => true],
        'email2'          => ['label' => 'Email 2',               'checked' => false],
        'email3'          => ['label' => 'Email 3',               'checked' => false],
        'primary_address' => ['label' => 'Primary Address',       'checked' => true],
        'tags'            => ['label' => 'Tags',                  'checked' => true],
        'website'         => ['label' => 'Website',               'checked' => false],
        'facebook'        => ['label' => 'Facebook',              'checked' => false],
        'twitter'         => ['label' => 'Twitter',               'checked' => false],
        'linkedin'        => ['label' => 'LinkedIn',              'checked' => false],
        'instagram'       => ['label' => 'Instagram',             'checked' => false],
        'notes'           => ['label' => 'Notes',                 'checked' => false],
    ];

    // ============================================================
    // OPEN / CLOSE
    // ============================================================

    #[On('openExportModal')]
    public function openModal(): void
    {
        $this->showExportModal = true;
    }

    public function closeModal(): void
    {
        $this->showExportModal = false;
    }

    // ============================================================
    // SELECT / DESELECT ALL
    // ============================================================

    public function selectAll(): void
    {
        foreach ($this->columns as $key => $col) {
            $this->columns[$key]['checked'] = true;
        }
    }

    public function deselectAll(): void
    {
        foreach ($this->columns as $key => $col) {
            $this->columns[$key]['checked'] = false;
        }
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function getSelectedCount(): int
    {
        return collect($this->columns)->filter(fn($col) => $col['checked'])->count();
    }

    // ============================================================
    // EXPORT
    // Livewire cannot stream files directly (response gets swallowed
    // by Livewire's JSON AJAX handler).
    //
    // Flow:
    //   1. Save selected columns to session
    //   2. Redirect to /contacts/export/csv
    //   3. ContactExportController reads session + streams CSV
    // ============================================================

    public function exportCsv(): void
    {
        $selectedColumns = collect($this->columns)
            ->filter(fn($col) => $col['checked'])
            ->keys()
            ->toArray();

        if (empty($selectedColumns)) {
            session()->flash('export_error', 'Please select at least one column to export.');
            return;
        }

        // Store in session — ContactExportController picks this up
        session(['export_selected_columns' => $selectedColumns]);

        // Redirect triggers a real HTTP GET → controller streams the file
        $this->redirect(route('contacts.export.csv'));
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        return view('livewire.contacts.manage-export');
    }
}
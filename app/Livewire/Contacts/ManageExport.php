<?php

namespace App\Livewire\Contacts;

use Livewire\Attributes\On;
use Livewire\Component;

class ManageExport extends Component
{
    public bool $showExportModal = false;

    // All available columns with their labels and default checked state
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

    private function normalizePhoneTypeForExport(?string $phoneType): string
    {
        if (empty($phoneType)) {
            return 'Mobile';
        }

        $mapping = [
            'work'     => 'Work',
            'home'     => 'Home',
            'self'     => 'Self',
            'office'   => 'Work',
            'business' => 'Work',
        ];

        return $mapping[strtolower($phoneType)] ?? 'Mobile';
    }

    // ============================================================
    // EXPORT - Everything self-contained here
    // ============================================================

    public function exportCsv()
    {
        // Get only checked column keys
        $selectedColumns = collect($this->columns)
            ->filter(fn($col) => $col['checked'])
            ->keys()
            ->toArray();

        // Validate at least one column selected
        if (empty($selectedColumns)) {
            session()->flash('export_error', 'Please select at least one column to export.');
            return;
        }

        // Full column label map
        $allColumnLabels = [
            'name_prefix'     => 'Name Prefix',
            'gender'          => 'Gender',
            'first_name'      => 'First Name',
            'middle_name'     => 'Middle Name',
            'last_name'       => 'Last Name',
            'birthday'        => 'Birthday (DD/MM/YYYY)',
            'self_employed'   => 'Self Employed',
            'company_name'    => 'Company Name',
            'designation'     => 'Designation',
            'phone1_label'    => 'Phone 1 Label',
            'phone1_code'     => 'Phone 1 Country Code',
            'phone1_number'   => 'Phone 1 Number',
            'phone2_label'    => 'Phone 2 Label',
            'phone2_code'     => 'Phone 2 Country Code',
            'phone2_number'   => 'Phone 2 Number',
            'phone3_label'    => 'Phone 3 Label',
            'phone3_code'     => 'Phone 3 Country Code',
            'phone3_number'   => 'Phone 3 Number',
            'email1'          => 'Email 1',
            'email2'          => 'Email 2',
            'email3'          => 'Email 3',
            'primary_address' => 'Primary Address',
            'tags'            => 'Tags',
            'website'         => 'Website',
            'facebook'        => 'Facebook',
            'twitter'         => 'Twitter',
            'linkedin'        => 'LinkedIn',
            'instagram'       => 'Instagram',
            'notes'           => 'Notes',
        ];

        // Get org from session
        $org = session('selected_Orga_UIN');

        // Fetch contacts directly - self-contained, no dependency on parent component
        $contacts = \App\Models\Admn_User_Mast::where('Admn_Orga_Mast_UIN', $org)
            ->where('Is_Actv', 1)
            ->with([
                'tags',
                'prefix',
                'phones'    => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
                'emails'    => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
                'addresses' => fn($q) => $q->where('Is_Prmy', true)
                                           ->with(['country', 'state', 'district', 'pincode']),
            ])
            ->get();

        $filename = 'contacts_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($contacts, $selectedColumns, $allColumnLabels) {
            $file = fopen('php://output', 'w');

            // Write CSV header row (only selected column labels in order)
            $headerRow = [];
            foreach ($selectedColumns as $key) {
                if (isset($allColumnLabels[$key])) {
                    $headerRow[] = $allColumnLabels[$key];
                }
            }
            fputcsv($file, $headerRow);

            // Write each contact as a row
            foreach ($contacts as $contact) {
                $phones = $contact->phones;
                $phone1 = $phones->get(0);
                $phone2 = $phones->get(1);
                $phone3 = $phones->get(2);

                $emails = $contact->emails;
                $email1 = $emails->get(0);
                $email2 = $emails->get(1);
                $email3 = $emails->get(2);

                // Build primary address string
                $primaryAddress    = $contact->addresses->first();
                $fullAddressString = '';
                if ($primaryAddress) {
                    $addressParts = [
                        $primaryAddress->Addr,
                        $primaryAddress->Loca,
                        $primaryAddress->Lndm,
                        optional($primaryAddress->pincode)->Code,
                        optional($primaryAddress->district)->Name,
                        optional($primaryAddress->state)->Name,
                        optional($primaryAddress->country)->Name,
                    ];
                    $fullAddressString = implode(', ', array_filter($addressParts));
                }

                // Full data map for every possible column
                $allData = [
                    'name_prefix'     => optional($contact->prefix)->Prfx_Name ?? '',
                    'gender'          => $contact->Gend ?? '',
                    'first_name'      => $contact->FaNm ?? '',
                    'middle_name'     => $contact->MiNm ?? '',
                    'last_name'       => $contact->LaNm ?? '',
                    'birthday'        => $contact->Brth_Dt
                                            ? \Carbon\Carbon::parse($contact->Brth_Dt)->format('d/m/Y')
                                            : '',
                    'self_employed'   => $contact->Prfl_Name ? $contact->Prfl_Name : 'No',
                    'company_name'    => $contact->Comp_Name ?? '',
                    'designation'     => $contact->Comp_Dsig ?? '',
                    'phone1_label'    => $phone1 ? $this->normalizePhoneTypeForExport($phone1->Phon_Type) : '',
                    'phone1_code'     => optional($phone1)->Cutr_Code ?? '',
                    'phone1_number'   => optional($phone1)->Phon_Numb ?? '',
                    'phone2_label'    => $phone2 ? $this->normalizePhoneTypeForExport($phone2->Phon_Type) : '',
                    'phone2_code'     => optional($phone2)->Cutr_Code ?? '',
                    'phone2_number'   => optional($phone2)->Phon_Numb ?? '',
                    'phone3_label'    => $phone3 ? $this->normalizePhoneTypeForExport($phone3->Phon_Type) : '',
                    'phone3_code'     => optional($phone3)->Cutr_Code ?? '',
                    'phone3_number'   => optional($phone3)->Phon_Numb ?? '',
                    'email1'          => optional($email1)->Emai_Addr ?? '',
                    'email2'          => optional($email2)->Emai_Addr ?? '',
                    'email3'          => optional($email3)->Emai_Addr ?? '',
                    'primary_address' => $fullAddressString,
                    'tags'            => $contact->tags->pluck('Name')->implode(', '),
                    'website'         => $contact->Web ?? '',
                    'facebook'        => $contact->FcBk ?? '',
                    'twitter'         => $contact->Twtr ?? '',
                    'linkedin'        => $contact->LnDn ?? '',
                    'instagram'       => $contact->Intg ?? '',
                    'notes'           => $contact->Note ?? '',
                ];

                // Only include selected columns, in selected order
                $row = [];
                foreach ($selectedColumns as $key) {
                    $row[] = $allData[$key] ?? '';
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        return view('livewire.contacts.manage-export');
    }
}
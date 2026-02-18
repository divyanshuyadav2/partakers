<?php

namespace App\Http\Controllers;

use App\Models\Admn_Cnta_Link_Mast;
use App\Models\Admn_Cutr_Mast;
use App\Models\Admn_Dist_Mast;
use App\Models\Admn_Grup_Mast;
use App\Models\Admn_Prfx_Name_Mast;
use App\Models\Admn_Stat_Mast;
use App\Models\Admn_Tag_Mast;
use App\Models\Admn_User_Mast;
use Illuminate\Http\Request;

class ContactExportController extends Controller
{
    private array $allColumnLabels = [
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

    public function exportCsv(Request $request)
    {
        // Get selected columns from session (set by ManageExport Livewire component)
        $selectedColumns = session('export_selected_columns', array_keys($this->allColumnLabels));

        // Clear from session after reading
        session()->forget('export_selected_columns');

        $org = session('selected_Orga_UIN');

        // ============================================================
        // Exact same query + eager loading as the original exportCsv()
        // ============================================================
        $contacts = Admn_User_Mast::where('Admn_Orga_Mast_UIN', $org)
            ->where('Is_Actv', 100201)
            ->with([
                // Admn_Tag_Mast — contact tags
                'tags',

                // Phones ordered by primary first
                // Admn_Cutr_Mast — country code on each phone
                'phones' => fn($q) => $q->orderBy('Is_Prmy', 'desc'),

                // Emails ordered by primary first
                'emails' => fn($q) => $q->orderBy('Is_Prmy', 'desc'),

                // Primary address only, with full location chain:
                // Admn_Cutr_Mast  → country
                // Admn_Stat_Mast  → state
                // Admn_Dist_Mast  → district
                // Admn_Cnta_Link_Mast (or pincode model) → pincode
                'addresses' => fn($q) => $q->where('Is_Prmy', true)
                                           ->with(['country', 'state', 'district', 'pincode']),
            ])
            ->get();

        // ============================================================
        // Load prefix separately using Admn_Prfx_Name_Mast
        // (same as original — prefix is on the contact model)
        // ============================================================
        $contacts->load('prefix'); // Admn_Prfx_Name_Mast

        $filename = 'contacts_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $allColumnLabels = $this->allColumnLabels;

        $callback = function () use ($contacts, $selectedColumns, $allColumnLabels) {
            $file = fopen('php://output', 'w');

            // Write CSV header row — only selected columns
            $headerRow = [];
            foreach ($selectedColumns as $key) {
                if (isset($allColumnLabels[$key])) {
                    $headerRow[] = $allColumnLabels[$key];
                }
            }
            fputcsv($file, $headerRow);

            foreach ($contacts as $contact) {

                // ------------------------------------------------
                // PHONES — same logic as original exportCsv()
                // ------------------------------------------------
                $phones = $contact->phones;
                $phone1 = $phones->get(0);
                $phone2 = $phones->get(1);
                $phone3 = $phones->get(2);

                // ------------------------------------------------
                // EMAILS — same logic as original exportCsv()
                // ------------------------------------------------
                $emails = $contact->emails;
                $email1 = $emails->get(0);
                $email2 = $emails->get(1);
                $email3 = $emails->get(2);

                // ------------------------------------------------
                // PRIMARY ADDRESS
                // Admn_Cutr_Mast  → $primaryAddress->country->Name
                // Admn_Stat_Mast  → $primaryAddress->state->Name
                // Admn_Dist_Mast  → $primaryAddress->district->Name
                // Admn_Cnta_Link_Mast → $primaryAddress->pincode->Code
                // ------------------------------------------------
                $primaryAddress    = $contact->addresses->first();
                $fullAddressString = '';
                if ($primaryAddress) {
                    $addressParts = [
                        $primaryAddress->Addr,
                        $primaryAddress->Loca,
                        $primaryAddress->Lndm,
                        optional($primaryAddress->pincode)->Code,    // Admn_Cnta_Link_Mast
                        optional($primaryAddress->district)->Name,   // Admn_Dist_Mast
                        optional($primaryAddress->state)->Name,      // Admn_Stat_Mast
                        optional($primaryAddress->country)->Name,    // Admn_Cutr_Mast
                    ];
                    $fullAddressString = implode(', ', array_filter($addressParts));
                }

                // ------------------------------------------------
                // FULL DATA MAP — mirrors exactly the original
                // exportCsv() $row array from the main component
                // ------------------------------------------------
                $allData = [
                    // Admn_Prfx_Name_Mast → prefix name
                    'name_prefix'  => optional($contact->prefix)->Prfx_Name ?? '',

                    'gender'       => $contact->Gend ?? '',
                    'first_name'   => $contact->FaNm ?? '',
                    'middle_name'  => $contact->MiNm ?? '',
                    'last_name'    => $contact->LaNm ?? '',

                    'birthday'     => $contact->Brth_Dt
                                        ? \Carbon\Carbon::parse($contact->Brth_Dt)->format('d/m/Y')
                                        : '',

                    // Admn_User_Mast → Prfl_Name (self-employed profile)
                    'self_employed' => $contact->Prfl_Name ? $contact->Prfl_Name : 'No',

                    'company_name' => $contact->Comp_Name ?? '',
                    'designation'  => $contact->Comp_Dsig ?? '',

                    // Phone 1 — Admn_Cutr_Mast for Cutr_Code
                    'phone1_label'  => $phone1
                                        ? $this->normalizePhoneTypeForExport($phone1->Phon_Type)
                                        : '',
                    'phone1_code'   => optional($phone1)->Cutr_Code ?? '',
                    'phone1_number' => optional($phone1)->Phon_Numb ?? '',

                    // Phone 2
                    'phone2_label'  => $phone2
                                        ? $this->normalizePhoneTypeForExport($phone2->Phon_Type)
                                        : '',
                    'phone2_code'   => optional($phone2)->Cutr_Code ?? '',
                    'phone2_number' => optional($phone2)->Phon_Numb ?? '',

                    // Phone 3
                    'phone3_label'  => $phone3
                                        ? $this->normalizePhoneTypeForExport($phone3->Phon_Type)
                                        : '',
                    'phone3_code'   => optional($phone3)->Cutr_Code ?? '',
                    'phone3_number' => optional($phone3)->Phon_Numb ?? '',

                    // Emails
                    'email1'        => optional($email1)->Emai_Addr ?? '',
                    'email2'        => optional($email2)->Emai_Addr ?? '',
                    'email3'        => optional($email3)->Emai_Addr ?? '',

                    // Full address built from:
                    // Admn_Cutr_Mast + Admn_Stat_Mast + Admn_Dist_Mast + Admn_Cnta_Link_Mast
                    'primary_address' => $fullAddressString,

                    // Admn_Tag_Mast → tag names
                    'tags'          => $contact->tags->pluck('Name')->implode(', '),

                    'website'       => $contact->Web  ?? '',
                    'facebook'      => $contact->FcBk ?? '',
                    'twitter'       => $contact->Twtr ?? '',
                    'linkedin'      => $contact->LnDn ?? '',
                    'instagram'     => $contact->Intg ?? '',
                    'notes'         => $contact->Note ?? '',
                ];

                // Build final row with ONLY selected columns in correct order
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
    // Same helper as original exportCsv() in the main component
    // ============================================================
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
}
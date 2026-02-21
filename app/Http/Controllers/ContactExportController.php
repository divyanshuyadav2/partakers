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
use App\Models\Admn_Bank_Mast;
use App\Models\Admn_Docu_Type_Mast;
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
    
    'phone1_uses_of'    => 'Phone 1 Uses of',
    'phone1_number'   => 'Phone 1 Number',
    
    'phone2_uses_of'    => 'Phone 2 Uses of',
    'phone2_number'   => 'Phone 2 Number',
    
    'phone3_uses_of'    => 'Phone 3 Uses of',
    'phone3_number'   => 'Phone 3 Number',
    
    'email1'          => 'Email 1',
    'email2'          => 'Email 2',
    'email3'          => 'Email 3',
    'address_type'    => 'Address Type',
    'primary_address' => 'Primary Address',
    'degree_name'     => 'Degree Name',
    'degree_year'     => 'Degree Completion Year',
    'skill_name'      => 'Skill Name',
    'employment_org'  => 'Present Employment Organization',
    'employment_desg' => 'Present Employment Designation',
    'bank_name'       => 'Bank Name',
    'bank_account'    => 'Bank A/c Number',
    'bank_acc_type'   => 'A/c Type',
    'bank_ifsc'       => 'IFSC Code',
    'document_type'   => 'Document Type',
    'document_number' => 'Document Number',
    'tags'            => 'Tags',
    'groups'          => 'Groups',
    'website'         => 'Website',
    'facebook'        => 'Facebook',
    'twitter'         => 'Twitter',
    'linkedin'        => 'LinkedIn',
    'instagram'       => 'Instagram',
    'notes'           => 'Notes',
   ];

    public function exportCsv(Request $request)
    {
        // Get selected columns with filters from session
        $selectedColumnsData = session('export_selected_columns', []);
        session()->forget('export_selected_columns');

        $org = session('selected_Orga_UIN');

        // Build query with filters
        $query = Admn_User_Mast::where('Admn_Orga_Mast_UIN', $org)
            ->where('Is_Actv', 100201);

        // ============================================================
        // APPLY FILTERS with OR logic - contacts matching ANY filter will be included
        // ============================================================
        $filters = $this->extractFilters($selectedColumnsData);

        // Check if any filters are active
        $hasActiveFilters = !empty($filters['gender']) && $filters['gender'] !== 'all'
                         || !empty($filters['address_type']) && $filters['address_type'] !== 'all'
                         || !empty($filters['bank_name'])
                         || !empty($filters['document_type']);

        if ($hasActiveFilters) {
            $query->where(function($q) use ($filters) {
                // Gender filter (OR)
                if (!empty($filters['gender']) && $filters['gender'] !== 'all') {
                    $q->orWhere('Gend', $filters['gender']);
                }

                // Address type filter (OR)
                if (!empty($filters['address_type']) && $filters['address_type'] !== 'all') {
                    $q->orWhereHas('addresses', function($addressQuery) use ($filters) {
                        $addressQuery->where('Admn_Addr_Type_Mast_UIN', $filters['address_type'])
                                    ->where('Is_Prmy', true);
                    });
                }

                // Bank name filter (OR)
                if (!empty($filters['bank_name'])) {
                    $q->orWhereHas('bankAccounts', function($bankQuery) use ($filters) {
                        $bankQuery->where('Bank_Name_UIN', $filters['bank_name'])
                                 ->where('Prmy', 1);
                    });
                }

                // Document type filter (OR)
                if (!empty($filters['document_type'])) {
                    $q->orWhereHas('documents', function($docQuery) use ($filters) {
                        $docQuery->where('Admn_Docu_Type_Mast_UIN', $filters['document_type'])
                                ->where('Prmy', 1);
                    });
                }
            });
        }

        // ============================================================
        // Eager load relationships
        // ============================================================
        $contacts = $query->with([
            'tags',
            'prefix',
            'phones' => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
            'emails' => fn($q) => $q->orderBy('Is_Prmy', 'desc'),
            'addresses' => fn($q) => $q->where('Is_Prmy', true)
                                       ->with(['country', 'state', 'district', 'pincode', 'type']),
            'latestEducation',
            'skills',
            'currentEmployment',
            'bankAccounts' => fn($q) => $q->where('Prmy', 1)->with('bank'),
            'documents' => fn($q) => $q->where('Prmy', 1)->with('documentType'),
            'group',
        ])->get();

        // Extract column keys for CSV headers
        $selectedColumns = $this->extractColumnKeys($selectedColumnsData);

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

            // Write CSV header row
            $headerRow = [];
            foreach ($selectedColumns as $key) {
                if (isset($allColumnLabels[$key])) {
                    $headerRow[] = $allColumnLabels[$key];
                }
            }
            fputcsv($file, $headerRow);

            foreach ($contacts as $contact) {
                // ------------------------------------------------
                // PHONES
                // ------------------------------------------------
                $phones = $contact->phones;
                $phone1 = $phones->get(0);
                $phone2 = $phones->get(1);
                $phone3 = $phones->get(2);

                // ------------------------------------------------
                // EMAILS
                // ------------------------------------------------
                $emails = $contact->emails;
                $email1 = $emails->get(0);
                $email2 = $emails->get(1);
                $email3 = $emails->get(2);

                // ------------------------------------------------
                // PRIMARY ADDRESS
                // ------------------------------------------------
                $primaryAddress = $contact->addresses->first();
                $fullAddressString = '';
                $addressType = '';
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
                    $addressType = optional($primaryAddress->type)->Name ?? '';
                }

                // ------------------------------------------------
                // EDUCATION (latest by completion year)
                // ------------------------------------------------
                $latestEducation = $contact->latestEducation;
                $degreeName = optional($latestEducation)->Deg_Name ?? '';
                $degreeYear = optional($latestEducation)->Cmpt_Year ?? '';

                // ------------------------------------------------
                // SKILLS
                // ------------------------------------------------
                $skillNames = $contact->skills->pluck('Skil_Name')->implode(', ');

                // ------------------------------------------------
                // EMPLOYMENT (current or most recent)
                // ------------------------------------------------
                $currentEmployment = $contact->currentEmployment;
                $employmentOrg = optional($currentEmployment)->Orga_Name ?? '';
                $employmentDesg = optional($currentEmployment)->Dsgn ?? '';

                // ------------------------------------------------
                // BANK ACCOUNT
                // ------------------------------------------------
                $primaryBank = $contact->bankAccounts->first();
                $bankName = optional(optional($primaryBank)->bank)->Bank_Name ?? '';
                $bankAccount = optional($primaryBank)->Acnt_Numb ?? '';
                $bankAccType = optional($primaryBank)->Acnt_Type ?? '';
                $bankIfsc = optional($primaryBank)->IFSC_Code ?? '';

                // ------------------------------------------------
                // DOCUMENT
                // ------------------------------------------------
                $primaryDoc = $contact->documents->first();
                $docType = optional(optional($primaryDoc)->documentType)->Docu_Name ?? '';
                $docNumber = optional($primaryDoc)->Docu_Numb ?? '';

                // ------------------------------------------------
                // FULL DATA MAP
                // ------------------------------------------------
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
                
                // Phone 1 with WhatsApp/Telegram indicators
                'phone1_uses_of'    => $phone1 ? $this->normalizePhoneTypeForExport($phone1->Phon_Type) : '',
                'phone1_number'   => $this->formatPhoneWithApps($phone1),
                
                // Phone 2 with WhatsApp/Telegram indicators
                'phone2_uses_of'    => $phone2 ? $this->normalizePhoneTypeForExport($phone2->Phon_Type) : '',
                'phone2_number'   => $this->formatPhoneWithApps($phone2),
                
                // Phone 3 with WhatsApp/Telegram indicators
                'phone3_uses_of'    => $phone3 ? $this->normalizePhoneTypeForExport($phone3->Phon_Type) : '',
                'phone3_number'   => $this->formatPhoneWithApps($phone3),
                
                'email1'          => optional($email1)->Emai_Addr ?? '',
                'email2'          => optional($email2)->Emai_Addr ?? '',
                'email3'          => optional($email3)->Emai_Addr ?? '',
                'address_type'    => $addressType,
                'primary_address' => $fullAddressString,
                'degree_name'     => $degreeName,
                'degree_year'     => $degreeYear,
                'skill_name'      => $skillNames,
                'employment_org'  => $employmentOrg,
                'employment_desg' => $employmentDesg,
                'bank_name'       => $bankName,
                'bank_account'    => $bankAccount,
                'bank_acc_type'   => $bankAccType,
                'bank_ifsc'       => $bankIfsc,
                'document_type'   => $docType,
                'document_number' => $docNumber,
                'tags'            => $contact->tags->pluck('Name')->implode(', '),
                'groups'          => optional($contact->group)->Name ?? '',
                'website'         => $contact->Web  ?? '',
                'facebook'        => $contact->FcBk ?? '',
                'twitter'         => $contact->Twtr ?? '',
                'linkedin'        => $contact->LnDn ?? '',
                'instagram'       => $contact->Intg ?? '',
                'notes'           => $contact->Note ?? '',
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

    /**
     * Extract filter values from selected columns data
     */
    private function extractFilters(array $selectedColumnsData): array
    {
        $filters = [
            'gender' => null,
            'address_type' => null,
            'bank_name' => null,
            'document_type' => null,
        ];

        foreach ($selectedColumnsData as $item) {
            $key = is_array($item) ? ($item['key'] ?? null) : $item;
            $filterValue = is_array($item) ? ($item['filter_value'] ?? null) : null;

            if (in_array($key, ['gender', 'address_type', 'bank_name', 'document_type'])) {
                $filters[$key] = $filterValue;
            }
        }

        return $filters;
    }

    /**
     * Extract column keys from selected columns data
     */
    private function extractColumnKeys(array $selectedColumnsData): array
    {
        return array_map(function($item) {
            return is_array($item) ? ($item['key'] ?? $item) : $item;
        }, $selectedColumnsData);
    }

    /**
     * Normalize phone type for export
     */
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
    /**
 * Format phone number with WhatsApp/Telegram indicators
 */
private function formatPhoneWithApps($phone): string
{
    if (!$phone || !$phone->Phon_Numb) {
        return '';
    }

    $number = $phone->Phon_Numb;
    $indicators = [];

    if ($phone->Has_WtAp) {
        $indicators[] = 'WhatsApp';
    }
    if ($phone->Has_Telg) {
        $indicators[] = 'Telegram';
    }

    if (!empty($indicators)) {
        return $number . ' (' . implode(', ', $indicators) . ')';
    }

    return $number;
}
}
<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;

trait WithContactValidation
{
    // ========================================================================
    // MAIN RULES
    // ========================================================================
    public function rules()
    {
        // 1. Determine Limits (Use class constants if available, else default)
        $maxEmails = defined('static::MAX_EMAILS') ? static::MAX_EMAILS : 5;
        $maxPhones = defined('static::MAX_PHONES') ? static::MAX_PHONES : 5;
        $maxLandlines = defined('static::MAX_LANDLINES') ? static::MAX_LANDLINES : 5;
        $maxAddresses = defined('static::MAX_ADDRESSES') ? static::MAX_ADDRESSES : 5;
        $maxRefs = defined('static::MAX_REFERENCES') ? static::MAX_REFERENCES : 5;
        $maxBanks = defined('static::MAX_BANKS') ? static::MAX_BANKS : 5;
        $maxDocs = defined('static::MAX_DOCUMENTS') ? static::MAX_DOCUMENTS : 10;
        $maxEduc = defined('static::MAX_EDUCATIONS') ? static::MAX_EDUCATIONS : 10;
        $maxSkills = defined('static::MAX_SKILLS') ? static::MAX_SKILLS : 20;
        $maxWork = defined('static::MAX_WORK_EXPERIENCES') ? static::MAX_WORK_EXPERIENCES : 10;

        // 2. Common Rules
        $rules = [
            'Prty' => 'required|in:I,B',
            'Prfx_UIN' => 'nullable|integer|exists:admn_prfx_name_mast,Prfx_Name_UIN',
            'MiNm' => 'nullable|string|max:50',
            'LaNm' => 'nullable|string|max:50',
            'Blood_Grp' => 'nullable|string',
            'Prfl_Pict' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048', // 2MB

            // Employment / Organization
            'Comp_Name' => 'nullable|string|max:50',
            'Comp_Dsig' => 'nullable|string|max:30',
            'Comp_LdLi' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:20'],
            'Comp_Desp' => 'nullable|string|max:500',
            'Comp_Emai' => ['nullable', 'string', 'email', 'max:255', 'regex:/^.+@.+\..+$/i'],
            'Comp_Web' => 'nullable|url|max:255',
            'Comp_Addr' => 'nullable|string|max:500',
            'Prfl_Name' => 'nullable|string|max:30',
            'Prfl_Addr' => 'nullable|string|max:500',

            // Web Presence (Using Helper Validation)
            'Web' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'LnDn' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateDomain($v, 'linkedin.com', $f)],
            'Twtr' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateDomain($v, ['twitter.com', 'x.com'], $f)],
            'FcBk' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateDomain($v, 'facebook.com', $f)],
            'Intg' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateDomain($v, 'instagram.com', $f)],
            'Redt' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateDomain($v, 'reddit.com', $f)],
            'Ytb' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateDomain($v, ['youtube.com', 'youtu.be'], $f)],
            'Yaho' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateDomain($v, 'yahoo.com', $f)],

            // Collections
            'phones' => "nullable|array|max:$maxPhones",
            'phones.*.Cutr_Code' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:5'],
            'phones.*.Phon_Type' => 'nullable|string',
            'phones.*.Phon_Numb' => [
                'nullable', 'string', 'regex:/^[0-9]+$/', 'distinct',
                fn($a, $v, $f) => $this->validatePhoneNumberFormat($a, $v, $f)
            ],
            'phones.*.Has_WtAp' => 'nullable|boolean',
            'phones.*.Has_Telg' => 'nullable|boolean',

            'landlines' => "nullable|array|max:$maxLandlines",
            'landlines.*.Cutr_Code' => 'nullable|string|regex:/^[0-9]+$/|max:5',
            'landlines.*.Land_Numb' => 'nullable|string|regex:/^[0-9]+$/|max:20|distinct',
            'landlines.*.Land_Type' => 'nullable|string',

            'emails' => "nullable|array|max:$maxEmails",
            'emails.*.Emai_Addr' => ['nullable', 'string', 'email', 'max:255', 'distinct', 'regex:/^.+@.+\..+$/i'],
            'emails.*.Emai_Type' => 'nullable',

            'addresses' => "nullable|array|max:$maxAddresses",
            'addresses.*.Addr' => 'nullable|string|max:255|distinct',
            'addresses.*.Loca' => 'nullable|string|max:50|distinct',
            'addresses.*.Lndm' => 'nullable|string|max:100|distinct',
            'addresses.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN',
            'addresses.*.Admn_Stat_Mast_UIN' => 'nullable|exists:admn_stat_mast,Admn_Stat_Mast_UIN',
            'addresses.*.Admn_Dist_Mast_UIN' => 'nullable|exists:admn_dist_mast,Admn_Dist_Mast_UIN',
            'addresses.*.Admn_PinCode_Mast_UIN' => 'nullable|exists:admn_pincode_mast,Admn_PinCode_Mast_UIN',


            'references' => "nullable|array|max:$maxRefs",
            'references.*.Refa_Name' => 'nullable|string|max:50|distinct',
            'references.*.Refa_Emai' => ['nullable', 'string', 'email', 'max:50', 'distinct', 'regex:/^.+@.+\..+$/i'],
            'references.*.Refa_Phon' => ['nullable', 'string', 'distinct', 'regex:/^[0-9]+$/', 'max:20'],
            'references.*.Refa_Rsip' => 'nullable|string|max:50',

            // Banks
            'bankAccounts' => "nullable|array|max:$maxBanks",
            'bankAccounts.*.Bank_Name_UIN' => ['nullable', 'exists:admn_bank_name,Bank_UIN', 'required_with:bankAccounts.*.Acnt_Numb'],
            'bankAccounts.*.Acnt_Numb' => ['required_with:bankAccounts.*.Bank_Name_UIN', 'string', 'max:50', 'distinct'],
            'bankAccounts.*.Bank_Brnc_Name' => 'nullable|string|max:50',
            'bankAccounts.*.Acnt_Type' => 'nullable|string|max:50|required_with:bankAccounts.*.Bank_Name_UIN',
            'bankAccounts.*.IFSC_Code' => 'nullable|string|max:11',
            'bankAccounts.*.Swift_Code' => 'nullable|string|max:11|min:8',
            'bankAccounts.*.newAttachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:100', // 100KB

            // Documents (UPDATED RULES)
            'documents' => "nullable|array|max:$maxDocs",
            'documents.*.selected_types' => 'nullable|array',
            'documents.*.selected_types.*' => 'exists:admn_docu_type_mast,Admn_Docu_Type_Mast_UIN',

            // Name: Required if types selected + Distinct check
            'documents.*.Docu_Name' => [
                'nullable',
                'string',
                'max:255',
                'distinct',
                'required_with:documents.*.selected_types' // Required if selected_types is present/not empty
            ],

            // Reg Number: Required if types selected
            'documents.*.Regn_Numb' => [
                'nullable',
                'string',
                'max:100',
                'required_with:documents.*.selected_types' // Required if selected_types is present/not empty
            ],

            'documents.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN',
            'documents.*.Auth_Issd' => 'nullable|string|max:100',
            'documents.*.Vald_From' => 'nullable|date',
            'documents.*.Vald_Upto' => [
                'nullable', 'date',
                fn($a, $v, $f) => $this->validateDateRange($a, $v, $f, 'documents', 'Vald_From')
            ],
            'documents.*.Docu_Atch_Path' => [
                'nullable',
                fn($a, $v, $f) => $this->validateDocumentAttachment($a, $v, $f)
            ],

            // Education, Skills, Work
            'educations' => "nullable|array|max:$maxEduc",
            'educations.*.Deg_Name' => 'nullable|string|max:100',
            'educations.*.Inst_Name' => 'nullable|string|max:255',
            'educations.*.Cmpt_Year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'educations.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN',

            'skills' => "nullable|array|max:$maxSkills",
            'skills.*.Skil_Type' => 'nullable|string|max:100',
            'skills.*.Skil_Type_1' => 'nullable|string|max:100',
            'skills.*.Skil_Name' => 'nullable|string|max:150',
            'skills.*.Profc_Lvl' => 'nullable|integer|min:1|max:5',

            'workExperiences' => "nullable|array|max:$maxWork",
            'workExperiences.*.Orga_Name' => 'nullable|string|max:255',
            'workExperiences.*.Dsgn' => 'nullable|string|max:150',
            'workExperiences.*.Prd_From' => [
                'nullable', 'date', 'before:today', 'required_with:workExperiences.*.Prd_To',
                function ($attribute, $value, $fail) {
                    if (preg_match('/workExperiences\.(\d+)\.Prd_From/', $attribute, $matches)) {
                        $index = $matches[1];
                        $toDate = $this->workExperiences[$index]['Prd_To'] ?? null;

                        if ($toDate && $value && strtotime($value) >= strtotime($toDate)) {
                            $fail("The From Date must be before the To Date.");
                        }
                    }
                }
            ],
            'workExperiences.*.Prd_To' => [
                'nullable', 'required_with:workExperiences.*.Prd_From', 'date', 'before_or_equal:today',
                fn($a, $v, $f) => $this->validateDateRange($a, $v, $f, 'workExperiences', 'Prd_From')
            ],
            'workExperiences.*.Orga_Type' => 'nullable|string|max:100',
            'workExperiences.*.Job_Desp' => 'nullable|string|max:1000',
            'workExperiences.*.Work_Type' => 'nullable|string',
            'workExperiences.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN',

            'Note' => 'nullable|string|max:5000',
        ];

        // 3. Conditional Rules (Business vs Individual)
        if ($this->Prty === 'B') {
            $rules['addresses.*.Admn_Addr_Type_Mast_UIN'] = 'nullable';
            $rules['FaNm'] = 'required|string|max:50'; // Organization Name
            $rules['Gend'] = 'required|string'; // Organization Type
            $rules['Brth_Dt'] = 'nullable|date|before:today'; // Incorporation Date
            $rules['Anvy_Dt'] = 'nullable|date|before_or_equal:today';
            $rules['Deth_Dt'] = 'nullable|date|before_or_equal:today';
        } else {
            // Individual
             $rules['addresses.*.Admn_Addr_Type_Mast_UIN'] = [
                'nullable', 'distinct',
                fn($a, $v, $f) => $this->requireAddressTypeIfFilled($a, $v, $f)
             ];
            $rules['FaNm'] = 'required|string|max:50'; // First Name
            $rules['Gend'] = 'required|string'; // Gender
            $rules['Brth_Dt'] = 'nullable|date|before:today'; // Birth Date
            $rules['Anvy_Dt'] = [
                'nullable', 'date', 'before_or_equal:today',
                function ($attribute, $value, $fail) {
                    if ($this->Brth_Dt && $value && strtotime($value) <= strtotime($this->Brth_Dt)) {
                        $fail('The Anniversary date cannot be before the birth date.');
                    }
                    if ($this->Deth_Dt && $value && strtotime($value) >= strtotime($this->Deth_Dt)) {
                        $fail('The Anniversary date must be before the death date.');
                    }
                }
            ];
            $rules['Deth_Dt'] = [
                'nullable', 'date', 'before_or_equal:today',
                function ($attribute, $value, $fail) {
                    if ($this->Brth_Dt && $value && strtotime($value) <= strtotime($this->Brth_Dt)) {
                        $fail('The death date must be after the birth date.');
                    }
                    if ($this->Anvy_Dt && $value && strtotime($value) <= strtotime($this->Anvy_Dt)) {
                        $fail('The death date must be after the anniversary date.');
                    }
                }
            ];
        }

        // 4. Conditional Properties (Fix for CreateByLink crash)
        if (property_exists($this, 'selectedTags')) {
            $rules['selectedTags'] = 'nullable|array';
            $rules['selectedTags.*'] = 'exists:admn_tag_mast,Admn_Tag_Mast_UIN';
        }

        if (property_exists($this, 'newNoteContent')) {
            $rules['newNoteContent'] = 'nullable|string|max:5000';
        }

        return $rules;
    }

    // ========================================================================
    // MESSAGES
    // ========================================================================
    public function messages()
    {
        $commonMessages = [
            'Prfx_UIN.integer' => 'The prefix must be a valid ID.',
            'Prfx_UIN.exists' => 'The selected prefix is invalid.',

            // Personal
            'MiNm.string' => 'The middle name must be a string.',
            'MiNm.max' => 'The middle name may not be greater than :max characters.',
            'LaNm.string' => 'The last name must be a string.',
            'LaNm.max' => 'The last name may not be greater than :max characters.',
            'Note.string' => 'The remarks must be a string.',
            'Note.max' => 'The remarks may not be greater than :max characters.',
            'Prfl_Pict.image' => 'The profile picture must be an image file.',
            'Prfl_Pict.mimes' => 'The profile picture must be a file of type: JPEG, JPG, PNG, or GIF.',
            'Prfl_Pict.max' => 'The profile picture may not be greater than 2 MB.',

            // Employment / Company
            'Comp_Name.string' => 'The company name must be a string.',
            'Comp_Name.max' => 'The company name must not exceed :max characters.',
            'Comp_Dsig.string' => 'The designation must be a string.',
            'Comp_Dsig.max' => 'The designation must not exceed :max characters.',
            'Comp_LdLi.regex' => 'The company landline must contain only digits.',
            'Comp_LdLi.max' => 'The company landline may not be greater than :max digits.',
            'Comp_Desp.string' => 'The company business description must be a string.',
            'Comp_Desp.max' => 'The company business description may not be greater than :max characters.',
            'Comp_Emai.email' => 'The company email must be a valid email address.',
            'Comp_Emai.regex' => 'The company email must be a valid address with a domain extension (e.g., name@domain.com).',
            'Comp_Emai.max' => 'The company email may not be greater than :max characters.',
            'Comp_Web.url' => 'The company website must be a valid URL.',
            'Comp_Web.max' => 'The company website URL may not be greater than :max characters.',
            'Comp_Addr.string' => 'The company address must be a string.',
            'Comp_Addr.max' => 'The company address may not be greater than :max characters.',
            'Prfl_Name.string' => 'The profession/service name must be a string.',
            'Prfl_Name.max' => 'The profession/service name may not be greater than :max characters.',
            'Prfl_Addr.string' => 'The business address must be a string.',
            'Prfl_Addr.max' => 'The business address may not be greater than :max characters.',

            // Web Presence
            'Web.url' => 'The website URL must be a valid URL.',
            'Web.max' => 'The website URL may not be greater than :max characters.',
            'FcBk.url' => 'The Facebook URL must be a valid URL.',
            'FcBk.max' => 'The Facebook URL may not be greater than :max characters.',
            'Twtr.url' => 'The Twitter URL must be a valid URL.',
            'Twtr.max' => 'The Twitter URL may not be greater than :max characters.',
            'LnDn.url' => 'The LinkedIn URL must be a valid URL.',
            'LnDn.max' => 'The LinkedIn URL may not be greater than :max characters.',
            'Intg.url' => 'The Instagram URL must be a valid URL.',
            'Intg.max' => 'The Instagram URL may not be greater than :max characters.',
            'Yaho.url' => 'The Yahoo URL must be a valid URL.',
            'Yaho.max' => 'The Yahoo URL may not be greater than :max characters.',
            'Redt.url' => 'The Reddit URL must be a valid URL.',
            'Redt.max' => 'The Reddit URL may not be greater than :max characters.',
            'Ytb.url' => 'The YouTube URL must be a valid URL.',
            'Ytb.max' => 'The YouTube URL may not be greater than :max characters.',

            // Phones
            'phones.array' => 'The mobile numbers field must be an array.',
            'phones.*.Phon_Numb.regex' => 'Each mobile number must contain only digits.',
            'phones.*.Phon_Numb.max' => 'Each mobile number may not be greater than :max digits.',
            'phones.*.Phon_Numb.distinct' => 'This mobile number has been submitted previously.',
            'phones.*.Cutr_Code.regex' => 'Each country code must contain only digits.',
            'phones.*.Cutr_Code.max' => 'Each country code may not be greater than :max digits.',

            // Landlines
            'landlines.array' => 'The landline numbers field must be an array.',
            'landlines.*.Land_Numb.regex' => 'Each landline number must contain only digits.',
            'landlines.*.Land_Numb.max' => 'Each landline number may not be greater than :max digits.',
            'landlines.*.Land_Numb.distinct' => 'This landline number has been submitted previously.',
            'landlines.*.Cutr_Code.regex' => 'Each country code must contain only digits.',
            'landlines.*.Cutr_Code.max' => 'Each country code may not be greater than :max digits.',

            // Emails
            'emails.array' => 'The emails field must be an array.',
            'emails.*.Emai_Addr.email' => 'Each email address must be a valid email format.',
            'emails.*.Emai_Addr.regex' => 'The email address must contain a valid domain extension (e.g., .com, .net).',
            'emails.*.Emai_Addr.max' => 'Each email address may not be greater than :max characters.',
            'emails.*.Emai_Addr.distinct' => 'This email address has been submitted previously.',

            // Addresses
            'addresses.array' => 'The addresses field must be an array.',
            'addresses.max' => 'You can add a maximum of :max addresses.',
            'addresses.*.Addr.string' => 'The address line must be a string.',
            'addresses.*.Addr.max' => 'The address line may not be greater than :max characters.',
            'addresses.*.Loca.string' => 'The locality/street must be a string.',
            'addresses.*.Loca.max' => 'The locality/street may not be greater than :max characters.',
            'addresses.*.Lndm.string' => 'The landmark must be a string.',
            'addresses.*.Lndm.max' => 'The landmark may not be greater than :max characters.',
            'addresses.*.Admn_Addr_Type_Mast_UIN.exists' => 'The selected address type is invalid.',
            'addresses.*.Admn_Cutr_Mast_UIN.exists' => 'The selected country is invalid.',
            'addresses.*.Admn_Stat_Mast_UIN.exists' => 'The selected state is invalid.',
            'addresses.*.Admn_Dist_Mast_UIN.exists' => 'The selected district is invalid.',
            'addresses.*.Admn_PinCode_Mast_UIN.exists' => 'The selected pincode is invalid.',
            'addresses.*.Admn_Addr_Type_Mast_UIN.distinct' => 'Address type has been already selected previously',
            'addresses.*.Admn_Addr_Type_Mast_UIN.required' => 'The address type is required when address details are filled.',

            // References
            'references.array' => 'The references field must be an array.',
            'references.max' => 'You can add a maximum of :max references.',
            'references.*.Refa_Name.string' => "Each name must be a string.",
            'references.*.Refa_Name.max' => "Each name may not be greater than :max characters.",
            'references.*.Refa_Emai.email' => "Each email must be a valid email address.",
            'references.*.Refa_Emai.regex' => 'The reference email must be a valid address with a domain extension.',
            'references.*.Refa_Emai.max' => "Each email may not be greater than :max characters.",
            'references.*.Refa_Phon.regex' => "Each mobile number must contain only digits.",
            'references.*.Refa_Phon.max' => "Each mobile number may not be greater than :max digits.",
            'references.*.Refa_Rsip.string' => "Each relationship/designation must be a string.",
            'references.*.Refa_Rsip.max' => "Each relationship/designation may not be greater than :max characters.",
            'references.*.Refa_Name.distinct' => "This reference person's name has been submitted previously.",
            'references.*.Refa_Emai.distinct' => "This email address has been submitted previously.",
            'references.*.Refa_Phon.distinct' => "This reference person's mobile has been submitted previously.",

            // Tags
            'selectedTags.array' => 'The selected tags must be an array.',
            'selectedTags.*.exists' => 'One or more selected tags are invalid.',

            // Bank Accounts
            'bankAccounts.array' => 'The bank accounts field must be an array.',
            'bankAccounts.max' => 'You can add a maximum of :max bank accounts.',
            'bankAccounts.*.Bank_Name_UIN.exists' => 'The selected bank is invalid.',
            'bankAccounts.*.Bank_Brnc_Name.string' => 'Branch name must be a string.',
            'bankAccounts.*.Bank_Brnc_Name.max' => 'Branch name may not exceed :max characters.',
            'bankAccounts.*.Bank_Name_UIN.required_with' => 'The bank name is required when an account number is entered.',
            'bankAccounts.*.Acnt_Numb.required_with' => 'The account number is required when a bank is selected.',
            'bankAccounts.*.Acnt_Numb.string' => 'Account number must be a string.',
            'bankAccounts.*.Acnt_Numb.max' => 'Account number may not exceed :max characters.',
            'bankAccounts.*.Acnt_Numb.distinct'=> 'Account number has been entered previously',
            'bankAccounts.*.Acnt_Type' => 'Account Type is required',
            'bankAccounts.*.IFSC_Code.string' => 'Indian Finance System code (IFSC) must be a string.',
            'bankAccounts.*.IFSC_Code.max' => 'Indian Finance System code (IFSC) may not exceed :max characters.',
            'bankAccounts.*.Swift_Code.string' => 'SWIFT code must be a string.',
            'bankAccounts.*.Swift_Code.max' => 'SWIFT code may not exceed :max characters.',
            'bankAccounts.*.Swift_Code.min' => 'SWIFT code must be at least :min characters.',
            'bankAccounts.*.newAttachments.*.mimes' => 'Document must be a PDF, JPG, JPEG, or PNG file.',
            'bankAccounts.*.newAttachments.*.max' => 'Document size must not exceed :max KB.',

            // Documents
            'documents.array' => 'The documents field must be an array.',
            'documents.max' => 'You can add a maximum of :max documents.',
            'documents.*.selected_types.array' => 'Document types must be an array.',
            'documents.*.selected_types.*.exists' => 'One of the selected document types is invalid.',
            'documents.*.Docu_Name.string' => 'Document name must be a string.',
            'documents.*.Docu_Name.max' => 'Document name may not exceed :max characters.',
            'documents.*.Docu_Name.distinct' => 'This document name has been entered previously.',
            'documents.*.Docu_Name.required_with' => 'The document name is required when document type is selected.',
            'documents.*.Regn_Numb.string' => 'Registration number must be a string.',
            'documents.*.Regn_Numb.max' => 'Registration number may not exceed :max characters.',
            'documents.*.Regn_Numb.required_with' => 'The registration number is required when document type is selected.',
            'documents.*.Admn_Cutr_Mast_UIN.exists' => 'The selected country is invalid.',
            'documents.*.Auth_Issd.string' => 'Authority issued must be a string.',
            'documents.*.Auth_Issd.max' => 'Authority issued may not exceed :max characters.',
            'documents.*.Vald_From.date' => 'Valid from must be a valid date.',
            'documents.*.Vald_Upto.date' => 'Valid upto must be a valid date.',
            'documents.*.Docu_Atch_Path.file' => 'Document attachment must be a file.',
            'documents.*.Docu_Atch_Path.mimes' => 'Document attachment must be PDF, JPG, PNG, or WEBP.',
            'documents.*.Docu_Atch_Path.max' => 'Document attachment may not be greater than 100 KB.',

            // Education
            'educations.array' => 'The educations field must be an array.',
            'educations.max' => 'You can add a maximum of :max educations.',
            'educations.*.Deg_Name.string' => 'Degree name must be a string.',
            'educations.*.Deg_Name.max' => 'Degree name may not exceed :max characters.',
            'educations.*.Inst_Name.string' => 'Institution name must be a string.',
            'educations.*.Inst_Name.max' => 'Institution name may not exceed :max characters.',
            'educations.*.Cmpt_Year.integer' => 'Completion year must be an integer.',
            'educations.*.Cmpt_Year.min' => 'Completion year must be at least :min.',
            'educations.*.Cmpt_Year.max' => 'Completion year may not be greater than :max.',
            'educations.*.Admn_Cutr_Mast_UIN.exists' => 'The selected country for education is invalid.',

            // Skills
            'skills.array' => 'The skills field must be an array.',
            'skills.max' => 'You can add a maximum of :max skills.',
            'skills.*.Skil_Type.string' => 'Skill type must be a string.',
            'skills.*.Skil_Type.max' => 'Skill type may not exceed :max characters.',
            'skills.*.Skil_Type_1.string' => 'Skill subtype must be a string.',
            'skills.*.Skil_Type_1.max' => 'Skill subtype may not exceed :max characters.',
            'skills.*.Skil_Name.string' => 'Skill name must be a string.',
            'skills.*.Skil_Name.max' => 'Skill name may not exceed :max characters.',
            'skills.*.Profc_Lvl.integer' => 'Proficiency level must be an integer.',
            'skills.*.Profc_Lvl.min' => 'Proficiency level must be at least :min.',
            'skills.*.Profc_Lvl.max' => 'Proficiency level may not be greater than :max.',

            // Work Experience
            'workExperiences.array' => 'The work experiences field must be an array.',
            'workExperiences.max' => 'You can add a maximum of :max work experiences.',
            'workExperiences.*.Orga_Name.string' => 'Organization name must be a string.',
            'workExperiences.*.Orga_Name.max' => 'Organization name may not exceed :max characters.',
            'workExperiences.*.Dsgn.string' => 'Designation must be a string.',
            'workExperiences.*.Dsgn.max' => 'Designation may not exceed :max characters.',
            'workExperiences.*.Prd_From.date' => 'Period from must be a valid date.',
            'workExperiences.*.Prd_From.required_with' => 'The From Date is required',
            'workExperiences.*.Prd_From.before' => 'The From Date must be before today.',
            'workExperiences.*.Prd_To.date' => 'Period to must be a valid date.',
            'workExperiences.*.Prd_To.required_with' => 'The To Date is required',
            'workExperiences.*.Prd_To.before_or_equal' => "To Date must be after the Form Date and before today's Date.",
            'workExperiences.*.Orga_Type.string' => 'Organization type must be a string.',
            'workExperiences.*.Orga_Type.max' => 'Organization type may not exceed :max characters.',
            'workExperiences.*.Job_Desp.string' => 'Job description must be a string.',
            'workExperiences.*.Job_Desp.max' => 'Job description may not exceed :max characters.',
            'workExperiences.*.Work_Type.in' => 'The selected work type is invalid.',
            'workExperiences.*.Admn_Cutr_Mast_UIN.exists' => 'The selected country for work experience is invalid.',

            // Notes
            'newNoteContent.string' => 'The note must be a string.',
            'newNoteContent.max' => 'The note may not be greater than :max characters.',
            'Anvy_Dt.date' => 'The anniversary date must be a valid date.',
            'Anvy_Dt.before_or_equal' => 'The anniversary date cannot be in the future.',
            'Deth_Dt.date' => 'The death date must be a valid date.',
            'Deth_Dt.before_or_equal' => 'The death date cannot be in the future.',
        ];

        // Conditional Messages
        if ($this->Prty === 'B') {
            $specificMessages = [
                'FaNm.required' => 'The Organization name is required.',
                'FaNm.string' => 'The Organization name must be a string.',
                'FaNm.max' => 'The Organization name may not be greater than :max characters.',
                'Gend.required' => 'The Organization Type is Required.',
                'Brth_Dt.date' => 'The Incorporation date must be a valid date.',
                'Brth_Dt.before' => 'The Incorporation date must be in the past.',
                'references.*.Refa_Name.distinct' => "This authorized person's name has been submitted previously",
                'references.*.Refa_Emai.distinct' => "This authorized person's email has been submitted previously.",
                'references.*.Refa_Phon.distinct' => "This authorized person's mobile has been submitted previously.",
            ];
        } else {
            $specificMessages = [
                'FaNm.required' => 'The first name is required.',
                'FaNm.string' => 'The first name must be a string.',
                'FaNm.max' => 'The first name may not be greater than :max characters.',
                'Gend.required' => 'The gender field is required.',
                'Brth_Dt.date' => 'The birth date must be a valid date.',
                'Brth_Dt.before' => 'The birth date must be before today.',
                'references.*.Refa_Name.distinct' => "This reference person's name has been submitted previously.",
                'references.*.Refa_Emai.distinct' => "This email address has been submitted previously.",
                'references.*.Refa_Phon.distinct' => "This reference person's mobile has been submitted previously.",
            ];
        }

        return array_merge($commonMessages, $specificMessages);
    }

    // ========================================================================
    // HELPER FUNCTIONS
    // ========================================================================

    public function validatePhoneNumberFormat($attribute, $value, $fail)
    {
        if (empty($value)) return;

        if (preg_match('/phones\.(\d+)\.Phon_Numb/', $attribute, $matches)) {
            $index = $matches[1];
            if (!isset($this->phones[$index])) return;

            $phone = $this->phones[$index];
            $countryCode = $phone['Cutr_Code'] ?? '91'; // Default India
            $country = $this->getAllCountriesCollectionProperty()->firstWhere('Phon_Code', trim($countryCode));

            if (!$country) {
                $fail('A valid country must be selected for this mobile number.');
                return;
            }

            $requiredLength = (int) $country->MoNo_Digt;
            $currentLength = strlen(preg_replace('/\D/', '', $value));

            if ($currentLength !== $requiredLength) {
                $fail("The mobile number must be exactly {$requiredLength} digits for {$country->Name}.");
            }
        }
    }

    public function requireAddressTypeIfFilled($attribute, $value, $fail)
    {
        if (preg_match('/addresses\.(\d+)\.Admn_Addr_Type_Mast_UIN/', $attribute, $matches)) {
            $index = $matches[1];
            $address = $this->addresses[$index] ?? [];
            $anyFilled = !empty($address['Addr']) || !empty($address['Loca']) || !empty($address['Lndm']) || !empty($address['Admn_PinCode_Mast_UIN']);

            if ($anyFilled && empty($value)) {
                $fail('The address type is required when address details are filled.');
            }
        }
    }

    public function validateDateRange($attribute, $value, $fail, $collectionName, $compareField)
    {
        if (preg_match("/{$collectionName}\.(\d+)\./", $attribute, $matches)) {
            $index = $matches[1];
            $start = $this->{$collectionName}[$index][$compareField] ?? null;

            if ($start && $value && strtotime($value) <= strtotime($start)) {
                if ($collectionName == 'documents') {
                    $fail('Valid upto date must be after valid from date.');
                } elseif ($collectionName == 'workExperiences') {
                    $fail("To Date must be after the Form Date and before today's Date.");
                } else {
                    $fail('End date must be after start date.');
                }
            }
        }
    }

    public function validateDocumentAttachment($attribute, $value, $fail)
    {
        if (empty($value)) return;

        if (is_object($value)) {
            if (!($value instanceof UploadedFile)) {
                $fail('Document attachment must be a valid file upload.');
                return;
            }

            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($value->getMimeType(), $allowedMimes)) {
                $fail('Document attachment must be PDF, JPG, PNG, or WEBP.');
            }

            if ($value->getSize() > 204800) { // 200KB
                $fail('Document attachment may not be greater than 100 KB.');
            }
        }
    }

    public function validateDomain($value, $domains, $fail)
    {
        if (empty($value)) return;
        $domains = (array) $domains;
        if (!Str::contains(strtolower($value), $domains)) {
            $fail('URL must be from: ' . implode(' or ', $domains));
        }
        $this->validateUniqueUrl(null, $value, $fail);
    }

    public function validateUniqueUrl($attribute, $value, $fail)
    {
        if (empty($value)) return;
        $valNorm = rtrim(strtolower($value), '/');
        $fields = ['Web', 'LnDn', 'Twtr', 'FcBk', 'Intg', 'Yaho', 'Redt', 'Ytb'];

        foreach ($fields as $field) {
            $otherVal = $this->{$field} ?? null;
            if ($otherVal && $otherVal !== $value && rtrim(strtolower($otherVal), '/') === $valNorm) {
                $fail('This URL is duplicated in ' . ucfirst($field));
                return;
            }
        }
    }

    public function validateCustomLogic()
    {
        $usedTypes = [];
        foreach ($this->addresses as $index => $addr) {
            if (!empty($addr['Admn_Addr_Type_Mast_UIN'])) {
                if (in_array($addr['Admn_Addr_Type_Mast_UIN'], $usedTypes)) {
                    $this->addError("addresses.{$index}.Admn_Addr_Type_Mast_UIN", 'This Address Type is already used.');
                }
                $usedTypes[] = $addr['Admn_Addr_Type_Mast_UIN'];
            }
        }
    }
}

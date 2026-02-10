<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait WithContactValidation
{
    // ========================================================================
    // MAIN RULES
    // ========================================================================
    public function rules()
    {
        // 1. Determine Limits
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
            'MiNm' => 'nullable|string|max:50',
            'LaNm' => 'nullable|string|max:50',
            'Blood_Grp' => 'nullable|string',
            'Prfl_Pict' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048', // 2MB

            // Employment / Organization
            'Comp_Name' => 'nullable|string|max:50',
            'Comp_Dsig' => 'nullable|string|max:30',
            'Comp_LdLi' => 'nullable|numeric|digits_between:5,20',
            'Comp_Desp' => 'nullable|string|max:500',
            'Comp_Emai' => 'nullable|email|max:255', // 'email' rule handles regex and format
            'Comp_Web' => 'nullable|url|max:255',
            'Comp_Addr' => 'nullable|string|max:500',
            'Prfl_Name' => 'nullable|string|max:30',
            'Prfl_Addr' => 'nullable|string|max:500',

            // Web Presence (Replaced validateDomain with Regex)
            'Web' => ['nullable', 'url', 'max:255', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'LnDn' => ['nullable', 'url', 'max:255', 'regex:/linkedin\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Twtr' => ['nullable', 'url', 'max:255', 'regex:/(twitter\.com|x\.com)/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'FcBk' => ['nullable', 'url', 'max:255', 'regex:/facebook\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Intg' => ['nullable', 'url', 'max:255', 'regex:/instagram\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Redt' => ['nullable', 'url', 'max:255', 'regex:/reddit\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Ytb' => ['nullable', 'url', 'max:255', 'regex:/(youtube\.com|youtu\.be)/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],
            'Yaho' => ['nullable', 'url', 'max:255', 'regex:/yahoo\.com/i', fn($a, $v, $f) => $this->validateUniqueUrl($a, $v, $f)],

            // Collections
            'phones' => "nullable|array|max:$maxPhones",
            'phones.*.Cutr_Code' => 'nullable|numeric|digits_between:1,5',
            'phones.*.Phon_Type' => 'nullable|string',
            'phones.*.Phon_Numb' => [
                'nullable',
                'numeric',
                'distinct',
                // Inline closure replaces validatePhoneNumberFormat
                function ($attribute, $value, $fail) {
                    if (preg_match('/phones\.(\d+)\.Phon_Numb/', $attribute, $matches)) {
                        $index = $matches[1];
                        $phone = $this->phones[$index] ?? [];
                        $countryCode = $phone['Cutr_Code'] ?? '91';

                        // Assumes getAllCountriesCollectionProperty is available in the component
                        $country = $this->getAllCountriesCollectionProperty()->firstWhere('Phon_Code', $countryCode);

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
                },
            ],
            'phones.*.Has_WtAp' => 'nullable|boolean',
            'phones.*.Has_Telg' => 'nullable|boolean',

            'landlines' => "nullable|array|max:$maxLandlines",
            'landlines.*.Cutr_Code' => 'nullable|numeric|digits_between:1,5',
            'landlines.*.Land_Numb' => 'nullable|numeric|digits_between:5,20|distinct',
            'landlines.*.Land_Type' => 'nullable|string',

            'emails' => "nullable|array|max:$maxEmails",
            'emails.*.Emai_Addr' => 'nullable|email|max:255|distinct',
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
            'references.*.Refa_Emai' => 'nullable|email|max:50|distinct',
            'references.*.Refa_Phon' => 'nullable|numeric|digits_between:5,20|distinct',
            'references.*.Refa_Rsip' => 'nullable|string|max:50',

            // Banks
            'bankAccounts' => "nullable|array|max:$maxBanks",
            'bankAccounts.*.Bank_Name_UIN' => 'nullable|exists:admn_bank_name,Bank_UIN|required_with:bankAccounts.*.Acnt_Numb',
            'bankAccounts.*.Acnt_Numb' => 'nullable|string|max:50|distinct|required_with:bankAccounts.*.Bank_Name_UIN',
            'bankAccounts.*.Bank_Brnc_Name' => 'nullable|string|max:50',
            'bankAccounts.*.Acnt_Type' => 'nullable|string|max:50|required_with:bankAccounts.*.Bank_Name_UIN',
            'bankAccounts.*.IFSC_Code' => 'nullable|string|max:11',
            'bankAccounts.*.Swift_Code' => 'nullable|string|max:11|min:8',
            'bankAccounts.*.newAttachments.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:100', // 100KB

            // Documents (Updated to remove custom closures)
            'documents' => "nullable|array|max:$maxDocs",
            'documents.*.selected_types' => 'nullable|array',
            'documents.*.selected_types.*' => 'exists:admn_docu_type_mast,Admn_Docu_Type_Mast_UIN',

            // Name: Required if types selected + Distinct check
            'documents.*.Docu_Name' => 'nullable|string|max:255|distinct|required_with:documents.*.selected_types',

            // Reg Number: Required if types selected
            'documents.*.Regn_Numb' => 'nullable|string|max:100|required_with:documents.*.selected_types',

            'documents.*.Admn_Cutr_Mast_UIN' => 'nullable|exists:admn_cutr_mast,Admn_Cutr_Mast_UIN|required_with:documents.*.Docu_Name',
            'documents.*.Auth_Issd' => 'nullable|string|max:100',
            'documents.*.Vald_From' => 'nullable|date',
            // Replaced validateDateRange with 'after'
            'documents.*.Vald_Upto' => 'nullable|date|after:documents.*.Vald_From',
            // Replaced validateDocumentAttachment with native file rules
            'documents.*.Docu_Atch_Path' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:200',

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
            'workExperiences.*.Prd_From' => 'nullable|date|before:today|required_with:workExperiences.*.Prd_To,workExperiences.*.Orga_Name',
            // Replaced custom date range logic with 'after'
            'workExperiences.*.Prd_To' => 'nullable|date|before_or_equal:today|after:workExperiences.*.Prd_From|required_with:workExperiences.*.Prd_From,workExperiences.*.Orga_Name',
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
        } elseif ($this->Prty === 'I') {
            // Individual
            // Replaced 'requireAddressTypeIfFilled' with 'required_with' checking if ANY address fields are filled
            $rules['addresses.*.Admn_Addr_Type_Mast_UIN'] = [
                'nullable',
                'distinct',
                'required_with:addresses.*.Addr,addresses.*.Loca,addresses.*.Lndm,addresses.*.Admn_PinCode_Mast_UIN',
            ];
            $rules['Prfx_UIN'] = 'nullable|integer|exists:admn_prfx_name_mast,Prfx_Name_UIN';

            $rules['FaNm'] = 'required|string|max:50'; // First Name
            $rules['Gend'] = 'required|string'; // Gender
            $rules['Brth_Dt'] = 'nullable|date|before:today'; // Birth Date

            // Replaced custom logic with 'after' rules
            $rules['Anvy_Dt'] = 'nullable|date|before_or_equal:today|after:Brth_Dt';

            // Death date must be after birth AND after anniversary
            $rules['Deth_Dt'] = 'nullable|date|before_or_equal:today|after:Brth_Dt|after:Anvy_Dt';
        }

        // 4. Conditional Properties
        if (property_exists($this, 'selectedTags')) {
            $rules['selectedTags'] = 'nullable|array';
            $rules['selectedTags.*'] = ['nullable', 'integer', 'exists:admn_tag_mast,Admn_Tag_Mast_UIN'];
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
            'Comp_LdLi.numeric' => 'The company landline must contain only digits.',
            'Comp_LdLi.digits_between' => 'The company landline must be between :min and :max digits.',
            'Comp_Desp.string' => 'The company business description must be a string.',
            'Comp_Desp.max' => 'The company business description may not be greater than :max characters.',
            'Comp_Emai.email' => 'The company email must be a valid email address.',
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
            'FcBk.regex' => 'The Facebook URL must be a valid facebook.com address.',
            'FcBk.max' => 'The Facebook URL may not be greater than :max characters.',

            'Twtr.url' => 'The Twitter URL must be a valid URL.',
            'Twtr.regex' => 'The Twitter URL must be a valid twitter.com or x.com address.',
            'Twtr.max' => 'The Twitter URL may not be greater than :max characters.',

            'LnDn.url' => 'The LinkedIn URL must be a valid URL.',
            'LnDn.regex' => 'The LinkedIn URL must be a valid linkedin.com address.',
            'LnDn.max' => 'The LinkedIn URL may not be greater than :max characters.',

            'Intg.url' => 'The Instagram URL must be a valid URL.',
            'Intg.regex' => 'The Instagram URL must be a valid instagram.com address.',
            'Intg.max' => 'The Instagram URL may not be greater than :max characters.',

            'Yaho.url' => 'The Yahoo URL must be a valid URL.',
            'Yaho.regex' => 'The Yahoo URL must be a valid yahoo.com address.',
            'Yaho.max' => 'The Yahoo URL may not be greater than :max characters.',

            'Redt.url' => 'The Reddit URL must be a valid URL.',
            'Redt.regex' => 'The Reddit URL must be a valid reddit.com address.',
            'Redt.max' => 'The Reddit URL may not be greater than :max characters.',

            'Ytb.url' => 'The YouTube URL must be a valid URL.',
            'Ytb.regex' => 'The YouTube URL must be a valid youtube.com or youtu.be address.',
            'Ytb.max' => 'The YouTube URL may not be greater than :max characters.',

            // Phones
            'phones.array' => 'The mobile numbers field must be an array.',
            'phones.*.Phon_Numb.numeric' => 'Each mobile number must contain only digits.',
            'phones.*.Phon_Numb.distinct' => 'This mobile number has been submitted previously.',
            'phones.*.Cutr_Code.numeric' => 'Each country code must contain only digits.',
            'phones.*.Cutr_Code.digits_between' => 'Each country code may not be greater than :max digits.',

            // Landlines
            'landlines.array' => 'The landline numbers field must be an array.',
            'landlines.*.Land_Numb.numeric' => 'Each landline number must contain only digits.',
            'landlines.*.Land_Numb.digits_between' => 'Each landline number must be between :min and :max digits.',
            'landlines.*.Land_Numb.distinct' => 'This landline number has been submitted previously.',
            'landlines.*.Cutr_Code.numeric' => 'Each country code must contain only digits.',
            'landlines.*.Cutr_Code.digits_between' => 'Each country code may not be greater than :max digits.',

            // Emails
            'emails.array' => 'The emails field must be an array.',
            'emails.*.Emai_Addr.email' => 'Each email address must be a valid email format.',
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
            'addresses.*.Admn_Addr_Type_Mast_UIN.required_with' => 'The address type is required when address details are filled.',

            // References
            'references.array' => 'The references field must be an array.',
            'references.max' => 'You can add a maximum of :max references.',
            'references.*.Refa_Name.string' => 'Each name must be a string.',
            'references.*.Refa_Name.max' => 'Each name may not be greater than :max characters.',
            'references.*.Refa_Emai.email' => 'Each email must be a valid email address.',
            'references.*.Refa_Emai.max' => 'Each email may not be greater than :max characters.',
            'references.*.Refa_Phon.numeric' => 'Each mobile number must contain only digits.',
            'references.*.Refa_Phon.digits_between' => 'Each mobile number must be between :min and :max digits.',
            'references.*.Refa_Rsip.string' => 'Each relationship/designation must be a string.',
            'references.*.Refa_Rsip.max' => 'Each relationship/designation may not be greater than :max characters.',
            'references.*.Refa_Name.distinct' => "This reference person's name has been submitted previously.",
            'references.*.Refa_Emai.distinct' => 'This email address has been submitted previously.',
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
            'bankAccounts.*.Acnt_Numb.distinct' => 'Account number has been entered previously',
            'bankAccounts.*.Acnt_Type.required_with' => 'Account Type is required when a bank is selected.',
            'bankAccounts.*.Acnt_Type.string' => 'Account type must be a string.',
            'bankAccounts.*.Acnt_Type.max' => 'Account type may not exceed :max characters.',
            'bankAccounts.*.IFSC_Code.string' => 'Indian Finance System code (IFSC) must be a string.',
            'bankAccounts.*.IFSC_Code.max' => 'Indian Finance System code (IFSC) may not exceed :max characters.',
            'bankAccounts.*.Swift_Code.string' => 'SWIFT code must be a string.',
            'bankAccounts.*.Swift_Code.max' => 'SWIFT code may not exceed :max characters.',
            'bankAccounts.*.Swift_Code.min' => 'SWIFT code must be at least :min characters.',
            'bankAccounts.*.newAttachments.*.mimes' => 'Document must be a PDF, JPG, PNG, WEBP file.',
            'bankAccounts.*.newAttachments.*.max' => 'Document attachment may not be greater than 100 KB.',


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
            'documents.*.Admn_Cutr_Mast_UIN.required_with' => 'The country is required when document name is provided.',
            'documents.*.Auth_Issd.string' => 'Authority issued must be a string.',
            'documents.*.Auth_Issd.max' => 'Authority issued may not exceed :max characters.',
            'documents.*.Vald_From.date' => 'Valid from must be a valid date.',
            'documents.*.Vald_Upto.date' => 'Valid upto must be a valid date.',
            'documents.*.Vald_Upto.after' => 'Valid upto must be after valid from date.',
            'documents.*.Docu_Atch_Path.file' => 'Document attachment must be a file.',
            'documents.*.Docu_Atch_Path.mimes' => 'Document attachment must be PDF, JPG, PNG, or WEBP.',
            'documents.*.Docu_Atch_Path.max' => 'Document attachment may not be greater than 200 KB.',

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
            'workExperiences.*.Prd_To.before_or_equal' => "To Date must be before today's Date.",
            'workExperiences.*.Prd_To.after' => 'The To Date must be after the From Date.',
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
            'Anvy_Dt.after' => 'The Anniversary date cannot be before the birth date.',

            'Deth_Dt.date' => 'The death date must be a valid date.',
            'Deth_Dt.before_or_equal' => 'The death date cannot be in the future.',
            'Deth_Dt.after' => 'The death date must be after the birth and anniversary date.',
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
                'Prfx_UIN.integer' => 'The prefix must be a valid ID.',
                'Prfx_UIN.exists' => 'The selected prefix is invalid.',

                'FaNm.required' => 'The first name is required.',
                'FaNm.string' => 'The first name must be a string.',
                'FaNm.max' => 'The first name may not be greater than :max characters.',
                'Gend.required' => 'The gender field is required.',
                'Brth_Dt.date' => 'The birth date must be a valid date.',
                'Brth_Dt.before' => 'The birth date must be before today.',
                'references.*.Refa_Name.distinct' => "This reference person's name has been submitted previously.",
                'references.*.Refa_Emai.distinct' => 'This email address has been submitted previously.',
                'references.*.Refa_Phon.distinct' => "This reference person's mobile has been submitted previously.",
            ];
        }

        return array_merge($commonMessages, $specificMessages);
    }

    // ========================================================================
    // HELPER FUNCTIONS
    // ========================================================================

    public function validateUniqueUrl($attribute, $value, $fail)
    {
        if (empty($value)) {
            return;
        }
        $valNorm = rtrim(strtolower($value), '/');
        $fields = ['Web', 'LnDn', 'Twtr', 'FcBk', 'Intg', 'Yaho', 'Redt', 'Ytb'];

        foreach ($fields as $field) {
            $otherVal = $this->{$field} ?? null;
            if ($otherVal && rtrim(strtolower($otherVal), '/') === $valNorm) {
                // Ensure we aren't comparing the field to itself (if called from updated hook context)
                if ($attribute && Str::contains($attribute, $field)) {
                    continue;
                }

                if ($value !== $this->$field) {
                    $fail('This URL is duplicated in ' . ucfirst($field));

                    return;
                }
            }
        }
    }
}

<?php

namespace App\Livewire\Traits;

trait WithDocumentNames
{
    public array $documentNameOptions = [];

    public function initializeWithDocumentNames(): void
    {
        $this->documentNameOptions = [
            'Aadhaar Card',
            'Passport',
            'Voter ID Card',
            'Driving License',
            'PAN Card',
            'Government Service-issued ID with photo',
            'Electricity Bill',
            'Water Bill',
            'Gas Bill',
            'Bank Account Statement or passbook',
            'Rent Agreement',
            'Letter from Employer',
            'Lease Agreement',
            'GST Registration',
            'Trade Mark Registration',
            'Import License',
            'Export License',
            'Postpaid Mobile Bill',
            'Telephone Bill',
            'Secondary School Certificate (SSC/Matriculation)',
            'Higher Secondary Certificate (HSC/Intermediate)',
            'Bachelor’s Degrees',
            'Associate Degrees/Diplomas',
            'Vocational Certificates',
            'Master’s Degrees',
            'Postgraduate Diplomas (PGDM/PGD)',
            'Certificate Programs',
            'Teacher Training Certificates',
            'Technical Certificates',
            'Healthcare Certificates',
            'Business & Management Certificates',
            'Creative Certificates',
            'Professional Degrees',
            'House / Property Tax',
            'Professional Tax',
            'Income Tax - Challan',
            'Income Tax - Return',
            'Public Provident Fund',
            'Employee Provident Fund',
            'ESIC Card',
            'Corporate Medical Insurance Card',
            'Life Insurance Premium',
            'Health Insurance Premium',
            'Vehicle Insurance',
            'Bank Guarantee',
            'Marriage Certificate',
            'Birth Certificate',
            'Death Certificate',
            'Discharge Certificate',
            'Partnership Agreement',
            'MOA',
            'No Dues Certificate',
            'Job Experience Certificate',
            'Relevant certificate',
            'Incorporation Certificate',
            'Caste Certificate',
            'Appointment Letter',
            'Relieving letter',
            'Police Verification Certificate',
            'Ration Card',
            'Amortization Schedule',
            'Loan Sanction Letter',
        ];
        sort($this->documentNameOptions);  // Keep the list sorted alphabetically
    }
}

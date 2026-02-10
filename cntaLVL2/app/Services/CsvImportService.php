<?php

namespace App\Services;

use App\Models\Admn_User_Mast;
use App\Models\Admn_Tag_Mast;
use App\Models\Admn_Cnta_Tag_Mast;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    private const MAX_IMPORT_ROWS = 500;

    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected ?int $organizationUIN = null;
    protected array $debugInfo = [];
    protected bool $limitReached = false;
    protected array $tagCache = []; // Cache for tag lookups

    /**
     * Generate hybrid unique 11-digit UIN using timestamp + random number
     */
    private function generateUniqueUIN($tableName, $primaryKeyColumn)
    {
        $maxAttempts = 100;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $newUIN = $this->generateHybridTimestampUIN();

            if ($newUIN < 15000000000 || $newUIN > 99999999999) {
                continue;
            }

            $exists = DB::table($tableName)
                ->where($primaryKeyColumn, $newUIN)
                ->exists();

            if (!$exists) {
                Log::info("Generated hybrid UIN: {$newUIN} for table: {$tableName}");
                return $newUIN;
            }

            usleep(mt_rand(1000, 5000));
        }

        throw new \Exception("Failed to generate unique hybrid UIN after {$maxAttempts} attempts for table: {$tableName}");
    }

    /**
     * Generate hybrid UIN using current timestamp and random number
     */
    private function generateHybridTimestampUIN(): int
    {
        $microtime = microtime(true);
        $timestamp = (int) ($microtime * 1000000);
        $timestampPart = $timestamp % 1000000;
        $randomPart = mt_rand(100, 999);
        $uin = 15000000000 + ($timestampPart * 1000) + $randomPart;

        if ($uin > 99999999999) {
            $simpleTimestamp = time() % 100000;
            $simpleRandom = mt_rand(1000, 9999);
            $uin = 15000000000 + ($simpleTimestamp * 10000) + $simpleRandom;
        }

        return $uin;
    }

    public function process(string $filePath, ?int $organizationUIN = null): array
    {
        $this->organizationUIN = $organizationUIN ?? session('selected_Orga_UIN');
        $this->tagCache = []; // Reset tag cache for each import

        if (!$this->organizationUIN) {
            throw new \Exception('Organization UIN is required for importing contacts. Current session org UIN: ' . session('selected_Orga_UIN'));
        }

        Log::info('Starting CSV import for organization UIN: ' . $this->organizationUIN);

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception("Could not open the file: {$filePath}");
        }

        $header = array_map('trim', fgetcsv($handle));
        $this->debugInfo['header'] = $header;
        Log::info('CSV Import Header: ', $header);

        $rowNumber = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($rowNumber > self::MAX_IMPORT_ROWS) {
                $this->limitReached = true;
                Log::info('CSV import limit reached. Halting processing of further rows.', ['limit' => self::MAX_IMPORT_ROWS]);
                break;
            }

            try {
                Log::info("Row {$rowNumber} raw data: ", $data);

                if (count($data) < count($header)) {
                    $data = array_pad($data, count($header), '');
                }

                $row = array_combine($header, $data);

                if ($row === false) {
                    throw new \Exception('Failed to combine header with row data');
                }

                Log::info("Row {$rowNumber} combined: ", $row);

                $firstName = trim($row['First Name'] ?? '');
                $organizationName = trim($row['Organization Name'] ?? '');

                Log::info("Row {$rowNumber} - First Name: '{$firstName}', Organization Name: '{$organizationName}'");

                if (empty($firstName) && empty($organizationName)) {
                    $this->skippedCount++;
                    $this->debugInfo["row_{$rowNumber}"] = 'Skipped: Both First Name and Organization Name are empty';
                    Log::info("Row {$rowNumber} skipped: Both First Name and Organization Name are empty");
                    continue;
                }

                DB::transaction(function () use ($row, $rowNumber) {
                    $prefixName = trim($row['Name Prefix'] ?? '');
                    $prefixUIN = $this->findPrefixUIN($prefixName);

                    Log::info("Row {$rowNumber} - Prefix lookup: '{$prefixName}' -> UIN: " . ($prefixUIN ?: 'NULL'));

                    // Generate unique UIN for user
                    $userUIN = $this->generateUniqueUIN('admn_user_mast', 'Admn_User_Mast_UIN');

                    $userData = [
                        'Admn_User_Mast_UIN' => $userUIN,
                        'Prfx_UIN' => $prefixUIN,
                        'Gend' => $this->normalizeGender($row['Gender'] ?? ''),
                        'FaNm' => trim($row['First Name'] ?? '') ?: 'N/A',
                        'MiNm' => trim($row['Middle Name'] ?? '') ?: null,
                        'LaNm' => trim($row['Last Name'] ?? '') ?: null,
                        'Comp_Name' => trim($row['Company Name'] ?? '') ?: null,
                        'Comp_Dsig' => trim($row['Designation'] ?? '') ?: null,
                        'Note' => trim($row['Notes'] ?? '') ?: null,
                        'Brth_Dt' => $this->parseDate($row['Birthday (DD/MM/YYYY)'] ?? null),
                        'Web' => trim($row['Website'] ?? '') ?: null,
                        'FcBk' => trim($row['Facebook'] ?? '') ?: null,
                        'Admn_Orga_Mast_UIN' => $this->organizationUIN,
                        'Is_Actv' => 100201,
                        'Is_Vf' => 100206,
                        'CrOn' => now()->format('Y-m-d H:i:s'),
                        'MoOn' => now()->format('Y-m-d H:i:s'),
                        'CrBy' => (string) session('authenticated_user_uin'),
                    ];

                    Log::info("Row {$rowNumber} - Inserting user data with UIN {$userUIN}: ", $userData);

                    // Insert user directly with generated UIN
                    $inserted = DB::table('admn_user_mast')->insert($userData);

                    if (!$inserted) {
                        throw new \Exception('Failed to insert user record');
                    }

                    Log::info("Row {$rowNumber} - Created user with UIN: {$userUIN}");

                    // Handle phone, email, and tags
                    $this->handlePhoneCreation($userUIN, $row, $rowNumber);
                    $this->handleEmailCreation($userUIN, $row, $rowNumber);
                    $this->handleTagAssignment($userUIN, $row, $rowNumber);

                    $this->debugInfo["row_{$rowNumber}"] = 'Successfully imported with UIN: ' . $userUIN;
                });

                $this->importedCount++;
                Log::info("Row {$rowNumber} - Successfully imported");
            } catch (\Exception $e) {
                $this->skippedCount++;
                $errorMsg = "Row {$rowNumber} error: " . $e->getMessage();
                $this->debugInfo["row_{$rowNumber}"] = $errorMsg;
                Log::error($errorMsg, [
                    'exception' => $e,
                    'row_data' => $row ?? null,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                continue;
            }
        }

        fclose($handle);

        Log::info("CSV Import completed - Imported: {$this->importedCount}, Skipped: {$this->skippedCount}");

        $results = [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'organization_uin' => $this->organizationUIN,
            'debug_info' => $this->debugInfo,
        ];

        if ($this->limitReached) {
            $results['limit_message'] = 'Import process stopped after reaching the maximum limit of ' . self::MAX_IMPORT_ROWS . ' rows. Any additional rows in the file were ignored.';
        }

        return $results;
    }

    /**
     * Handle tag assignment with generated UIN
     * Supports multiple tags separated by comma
     */
    private function handleTagAssignment(int $userUIN, array $row, int $rowNumber): void
    {
        // Support both 'Tag' and 'Tags' column names
        $tagValue = trim($row['Tag'] ?? $row['Tags'] ?? '');
        
        Log::info("Row {$rowNumber} - Tag value from CSV: '{$tagValue}'");

        if (empty($tagValue)) {
            Log::info("Row {$rowNumber} - No tags to assign");
            return;
        }

        try {
            // Split multiple tags by comma if provided
            $tagNames = array_map('trim', explode(',', $tagValue));
            $tagNames = array_filter($tagNames); // Remove empty values

            Log::info("Row {$rowNumber} - Processing " . count($tagNames) . " tag(s): " . implode(', ', $tagNames));

            $tagsAssigned = 0;

            foreach ($tagNames as $tagName) {
                if (empty($tagName)) {
                    continue;
                }

                Log::info("Row {$rowNumber} - Looking up tag: '{$tagName}'");

                // Find tag UIN from tag master
                $tagUIN = $this->findTagUIN($tagName);

                if (!$tagUIN) {
                    Log::warning("Row {$rowNumber} - Tag not found or invalid: '{$tagName}'");
                    continue;
                }

                Log::info("Row {$rowNumber} - Found tag UIN: {$tagUIN}");

                // Generate unique UIN for tag assignment record
                $tagAssignmentUIN = $this->generateUniqueUIN('admn_cnta_tag_mast', 'Admn_Cnta_Tag_Mast_UIN');

                $tagData = [
                    'Admn_Cnta_Tag_Mast_UIN' => $tagAssignmentUIN,
                    'Admn_User_Mast_UIN' => $userUIN,
                    'Admn_Tag_Mast_UIN' => $tagUIN,
                    'CrOn' => now()->format('Y-m-d H:i:s'),
                    'MoOn' => now()->format('Y-m-d H:i:s'),
                    'CrBy' => (string) session('authenticated_user_uin'),
                ];

                Log::info("Row {$rowNumber} - Attempting to insert tag assignment: ", $tagData);

                $inserted = DB::table('admn_cnta_tag_mast')->insert($tagData);

                if ($inserted) {
                    $tagsAssigned++;
                    Log::info("Row {$rowNumber} - Tag '{$tagName}' assigned successfully with assignment UIN: {$tagAssignmentUIN}");
                } else {
                    Log::error("Row {$rowNumber} - Failed to insert tag assignment for tag '{$tagName}' (UIN: {$tagUIN})");
                }
            }

            if ($tagsAssigned > 0) {
                Log::info("Row {$rowNumber} - Successfully assigned {$tagsAssigned} tag(s) to user {$userUIN}");
            } else {
                Log::warning("Row {$rowNumber} - No tags were successfully assigned to user {$userUIN}");
            }

        } catch (\Exception $e) {
            Log::error("Row {$rowNumber} - Exception during tag assignment: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Find tag UIN from Admn_Tag_Mast table
     * Uses caching to improve performance
     * Searches by exact match, case-insensitive, and fuzzy matching
     */
/**
 * Find tag UIN from Admn_Tag_Mast table
 * Searches for both organization-specific and system-wide tags
 * Uses caching to improve performance
 */
private function findTagUIN(string $tagName): ?int
{
    if (empty($tagName)) {
        Log::warning("findTagUIN called with empty tag name");
        return null;
    }

    // Check cache first
    $cacheKey = strtolower(trim($tagName));
    if (isset($this->tagCache[$cacheKey])) {
        Log::info("Tag found in cache: '{$tagName}' -> UIN: {$this->tagCache[$cacheKey]}");
        return $this->tagCache[$cacheKey];
    }

    try {
        Log::info("Searching for tag: '{$tagName}' in organization UIN: {$this->organizationUIN}");

        // First try exact match (case-sensitive)
        // Search for both system tags and organization-specific tags
        $tag = Admn_Tag_Mast::query()
            ->where('Name', $tagName)
            ->where(function($q) {
                $q->where('CrBy', 'System')  // System-wide tags
                  ->orWhere('Admn_Orga_Mast_UIN', $this->organizationUIN);  // Organization-specific tags
            })
            ->first();

        if ($tag) {
            Log::info("Tag found (exact match): '{$tagName}' -> UIN: {$tag->Admn_Tag_Mast_UIN} (Type: " . ($tag->CrBy === 'System' ? 'System' : 'Organization') . ")");
            $this->tagCache[$cacheKey] = $tag->Admn_Tag_Mast_UIN;
            return $tag->Admn_Tag_Mast_UIN;
        }

        Log::info("Exact match failed, trying case-insensitive for: '{$tagName}'");

        // Try case-insensitive match
        $tag = Admn_Tag_Mast::query()
            ->whereRaw('LOWER(Name) = LOWER(?)', [$tagName])
            ->where(function($q) {
                $q->where('CrBy', 'System')
                  ->orWhere('Admn_Orga_Mast_UIN', $this->organizationUIN);
            })
            ->first();

        if ($tag) {
            Log::info("Tag found (case-insensitive): '{$tagName}' -> UIN: {$tag->Admn_Tag_Mast_UIN} (Type: " . ($tag->CrBy === 'System' ? 'System' : 'Organization') . ")");
            $this->tagCache[$cacheKey] = $tag->Admn_Tag_Mast_UIN;
            return $tag->Admn_Tag_Mast_UIN;
        }

        Log::info("Case-insensitive match failed, trying fuzzy match for: '{$tagName}'");

        // Try LIKE match for partial matching
        $tag = Admn_Tag_Mast::query()
            ->where('Name', 'LIKE', "%{$tagName}%")
            ->where(function($q) {
                $q->where('CrBy', 'System')
                  ->orWhere('Admn_Orga_Mast_UIN', $this->organizationUIN);
            })
            ->first();

        if ($tag) {
            Log::info("Tag found (fuzzy match): '{$tagName}' -> actual tag: '{$tag->Name}' -> UIN: {$tag->Admn_Tag_Mast_UIN} (Type: " . ($tag->CrBy === 'System' ? 'System' : 'Organization') . ")");
            $this->tagCache[$cacheKey] = $tag->Admn_Tag_Mast_UIN;
            return $tag->Admn_Tag_Mast_UIN;
        }

        Log::warning("Tag not found after all attempts: '{$tagName}' for organization UIN: {$this->organizationUIN}");

        // List available tags for debugging (both system and organization-specific)
        $systemTags = Admn_Tag_Mast::query()
            ->where('CrBy', 'System')
            ->pluck('Name')
            ->toArray();
        
        $orgTags = Admn_Tag_Mast::query()
            ->where('Admn_Orga_Mast_UIN', $this->organizationUIN)
            ->pluck('Name')
            ->toArray();
        
        Log::info("Available system-wide tags: " . implode(', ', $systemTags));
        Log::info("Available organization tags: " . implode(', ', $orgTags));

        return null;

    } catch (\Exception $e) {
        Log::error("Error looking up tag '{$tagName}': " . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

    /**
     * Handle phone creation with generated UIN
     */
    private function handlePhoneCreation(int $userUIN, array $row, int $rowNumber): void
    {
        $phoneValue = trim($row['Phone'] ?? '');
        if (empty($phoneValue)) {
            return;
        }

        try {
            $countryCode = trim($row['Contry Code'] ?? '');
            $phoneLabel = trim($row['Phone Label'] ?? 'Self');

            $phoneUIN = $this->generateUniqueUIN('admn_cnta_phon_mast', 'Admn_Cnta_Phon_Mast_UIN');

            $phoneData = [
                'Admn_Cnta_Phon_Mast_UIN' => $phoneUIN,
                'Admn_User_Mast_UIN' => $userUIN,
                'Phon_Numb' => $phoneValue,
                'Cutr_Code' => $countryCode ?: '91',
                'Phon_Type' => $this->normalizePhoneType($phoneLabel),
                'Is_Prmy' => true,
                'Has_WtAp' => false,
                'Has_Telg' => false,
                'CrOn' => now()->format('Y-m-d H:i:s'),
                'MoOn' => now()->format('Y-m-d H:i:s'),
                'CrBy' => (string) session('authenticated_user_uin'),
            ];

            Log::info("Row {$rowNumber} - Adding phone with UIN {$phoneUIN}: ", $phoneData);

            $inserted = DB::table('admn_cnta_phon_mast')->insert($phoneData);

            if ($inserted) {
                Log::info("Row {$rowNumber} - Phone added successfully with UIN: {$phoneUIN}");
            } else {
                Log::warning("Row {$rowNumber} - Failed to insert phone record");
            }
        } catch (\Exception $e) {
            Log::warning("Row {$rowNumber} - Failed to add phone: " . $e->getMessage());
        }
    }

    /**
     * Handle email creation with generated UIN
     */
    private function handleEmailCreation(int $userUIN, array $row, int $rowNumber): void
    {
        $emailValue = trim($row['Email'] ?? '');
        if (empty($emailValue) || !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
            if (!empty($emailValue)) {
                Log::warning("Row {$rowNumber} - Invalid email format: {$emailValue}");
            }
            return;
        }

        try {
            $emailUIN = $this->generateUniqueUIN('admn_cnta_emai_mast', 'Admn_Cnta_Emai_Mast_UIN');

            $emailData = [
                'Admn_Cnta_Emai_Mast_UIN' => $emailUIN,
                'Admn_User_Mast_UIN' => $userUIN,
                'Emai_Addr' => $emailValue,
                'Emai_Type' => 'personal',
                'Is_Prmy' => true,
                'CrOn' => now()->format('Y-m-d H:i:s'),
                'MoOn' => now()->format('Y-m-d H:i:s'),
                'CrBy' => (string) session('authenticated_user_uin'),
            ];

            Log::info("Row {$rowNumber} - Adding email with UIN {$emailUIN}: ", $emailData);

            $inserted = DB::table('admn_cnta_emai_mast')->insert($emailData);

            if ($inserted) {
                Log::info("Row {$rowNumber} - Email added successfully with UIN: {$emailUIN}");
            } else {
                Log::warning("Row {$rowNumber} - Failed to insert email record");
            }
        } catch (\Exception $e) {
            Log::warning("Row {$rowNumber} - Failed to add email: " . $e->getMessage());
        }
    }

    private function normalizeGender(string $genderValue): ?string
    {
        if (empty($genderValue)) {
            return null;
        }

        $genderValue = strtolower(trim($genderValue));

        $genderMappings = [
            'male' => 'male',
            'm' => 'male',
            'man' => 'male',
            'boy' => 'male',
            'female' => 'female',
            'f' => 'female',
            'woman' => 'female',
            'girl' => 'female',
            'other' => 'other',
            'non-binary' => 'other',
            'prefer not to say' => 'other',
            'transgender' => 'other',
        ];

        return $genderMappings[$genderValue] ?? null;
    }

    private function findPrefixUIN(string $prefixName): ?int
    {
        if (empty($prefixName)) {
            return null;
        }

        try {
            $prefix = \App\Models\Admn_Prfx_Name_Mast::active()
                ->where('Prfx_Name', $prefixName)
                ->first();

            if (!$prefix) {
                $prefix = \App\Models\Admn_Prfx_Name_Mast::active()
                    ->whereRaw('LOWER(Prfx_Name) = LOWER(?)', [$prefixName])
                    ->first();
            }

            if (!$prefix) {
                $searchTerm = strtolower(trim($prefixName));

                $variations = [
                    'mr' => 'Mr.',
                    'mrs' => 'Mrs.',
                    'ms' => 'Ms.',
                    'dr' => 'Dr.',
                    'prof' => 'Prof.',
                    'professor' => 'Prof.',
                    'sir' => 'Sir',
                    'madam' => 'Madam',
                    'miss' => 'Miss',
                ];

                if (isset($variations[$searchTerm])) {
                    $prefix = \App\Models\Admn_Prfx_Name_Mast::active()
                        ->where('Prfx_Name', $variations[$searchTerm])
                        ->first();
                }
            }

            if ($prefix) {
                Log::info("Prefix found: '{$prefixName}' -> UIN: {$prefix->Prfx_Name_UIN}");
                return $prefix->Prfx_Name_UIN;
            } else {
                Log::info("Prefix not found: '{$prefixName}' - setting to NULL");
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Error looking up prefix '{$prefixName}': " . $e->getMessage());
            return null;
        }
    }

    private function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Date parsing failed for: {$date}");
            return null;
        }
    }

    private function normalizePhoneType($label): string
    {
        $label = strtolower(trim($label));

        $mapping = [
            'work' => 'work',
            'home' => 'home',
            'self' => 'self',
            'mobile' => 'self',
            'cell' => 'self',
            'cellular' => 'self',
            'personal' => 'self',
            'office' => 'work',
            'business' => 'work',
            'company' => 'work',
            'main' => 'work',
            'other' => 'self',
        ];

        return $mapping[$label] ?? 'self';
    }
}
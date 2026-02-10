<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Admn_User_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_user_mast';
    protected $primaryKey = 'Admn_User_Mast_UIN';
    public $incrementing = false;  // Tell Laravel this is not auto-incrementing
    protected $keyType = 'string';  // Since you're using a large timestamp-based number
    public $timestamps = false;  // Since you're using CrOn/MoOn

    protected $fillable = [
        'Admn_User_Mast_UIN',  // Primary Key
        'Admn_Orga_Mast_UIN',
        'Admn_Grup_Mast_UIN',  // Group UIN foreign key
        // Audit fields
        'CrBy',  // Created BY
        'CrOn',  // Created ON
        'MoBy',  // Modified BY
        'MoOn',  // Modified ON
        'VfBy',  // Verified BY
        'VfOn',  // Verified ON
        'Del_By',  // Deleted BY
        'Del_On',  // Deleted ON
        'Prfx_UIN',  // prefix UIN
        'FaNm',  // first_name
        'MiNm',  // middle_name
        'LaNm',  // last_name
        'Gend',  // gender
        'Blood_Grp',  // blood group
        'Prfl_Pict',  // profile_picture
        'Brth_Dt',  // birth_date
        'Anvy_Dt',  // anniversary_date
        'Deth_Dt',  // death_date (added)
        'Comp_Name',  // company_name
        'Comp_Dsig',  // company_designation
        'Comp_LdLi',  // company_landline
        'Comp_Desp',  // company_description
        'Comp_Emai',  // company_email
        'Comp_Web',  // company_website
        'Comp_Addr',  // company_address
        'Prfl_Name',  // profession_name
        'Prfl_Addr',  // profession_address
        // Social media links
        'Web',  // website
        'FcBk',  // facebook
        'Twtr',  // twitter
        'LnDn',  // linkedin
        'Intg',  // instagram
        'Yaho',  // yahoo
        'Redt',  // reddit
        'Ytb',  // youtube
        'Note',  // notes
        'Is_Actv',  // is active
        'Is_Vf',  // is verified
        'Prty',  // party type
    ];

    protected $casts = [
        'Prty' => 'string',
        'Brth_Dt' => 'date',
        'Anvy_Dt' => 'date',
        'Deth_Dt' => 'date',  // Added death date casting
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
        'Del_On' => 'datetime',
        'Prfx_UIN' => 'integer',  // Cast as integer
        'Admn_Orga_Mast_UIN' => 'integer',
        'Is_Actv' => 'integer',
        'Is_Vf' => 'integer',
    ];

    // Keep all your existing relationships
    public function addresses(): HasMany
    {
        return $this->hasMany(Admn_Cnta_Addr_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Admn_Cnta_Emai_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(Admn_Cnta_Phon_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    // CORRECTED NAME

    public function referencePersons(): HasMany
    {
        return $this->hasMany(Admn_Cnta_Refa_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Get party type label
     */
    public function getPartyTypeAttribute(): string
    {
        return match ($this->Prty) {
            'I' => 'Individual',
            'B' => 'Business',
            default => 'Unknown'
        };
    }

    /**
     * Check if contact is individual
     */
    public function isIndividual(): bool
    {
        return $this->Prty === 'I';
    }

    /**
     * Check if contact is business
     */
    public function isBusiness(): bool
    {
        return $this->Prty === 'B';
    }

    /**
     * Scope to get individual contacts only
     */
    public function scopeIndividuals($query)
    {
        return $query->where('Prty', 'I');
    }

    /**
     * Scope to get business contacts only
     */
    public function scopeBusinesses($query)
    {
        return $query->where('Prty', 'B');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Admn_Tag_Mast::class,
            'admn_cnta_tag_mast',
            'Admn_User_Mast_UIN',
            'Admn_Tag_Mast_UIN',
            'Admn_User_Mast_UIN',
            'Admn_Tag_Mast_UIN'
        )->withPivot(['CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn']);
    }

    // Updated accessors to handle prefix properly
    public function getFullNameAttribute(): string
    {
        // You'll need to handle Prfx_UIN lookup if you have a prefix table
        $prefix = $this->Prfx_UIN ? $this->getPrefixText() : null;

        $name = trim(implode(' ', array_filter([
            $prefix,
            $this->FaNm,
            $this->MiNm,
            $this->LaNm
        ])));

        return $name ?: 'Unnamed Contact';
    }

    // Helper method to get prefix text - you'll need to implement based on your prefix table
    protected function getPrefixText(): ?string
    {
        // If you have a prefix master table, you can join/lookup here
        // For now, returning null - implement based on your prefix table structure
        return null;
    }

    public function prefix(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admn_Prfx_Name_Mast::class, 'Prfx_UIN', 'Prfx_Name_UIN');
    }

    public function getInitialsAttribute(): string
    {
        $initials = '';
        if ($this->FaNm)
            $initials .= strtoupper(substr($this->FaNm, 0, 1));
        if ($this->LaNm)
            $initials .= strtoupper(substr($this->LaNm, 0, 1));

        return $initials ?: strtoupper(substr($this->FaNm ?? 'U', 0, 2));
    }

    // Removed avatar_color accessor since it's not in migration
    // If you need it, add Avtr_Colr to migration

    public function getPrimaryPhoneAttribute(): ?Admn_Cnta_Phon_Mast
    {
        if ($this->relationLoaded('phones')) {
            return $this->phones->where('Is_Prmy', true)->first()
                ?? $this->phones->where('Phon_Type', 'mobile')->first()
                ?? $this->phones->first();
        }

        return $this->phones()->where('Is_Prmy', true)->first()
            ?? $this->phones()->where('Phon_Type', 'mobile')->first()
            ?? $this->phones()->first();
    }

    public function getPrimaryEmailAttribute(): ?Admn_Cnta_Emai_Mast
    {
        if ($this->relationLoaded('emails')) {
            return $this->emails->where('Is_Prmy', true)->first()
                ?? $this->emails->first();
        }

        return $this->emails()->where('Is_Prmy', true)->first()
            ?? $this->emails()->first();
    }

    public function getPrimaryAddressAttribute(): ?Admn_Cnta_Addr_Mast
    {
        if ($this->relationLoaded('addresses')) {
            return $this->addresses->where('Is_Prmy', true)->first()
                ?? $this->addresses->where('Tag', 'home')->first()
                ?? $this->addresses->first();
        }

        return $this->addresses()->where('Is_Prmy', true)->first()
            ?? $this->addresses()->where('Tag', 'home')->first()
            ?? $this->addresses()->first();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->Prfl_Pict ? asset('storage/' . $this->Prfl_Pict) : null;
    }

    // Keep all your existing scopes
    public function scopeActive($query)
    {
        return $query->where('Is_Actv', 100201);
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q
                ->where('FaNm', 'like', "%{$search}%")
                ->orWhere('LaNm', 'like', "%{$search}%")
                ->orWhere('MiNm', 'like', "%{$search}%")
                ->orWhere('Comp_Name', 'like', "%{$search}%")
                ->orWhere('Comp_Dsig', 'like', "%{$search}%")
                ->orWhere('Prfl_Name', 'like', "%{$search}%")
                ->orWhereHas('emails', function ($emailQuery) use ($search) {
                    $emailQuery->where('Emai_Addr', 'like', "%{$search}%");
                })
                ->orWhereHas('phones', function ($phoneQuery) use ($search) {
                    $phoneQuery->where('Phon_Numb', 'like', "%{$search}%");
                });
        });
    }

    public function scopeWithEmail($query)
    {
        return $query->whereHas('emails');
    }

    public function scopeWithPhone($query)
    {
        return $query->whereHas('phones');
    }

    public function scopeWithCompany($query)
    {
        return $query->whereNotNull('Comp_Name')->where('Comp_Name', '!=', '');
    }

    public function scopeByGender($query, $gender)
    {
        if (empty($gender)) {
            return $query;
        }

        return $query->where('Gend', $gender);
    }

    public function scopeByTag($query, $tagName)
    {
        return $query->whereHas('tags', function ($q) use ($tagName) {
            $q->where('Name', $tagName);
        });
    }

    // Keep all your existing methods
    public function getSocialLinks(): array
    {
        return array_filter([
            'website' => $this->Web,
            'facebook' => $this->FcBk,
            'twitter' => $this->Twtr,
            'linkedin' => $this->LnDn,
            'instagram' => $this->Intg,
            'youtube' => $this->Ytb,
            'yahoo' => $this->Yaho,
            'reddit' => $this->Redt,
        ]);
    }

    // Backward compatibility
    public function getFirstNameAttribute()
    {
        return $this->FaNm;
    }

    public function getLastNameAttribute()
    {
        return $this->LaNm;
    }

    public function getCompanyNameAttribute()
    {
        return $this->Comp_Name;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Admn_Grup_Mast::class, 'Admn_Grup_Mast_UIN', 'Admn_Grup_Mast_UIN');
    }

    /**
     * Get all bank accounts for this user.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(Admn_User_Bank_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Get the primary bank account.
     */
    public function primaryBankAccount()
    {
        return $this->bankAccounts()->where('Prmy', 1)->first();
    }

    /**
     * Get all active bank accounts.
     */
    public function activeBankAccounts(): HasMany
    {
        return $this->bankAccounts()->where('Stau', 100201);
    }

    /**
     * Get all documents for this user.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Admn_Docu_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Get all active documents.
     */
    public function activeDocuments(): HasMany
    {
        return $this->documents()->where('Stau', 100201);
    }

    /**
     * Get verified documents.
     */
    public function verifiedDocuments(): HasMany
    {
        return $this->documents()->where('Stau', 100201)->whereNotNull('VrBy');
    }

    /**
     * Get pending verification documents.
     */
    public function pendingDocuments(): HasMany
    {
        return $this->documents()->where('Stau', 100201)->whereNull('VrBy');
    }

}

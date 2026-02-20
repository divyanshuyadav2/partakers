<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Admn_User_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_user_mast';
    protected $primaryKey = 'Admn_User_Mast_UIN';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'Admn_User_Mast_UIN',
        'Admn_Orga_Mast_UIN',
        'Admn_Grup_Mast_UIN',
        'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn', 'Del_By', 'Del_On',
        'Prfx_UIN', 'FaNm', 'MiNm', 'LaNm', 'Gend', 'Blood_Grp', 'Prfl_Pict',
        'Brth_Dt', 'Anvy_Dt', 'Deth_Dt', 'Comp_Name', 'Comp_Dsig', 'Comp_LdLi',
        'Comp_Desp', 'Comp_Emai', 'Comp_Web', 'Comp_Addr', 'Prfl_Name', 'Prfl_Addr',
        'Web', 'FcBk', 'Twtr', 'LnDn', 'Intg', 'Yaho', 'Redt', 'Ytb', 'Note',
        'Is_Actv', 'Is_Vf', 'Prty',
    ];

    protected $casts = [
        'Prty' => 'string',
        'Brth_Dt' => 'date',
        'Anvy_Dt' => 'date',
        'Deth_Dt' => 'date',
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
        'Del_On' => 'datetime',
        'Prfx_UIN' => 'integer',
        'Admn_Orga_Mast_UIN' => 'integer',
        'Is_Actv' => 'integer',
        'Is_Vf' => 'integer',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

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

    public function referencePersons(): HasMany
    {
        return $this->hasMany(Admn_Cnta_Refa_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
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

    public function prefix(): BelongsTo
    {
        return $this->belongsTo(Admn_Prfx_Name_Mast::class, 'Prfx_UIN', 'Prfx_Name_UIN');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Admn_Grup_Mast::class, 'Admn_Grup_Mast_UIN', 'Admn_Grup_Mast_UIN');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(Admn_User_Bank_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Admn_Docu_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(AdmnUserEducMast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    public function latestEducation(): HasOne
    {
        return $this->hasOne(AdmnUserEducMast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN')
                    ->orderBy('Cmpt_Year', 'desc')
                    ->orderBy('Admn_User_Educ_Mast_UIN', 'desc');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(AdmnUserSkilMast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    public function employments(): HasMany
    {
        return $this->hasMany(AdmnUserWorkMast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Get current employment (where Prd_To is NULL, or most recent)
     */
    public function currentEmployment(): HasOne
    {
        return $this->hasOne(AdmnUserWorkMast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN')
                    ->orderByRaw('CASE WHEN Prd_To IS NULL THEN 0 ELSE 1 END')
                    ->orderBy('Prd_To', 'desc')
                    ->orderBy('Prd_From', 'desc');
    }

    // ============================================================
    // ACCESSORS & HELPERS
    // ============================================================

    public function getFullNameAttribute(): string
    {
        $prefix = $this->Prfx_UIN ? $this->getPrefixText() : null;
        $name = trim(implode(' ', array_filter([
            $prefix,
            $this->FaNm,
            $this->MiNm,
            $this->LaNm
        ])));
        return $name ?: 'Unnamed Contact';
    }

    protected function getPrefixText(): ?string
    {
        return null;
    }

    public function getInitialsAttribute(): string
    {
        $initials = '';
        if ($this->FaNm) $initials .= strtoupper(substr($this->FaNm, 0, 1));
        if ($this->LaNm) $initials .= strtoupper(substr($this->LaNm, 0, 1));
        return $initials ?: strtoupper(substr($this->FaNm ?? 'U', 0, 2));
    }

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
            return $this->emails->where('Is_Prmy', true)->first() ?? $this->emails->first();
        }
        return $this->emails()->where('Is_Prmy', true)->first() ?? $this->emails()->first();
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

    public function getPartyTypeAttribute(): string
    {
        return match ($this->Prty) {
            'I' => 'Individual',
            'B' => 'Business',
            default => 'Unknown'
        };
    }

    public function isIndividual(): bool
    {
        return $this->Prty === 'I';
    }

    public function isBusiness(): bool
    {
        return $this->Prty === 'B';
    }

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

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('Is_Actv', 100201);
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('FaNm', 'like', "%{$search}%")
                ->orWhere('LaNm', 'like', "%{$search}%")
                ->orWhere('MiNm', 'like', "%{$search}%")
                ->orWhere('Comp_Name', 'like', "%{$search}%")
                ->orWhere('Comp_Dsig', 'like', "%{$search}%")
                ->orWhere('Prfl_Name', 'like', "%{$search}%")
                ->orWhereHas('emails', fn($emailQuery) => $emailQuery->where('Emai_Addr', 'like', "%{$search}%"))
                ->orWhereHas('phones', fn($phoneQuery) => $phoneQuery->where('Phon_Numb', 'like', "%{$search}%"));
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
        if (empty($gender)) return $query;
        return $query->where('Gend', $gender);
    }

    public function scopeByTag($query, $tagName)
    {
        return $query->whereHas('tags', fn($q) => $q->where('Name', $tagName));
    }

    public function scopeIndividuals($query)
    {
        return $query->where('Prty', 'I');
    }

    public function scopeBusinesses($query)
    {
        return $query->where('Prty', 'B');
    }

    // ============================================================
    // BACKWARD COMPATIBILITY
    // ============================================================

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

    public function primaryBankAccount()
    {
        return $this->bankAccounts()->where('Prmy', 1)->first();
    }

    public function activeBankAccounts(): HasMany
    {
        return $this->bankAccounts()->where('Stau', 100201);
    }

    public function activeDocuments(): HasMany
    {
        return $this->documents()->where('Stau', 100201);
    }

    public function verifiedDocuments(): HasMany
    {
        return $this->documents()->where('Stau', 100201)->whereNotNull('VrBy');
    }

    public function pendingDocuments(): HasMany
    {
        return $this->documents()->where('Stau', 100201)->whereNull('VrBy');
    }
}
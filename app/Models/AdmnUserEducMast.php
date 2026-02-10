<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmnUserEducMast extends Model
{
    protected $table = 'admn_user_educ_mast';
    protected $primaryKey = 'Admn_User_Educ_Mast_UIN';
    public $timestamps = false;
    protected $guarded = [];

    protected $fillable = [
        'Admn_User_Educ_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Deg_Name',
        'Inst_Name',
        'Cmpt_Year',
        'Admn_Cutr_Mast_UIN',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'Cmpt_Year' => 'integer',
        'Admn_User_Mast_UIN' => 'integer',
        'Admn_Cutr_Mast_UIN' => 'integer',
    ];

    // 🔗 Relationships

    /**
     * Get the contact that owns the education record.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Get the country for this education.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Admn_Cutr_Mast::class, 'Admn_Cutr_Mast_UIN', 'Admn_Cutr_Mast_UIN');
    }

    // 🔎 Scopes

    /**
     * Filter by contact.
     */
    public function scopeForContact($query, int $contactId)
    {
        return $query->where('Admn_User_Mast_UIN', $contactId);
    }

    /**
     * Get with country details.
     */
    public function scopeWithCountry($query)
    {
        return $query->with('country');
    }

    // 🔑 Accessors & Helpers

    /**
     * Get display name for education.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->Deg_Name,
            $this->Inst_Name,
            $this->Cmpt_Year ? "({$this->Cmpt_Year})" : null,
        ]);

        return implode(' - ', $parts) ?: 'Education Record';
    }

    /**
     * Get country name.
     */
    public function getCountryNameAttribute(): ?string
    {
        return $this->country?->Name ?? null;
    }
}

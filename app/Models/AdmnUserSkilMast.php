<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmnUserSkilMast extends Model
{
    protected $table = 'admn_user_skil_mast';
    protected $primaryKey = 'Admn_User_Skil_Mast_UIN';
    public $timestamps = false;
    protected $guarded = [];

    protected $fillable = [
        'Admn_User_Skil_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Skil_Type',
        'Skil_Type_1',
        'Skil_Name',
        'Profc_Lvl',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'Profc_Lvl' => 'integer',
        'Admn_User_Mast_UIN' => 'integer',
    ];

    // 🔗 Relationships

    /**
     * Get the contact that owns this skill.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
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
     * Filter by skill level.
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('Profc_Lvl', $level);
    }

    /**
     * Get expert level skills (8-10).
     */
    public function scopeExpert($query)
    {
        return $query->whereIn('Profc_Lvl', [8, 9, 10]);
    }

    /**
     * Filter by skill type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('Skil_Type', $type);
    }

    // 🔑 Accessors & Helpers

    /**
     * Get proficiency level as text.
     */
    public function getProficiencyTextAttribute(): string
    {
        return match ($this->Profc_Lvl) {
            1, 2, 3 => 'Beginner',
            4, 5, 6 => 'Intermediate',
            7, 8 => 'Advanced',
            9, 10 => 'Expert',
            default => 'Not Specified',
        };
    }

    /**
     * Get display name for skill.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->Skil_Name,
            $this->Skil_Type ? "({$this->Skil_Type})" : null,
            $this->Profc_Lvl ? "Level: {$this->Profc_Lvl}/10" : null,
        ]);

        return implode(' - ', $parts) ?: 'Skill';
    }

    /**
     * Get proficiency percentage.
     */
    public function getProficiencyPercentageAttribute(): int
    {
        return ($this->Profc_Lvl ?? 0) * 10;
    }
}

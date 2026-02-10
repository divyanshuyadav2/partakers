<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmnUserWorkMast extends Model
{
    protected $table = 'admn_user_work_mast';
    protected $primaryKey = 'Admn_User_Work_Mast_UIN';
    public $timestamps = false;
    protected $guarded = [];

    protected $fillable = [
        'Admn_User_Work_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Orga_Name',
        'Dsgn',
        'Prd_From',
        'Prd_To',
        'Orga_Type',
        'Job_Desp',
        'Work_Type',
        'Admn_Cutr_Mast_UIN',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'Prd_From' => 'date',
        'Prd_To' => 'date',
        'Admn_User_Mast_UIN' => 'integer',
        'Admn_Cutr_Mast_UIN' => 'integer',
    ];

    // 🔗 Relationships

    /**
     * Get the contact that owns this work experience.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Get the country for this work experience.
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
     * Get current work experience.
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('Prd_To')->orWhere('Prd_To', '>=', now()->toDateString());
    }

    /**
     * Get past work experience.
     */
    public function scopePast($query)
    {
        return $query->where('Prd_To', '<', now()->toDateString());
    }

    /**
     * Filter by work type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('Work_Type', $type);
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
     * Get duration in years or months.
     */
    public function getDurationAttribute(): string
    {
        if (!$this->Prd_From) {
            return 'No dates specified';
        }

        $from = $this->Prd_From;
        $to = $this->Prd_To ?? now();

        $months = $from->diffInMonths($to);
        $years = floor($months / 12);
        $remaining = $months % 12;

        if ($years > 0 && $remaining > 0) {
            return "{$years}y {$remaining}m";
        } elseif ($years > 0) {
            return "{$years} year" . ($years > 1 ? 's' : '');
        } else {
            return "{$months} month" . ($months > 1 ? 's' : '');
        }
    }

    /**
     * Check if this is current position.
     */
    public function isCurrentAttribute(): bool
    {
        return is_null($this->Prd_To) || $this->Prd_To->isFuture();
    }

    /**
     * Get display name for work experience.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->Dsgn ?? 'Position Unknown',
            $this->Orga_Name ? "at {$this->Orga_Name}" : null,
            $this->Prd_From ? "({$this->duration})" : null,
        ]);

        return implode(' ', $parts) ?: 'Work Experience';
    }

    /**
     * Get work type label.
     */
    public function getWorkTypeLabel(): string
    {
        return match ($this->Work_Type) {
            'Full' => 'Full Time',
            'Part' => 'Part Time',
            'WFH' => 'Work From Home',
            default => $this->Work_Type,
        };
    }
}

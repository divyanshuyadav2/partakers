<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Admn_Dist_Mast extends Model
{
    protected $table = 'admn_dist_mast';
    protected $primaryKey = 'Admn_Dist_Mast_UIN';
    public $timestamps = false;

    /**
     * CORRECTED: Removed 'Admn_Cutr_Mast_UIN' as it's no longer in the table.
     */
    protected $fillable = [
        'Admn_Stat_Mast_UIN', 'Name', 'Code',
        'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn'
    ];

    protected $casts = [
        'CrOn' => 'datetime', 'MoOn' => 'datetime', 'VfOn' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    /**
     * A District has one Country through its State.
     */
    public function country(): HasOneThrough
    {
        return $this->hasOneThrough(
            Admn_Cutr_Mast::class, // The final model we want
            Admn_Stat_Mast::class, // The intermediate model
            'Admn_Stat_Mast_UIN',  // Foreign key on State table (linking District to State)
            'Admn_Cutr_Mast_UIN',  // Foreign key on Country table (linking State to Country)
            'Admn_Stat_Mast_UIN',  // Local key on District table
            'Admn_Cutr_Mast_UIN'   // Local key on State table
        );
    }

    /** A District belongs to one State. */
    public function state(): BelongsTo
    {
        return $this->belongsTo(Admn_Stat_Mast::class, 'Admn_Stat_Mast_UIN');
    }

    /** A District has many PinCodes. */
    public function pinCodes(): HasMany
    {
        return $this->hasMany(Admn_PinCode_Mast::class, 'Admn_Dist_Mast_UIN');
    }

    // --- SCOPES ---

    /**
     * CORRECTED: This now queries through the 'state' relationship
     * because the direct country foreign key was removed.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->whereHas('state', function ($q) use ($countryId) {
            $q->where('Admn_Cutr_Mast_UIN', $countryId);
        });
    }

    public function scopeByState($query, $stateId)
    {
        return $query->where('Admn_Stat_Mast_UIN', $stateId);
    }
    
    public function scopeActive($query)
    {
        return $query->whereNotNull('Name')->where('Name', '!=', '');
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('Name', 'like', "%{$search}%")
              ->orWhere('Code', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->Name . ($this->Code ? " ({$this->Code})" : '');
    }

    public function getFullLocationAttribute(): string
    {
        $location = [];
        
        if ($this->Name) $location[] = $this->Name;
        if ($this->relationLoaded('state') && $this->state) $location[] = $this->state->Name;
        if ($this->relationLoaded('country') && $this->country) $location[] = $this->country->Name;
        
        return implode(', ', $location);
    }
}
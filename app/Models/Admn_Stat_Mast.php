<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Admn_Stat_Mast extends Model
{
    protected $table = 'admn_stat_mast';
    protected $primaryKey = 'Admn_Stat_Mast_UIN';
    public $timestamps = false;

    protected $fillable = [
        'Admn_Cutr_Mast_UIN', 'Name', 'Code',
        'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn'
    ];

    protected $casts = [
        'CrOn' => 'datetime', 'MoOn' => 'datetime', 'VfOn' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    /** A State belongs to one Country. */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Admn_Cutr_Mast::class, 'Admn_Cutr_Mast_UIN');
    }

    /** A State has many Districts. */
    public function districts(): HasMany
    {
        return $this->hasMany(Admn_Dist_Mast::class, 'Admn_Stat_Mast_UIN');
    }

    /**
     * A State has many PinCodes through its Districts.
     */
    public function pinCodes(): HasManyThrough
    {
        return $this->hasManyThrough(
            Admn_PinCode_Mast::class, // The final model we want
            Admn_Dist_Mast::class,    // The intermediate model
            'Admn_Stat_Mast_UIN',     // Foreign key on District table
            'Admn_Dist_Mast_UIN',     // Foreign key on PinCode table
            'Admn_Stat_Mast_UIN',     // Local key on State table
            'Admn_Dist_Mast_UIN'      // Local key on District table
        );
    }
    // Scopes
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('Admn_Cutr_Mast_UIN', $countryId);
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
}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Admn_PinCode_Mast extends Model
{
    protected $table = 'admn_pincode_mast';
    protected $primaryKey = 'Admn_PinCode_Mast_UIN';
    public $timestamps = false;

    /**
     * CORRECTED: Removed redundant foreign keys. A PinCode only needs to know its District.
     */
    protected $fillable = [
        'Admn_Dist_Mast_UIN', 'Code', 'Area_Name', 'Post_Offi',
        'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfBy', 'VfOn'
    ];

    protected $casts = [
        'CrOn' => 'datetime', 'MoOn' => 'datetime', 'VfOn' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    /**
     * A PinCode has one State through its District.
     */
    public function state(): HasOneThrough
    {
        return $this->hasOneThrough(
            Admn_Stat_Mast::class, // Final model
            Admn_Dist_Mast::class, // Intermediate model
            'Admn_Dist_Mast_UIN',  // FK on District table
            'Admn_Stat_Mast_UIN',  // FK on State table
            'Admn_Dist_Mast_UIN',  // Local key on PinCode table
            'Admn_Stat_Mast_UIN'   // Local key on District table
        );
    }

    /** A PinCode belongs to one District. */
    public function district(): BelongsTo
    {
        return $this->belongsTo(Admn_Dist_Mast::class, 'Admn_Dist_Mast_UIN');
    }
    /** A PinCode has many Addresses. */
    public function addresses()
    {
        return $this->hasMany(Admn_Cnta_Addr_Mast::class, 'Admn_PinCode_Mast_UIN');
    }

    // Scopes
    public function scopeByCountry($query, $countryId)
    {
        return $query->whereHas('district.state', function ($q) use ($countryId) {
            $q->where('Admn_Cutr_Mast_UIN', $countryId);
        });
    }

    /**
     * CORRECTED: Queries through the district relationship.
     */
    public function scopeByState($query, $stateId)
    {
        return $query->whereHas('district', function ($q) use ($stateId) {
            $q->where('Admn_Stat_Mast_UIN', $stateId);
        });
    }

    public function scopeByDistrict($query, $districtId)
    {
        return $query->where('Admn_Dist_Mast_UIN', $districtId);
    }


    public function scopeByCode($query, $code)
    {
        return $query->where('Code', $code);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('Code')->where('Code', '!=', '');
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('Code', 'like', "%{$search}%")
              ->orWhere('Area_Name', 'like', "%{$search}%")
              ->orWhere('Post_Offi', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->Area_Name,
            $this->Code ? "PIN: {$this->Code}" : null
        ]);
        
        return implode(' - ', $parts) ?: $this->Code;
    }

    public function getFullLocationAttribute(): string
    {
        // Using optional() helper prevents errors if a relationship is null
        $districtName = optional($this->district)->Name;
        $stateName = optional($this->state)->Name; // Uses the new hasOneThrough relationship
        $countryName = optional(optional($this->district)->country)->Name; // Chains through district to country

        $location = array_filter([
            $this->Area_Name,
            $this->Post_Offi && $this->Post_Offi !== $this->Area_Name ? $this->Post_Offi : null,
            $districtName,
            $stateName,
            $countryName,
            "PIN: {$this->Code}",
        ]);

        return implode(', ', $location);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->Area_Name && $this->Code) {
            return "{$this->Area_Name} - {$this->Code}";
        }
        
        return $this->Area_Name ?: $this->Code;
    }

    // Methods
    public function getLocationHierarchy(): array
    {
        return [
            'country' => $this->country,
            'state' => $this->state,
            'district' => $this->district,
            'pincode' => $this
        ];
    }

    public static function findByCode($code)
    {
        return self::where('Code', $code)->first();
    }

    public static function searchByLocation($search)
    {
        return self::where('Area_Name', 'like', "%{$search}%")
                   ->orWhere('Post_Offi', 'like', "%{$search}%")
                   ->orWhere('Code', 'like', "%{$search}%")
                   ->with(['country', 'state', 'district'])
                   ->get();
    }
}
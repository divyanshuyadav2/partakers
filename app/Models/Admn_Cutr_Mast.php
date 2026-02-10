<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Model;

class Admn_Cutr_Mast extends Model
{
    protected $table = 'admn_cutr_mast';
    protected $primaryKey = 'Admn_Cutr_Mast_UIN';
    public $timestamps = false;
    public $incrementing = false;  // Since Admn_Cutr_Mast_UIN is not auto-increment
    protected $keyType = 'int';  // Or 'string' if storing as string

    protected $fillable = [
        'Admn_Cutr_Mast_UIN',
        'Name',
        'Code',
        'Phon_Code',
        'Flag_Emoj',
        'MoNo_Digt',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
        'VfBy',
        'VfOn',
        'Del_By',
        'Del_On',
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
        'MoNo_Digt' => 'integer',
    ];

    // --- BOOT METHOD FOR AUTO-GENERATION ---

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate Admn_Cutr_Mast_UIN if not provided
            if (!$model->Admn_Cutr_Mast_UIN) {
                $model->Admn_Cutr_Mast_UIN = static::generateUIN();
            }

            // Set default MoNo_Digt if not provided
            if (!$model->MoNo_Digt) {
                $model->MoNo_Digt = 10;
            }

            // Set creation timestamp if not provided
            if (!$model->CrOn) {
                $model->CrOn = now();
            }
        });
    }

    /**
     * Generate a unique UIN for the country
     * Using: UNIX_TIMESTAMP() - 1592592000
     */
    protected static function generateUIN()
    {
        return intval(time() - 1592592000);
    }

    // --- RELATIONSHIPS ---

    /**
     * A Country has many States.
     */
    public function states(): HasMany
    {
        return $this->hasMany(Admn_Stat_Mast::class, 'Admn_Cutr_Mast_UIN', 'Admn_Cutr_Mast_UIN');
    }

    /**
     * A Country has many Districts through States.
     */
    public function districts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Admn_Dist_Mast::class,  // The final model we want
            Admn_Stat_Mast::class,  // The intermediate model
            'Admn_Cutr_Mast_UIN',  // Foreign key on the State table
            'Admn_Stat_Mast_UIN',  // Foreign key on the District table
            'Admn_Cutr_Mast_UIN',  // Local key on the Country table
            'Admn_Stat_Mast_UIN'  // Local key on the State table
        );
    }

    // --- HELPER METHODS ---

    /**
     * Get formatted country information
     */
    public function getFormattedInfo(): array
    {
        return [
            'uin' => $this->Admn_Cutr_Mast_UIN,
            'name' => $this->Name,
            'code' => $this->Code,
            'phone_code' => $this->Phon_Code,
            'emoji' => $this->Flag_Emoj,
            'mobile_digits' => $this->MoNo_Digt,
        ];
    }

    /**
     * Scope: Get only active countries
     */
    public function scopeActive($query)
    {
        return $query->whereNull('Del_By')->whereNull('Del_On');
    }

    /**
     * Scope: Get countries by code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('Code', $code);
    }
}

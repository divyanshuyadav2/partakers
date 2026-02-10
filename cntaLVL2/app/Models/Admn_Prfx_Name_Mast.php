<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admn_Prfx_Name_Mast extends Model
{
    protected $table = 'admn_prfx_name_mast';
    protected $primaryKey = 'Prfx_Name_UIN';
    public $timestamps = false; // Since you're using custom timestamp fields
    public $incrementing = false; // Since you might be using custom UIN values

    protected $fillable = [
        'Prfx_Name',
        'Prfx_Name_Desp',
        'Stau_UIN',
        'CrBy',
        'MoBy',
        'MoOn',
        'VfBy',
        'VfOn',
    ];

    protected $casts = [
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
        'Stau_UIN' => 'integer',
        'CrBy' => 'integer',
        'MoBy' => 'integer',
        'VfBy' => 'integer',
    ];

    /**
     * Get the status record
     */
    public function status()
    {
        return $this->belongsTo(Admn_Stat_Mast::class, 'Stau_UIN', 'Admn_Stat_Mast_UIN');
    }

    /**
     * Get created by user
     */
    public function createdBy()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'CrBy', 'Admn_User_Mast_UIN');
    }

    /**
     * Get modified by user
     */
    public function modifiedBy()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'MoBy', 'Admn_User_Mast_UIN');
    }

    /**
     * Get verified by user
     */
    public function verifiedBy()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'VfBy', 'Admn_User_Mast_UIN');
    }

    /**
     * Scope to get active prefixes
     */
    public function scopeActive($query)
    {
        return $query->where('Stau_UIN', 100201); // Adjust based on your active status code
    }

    /**
     * Get display name with fallback
     */
    public function getDisplayNameAttribute()
    {
        return $this->Prfx_Name ?: 'N/A';
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admn_Cnta_Link_Mast extends Model
{
    protected $table = 'admn_cnta_link_mast';
    protected $primaryKey = 'Admn_Cnta_Link_Mast_UIN';
    public $timestamps = false;

    protected $fillable = [
        'Tokn', 
          'Admn_Orga_Mast_UIN',
        'Cnta_Tag', 
        'Is_Used', 
        'Expy_Dt',
        'Is_Actv',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
        'VfBy',
        'VfOn'
    ];

    protected $casts = [
        'Is_Used' => 'boolean',
        'Is_Actv' => 'boolean',
        'Expy_Dt' => 'datetime',
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
    ];

    /**
     * Check if link is valid and not expired
     * Handle null expiry dates (permanent links)
     */
    public function isValidLink(): bool
    {
        return $this->Is_Actv && 
               !$this->Is_Used && 
               ($this->Expy_Dt === null || $this->Expy_Dt->isFuture());
    }

    /**
     * Mark link as used
     */
    public function markAsUsed()
    {
        $this->update([
            'Is_Used' => true,
            'Is_Actv' => false, // Also mark as inactive for extra security
            'MoBy' => 1, // System user ID (adjust as needed)
            'MoOn' => now(),
        ]);
    }
}
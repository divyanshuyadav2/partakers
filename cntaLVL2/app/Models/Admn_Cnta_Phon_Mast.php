<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admn_Cnta_Phon_Mast extends Model
{
    protected $table = 'admn_cnta_phon_mast';
    protected $primaryKey = 'Admn_Cnta_Phon_Mast_UIN';
    public $timestamps = false;

    protected $fillable = [
        'Admn_Cnta_Phon_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Phon_Numb',
        'Phon_Type',
        'Cutr_Code',
        'Has_WtAp',
        'Has_Telg',
        'Is_Prmy',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
        'VfBy',
        'VfOn'
    ];

    protected $casts = [
        'Is_Prmy' => 'boolean',
        'Has_WtAp' => 'boolean',
        'Has_Telg' => 'boolean',
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN');
    }

    public function country()
    {
        return $this->belongsTo(Admn_Cutr_Mast::class, 'Cutr_Code', '   Phon_Code');
    }

    /**
     * Get the full phone number with country code.
     */
    public function getFullPhoneNumberAttribute()
    {
        return "{$this->country->Code} {$this->Phon_Numb}";
    }

   
}
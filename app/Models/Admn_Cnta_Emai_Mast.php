<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admn_Cnta_Emai_Mast extends Model
{
    protected $table = 'admn_cnta_emai_mast';
    protected $primaryKey = 'Admn_Cnta_Emai_Mast_UIN';
    public $timestamps = false;

    protected $fillable = [
        'Admn_Cnta_Emai_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Emai_Addr',
        'Emai_Type', // e.g., personal, work
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
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN');
    }
}
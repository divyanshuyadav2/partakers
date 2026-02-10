<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admn_Cnta_Refa_Mast extends Model
{
    protected $table = 'admn_cnta_refa_mast';
    protected $primaryKey = 'Admn_Cnta_Refa_Mast_UIN';
    public $timestamps = false;

    protected $fillable = [
        'Admn_Cnta_Refa_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Refa_Name',
        'Refa_Phon',
        'Refa_Emai',
        'Refa_Rsip',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
        'VfBy',
        'VfOn'
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmnCntaNoteComnt extends Model
{
    protected $table = 'admn_cnta_note_comnt';

    protected $primaryKey = 'Admn_Cnta_Note_Comnt_UIN';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'Admn_Cnta_Note_Comnt_UIN',
        'Category',
        'Comnt_Text',
        'Stau_UIN',
        'CrOn',
        'MoOn',
        'CrBy',
        'Orga_UIN',
    ];
}

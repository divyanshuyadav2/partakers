<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admn_Addr_Type_Mast extends Model
{
        use HasFactory;

        protected $table = 'admn_addr_type_mast';
        protected $primaryKey = 'Admn_Addr_Type_Mast_UIN';
        public $timestamps = false;  // since we are not using created_at / updated_at

        protected $fillable = [
                'Admn_Addr_Type_Mast_UIN',
                'Addr_Type',
                'Name',
                'Cr_By',
                'Cr_On',
                'Mo_By',
                'Mo_On',
                'Del_By',
                'Del_On',
                'Vf_By',
                'Vf_On',
        ];

        protected $dates = [
                'Cr_On',
                'Mo_On',
                'Del_On',
                'Vf_On',
        ];
}

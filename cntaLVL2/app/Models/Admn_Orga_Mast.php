<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admn_Orga_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_orga_mast';
    protected $primaryKey = 'Orga_UIN';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];
}

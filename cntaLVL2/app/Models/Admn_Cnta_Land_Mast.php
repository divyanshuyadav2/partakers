<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admn_Cnta_Land_Mast extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admn_cnta_land_mast';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'Admn_Cnta_Land_Mast_UIN';

    /**
     * Indicates if the model's ID is auto-incrementing.
     * Set to false because we generate UINs manually in the Controller/Livewire.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the model should be timestamped.
     * Set to false because we use custom CrOn/MoOn columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'Admn_Cnta_Land_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Admn_Orga_Mast_UIN',
        'Land_Numb',
        'Land_Type',
        'Cutr_Code',
        'Is_Prmy',
        'Stau',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
    ];

    /**
     * Get the user that owns the landline.
     */
    public function user()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }
}

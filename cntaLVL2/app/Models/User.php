<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admn_user_logi_mast';
    protected $primaryKey = 'User_UIN';
    public $incrementing = true;
    public $timestamps = false;  // Your table doesn't have created_at/updated_at

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'User_UIN',
        'User_Name',
        'User_Logo',
        'Prmy_Emai',
        'Prmy_Emai_Vefi',
        'Altn_Emai',
        'Altn_Emai_Vefi',
        'Cutr_UIN',
        'ISD_UIN',
        'Prmy_MoNo',
        'Prmy_MoNo_Vefi',
        'OTP_Prmy_MoNo',
        'Altn_MoNo',
        'Altn_MoNo_Vefi',
        'OTP_Altn_MoNo',
        'User_pasw',
        'Pasw_Self_Gntd',
        'Pasw_Rest_By',
        'Pasw_Rest_On',
        'CrBy',
        'Stau_UIN',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'User_pasw',
        'OTP_Prmy_MoNo',
        'OTP_Altn_MoNo',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'Prmy_Emai_Vefi' => 'datetime',
            'Altn_Emai_Vefi' => 'datetime',
            'Prmy_MoNo_Vefi' => 'datetime',
            'Altn_MoNo_Vefi' => 'datetime',
            'Pasw_Rest_On' => 'datetime',
        ];
    }

    // Accessor for name (to work with Breeze)
    public function getNameAttribute()
    {
        return $this->User_Name;
    }

    // Accessor for email (to work with Breeze)
    public function getEmailAttribute()
    {
        return $this->Prmy_Emai;
    }

    // Accessor for password (to work with Breeze)
    public function getPasswordAttribute()
    {
        return $this->User_pasw;
    }

    public function organizations()
    {
        return $this->belongsToMany(
            Organization::class,
            'admn_user_orga_rela',
            'User_UIN',
            'Orga_UIN',
            'User_UIN',
            'Orga_UIN'
        );
    }

    public function userOrgaRelations()
    {
        return $this->hasMany(UserOrgaRela::class, 'User_UIN', 'User_UIN');
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Admn_Bank_Name extends Model
{
    protected $table = 'admn_bank_name';
    protected $primaryKey = 'Bank_UIN';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Bank_UIN',
        'Bank_Name',
        'Bank_Type',
        'Stau_UIN',
        'CrOn',
        'CrBy',
    ];

    protected $casts = [
        'Bank_UIN' => 'integer',
        'Stau_UIN' => 'integer',
        'CrOn' => 'integer',
        'CrBy' => 'integer',
    ];

    /**
     * Get all user bank accounts for this bank.
     */
    public function userBankAccounts(): HasMany
    {
        return $this->hasMany(Admn_User_Bank_Mast::class, 'Bank_Name_UIN', 'Bank_UIN');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Admn_User_Bank_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_user_bank_mast';
    protected $primaryKey = 'Admn_User_Bank_Mast_UIN';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Admn_User_Bank_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Admn_Orga_Mast_UIN',
        'Bank_Name_UIN',
        'Bank_Brnc_Name',
        'Acnt_Type',
        'Acnt_Numb',
        'IFSC_Code',
        'Swift_Code',
        'Prmy',
        'Stau',
        'CrBy',
        'MoBy',
        'VrBy',
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VrOn' => 'datetime',
        'Bank_Name_UIN' => 'integer',
        'Bank_Atch' => 'array',
    ];

    /**
     * Get the user that owns the bank account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Get the organization that owns the bank account.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Admn_Orga_Mast::class, 'Admn_Orga_Mast_UIN', 'Orga_UIN');
    }

    /**
     * Get the bank name details (alias for backward compatibility).
     */
    public function bankName(): BelongsTo
    {
        return $this->belongsTo(Admn_Bank_Name::class, 'Bank_Name_UIN', 'Bank_UIN');
    }

    /**
     * Get the bank name details (used by export controller).
     * This is an alias of bankName() for convenience.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Admn_Bank_Name::class, 'Bank_Name_UIN', 'Bank_UIN');
    }

    /**
     * Get the contact/user who owns this bank account (alias).
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    public function attachments()
    {
        return $this->hasMany(AdmnBankAttachment::class, 'Admn_User_Bank_Mast_UIN', 'Admn_User_Bank_Mast_UIN');
    }

    /**
     * Scope to get only primary bank accounts.
     */
    public function scopePrimary($query)
    {
        return $query->where('Prmy', 1);
    }

    /**
     * Scope to get only active bank accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('Stau', 100201);
    }

    /**
     * Mark this bank account as primary.
     */
    public function markAsPrimary()
    {
        // Remove primary flag from other accounts
        Admn_User_Bank_Mast::where('Admn_User_Mast_UIN', $this->Admn_User_Mast_UIN)
            ->where('Admn_User_Bank_Mast_UIN', '!=', $this->Admn_User_Bank_Mast_UIN)
            ->update(['Prmy' => 0]);

        // Mark this as primary
        $this->update(['Prmy' => 1]);
    }

    /**
     * Get formatted account number (hide middle digits for security).
     */
    public function getMaskedAccountNumber(): string
    {
        $accountNumber = $this->Acnt_Numb;
        if (strlen($accountNumber) <= 4) {
            return $accountNumber;
        }

        $first = substr($accountNumber, 0, 4);
        $last = substr($accountNumber, -4);
        $masked = str_repeat('*', strlen($accountNumber) - 8);
        return $first . $masked . $last;
    }
}
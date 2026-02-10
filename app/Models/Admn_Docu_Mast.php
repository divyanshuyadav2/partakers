<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Admn_Docu_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_docu_mast';
    protected $primaryKey = 'Admn_Docu_Mast_UIN';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Admn_Docu_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Admn_Orga_Mast_UIN',
        'Admn_Cutr_Mast_UIN',
        'Admn_Docu_Type_Mast_UIN',
        'Auth_Issd',
        'Regn_Numb',
        'Docu_Name',
        'Vald_From',
        'Vald_Upto',
        'Frnt_Side_Path',
        'Back_Side_Path',
        'Docu_Atch_Path',
        'Stau',
        'CrBy',
        'MoBy',
        'VrBy',
    ];

    protected $casts = [
        'Vald_From' => 'date',
        'Vald_Upto' => 'date',
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VrOn' => 'datetime',
    ];

    /**
     * Get the user that owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Get the organization that owns the document.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Admn_Orga_Mast::class, 'Admn_Orga_Mast_UIN', 'Orga_UIN');
    }

    /**
     * Get the country of the document.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Admn_Cutr_Mast::class, 'Admn_Cutr_Mast_UIN', 'Admn_Cutr_Mast_UIN');
    }

    /**
     * Get the document type.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(Admn_Docu_Type_Mast::class, 'Admn_Docu_Type_Mast_UIN', 'Admn_Docu_Type_Mast_UIN');
    }

    /**
     * Check if document is verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->VrBy);
    }

    /**
     * Check if document is valid (not expired and within validity period).
     */
    public function isValid(): bool
    {
        $today = now();

        // Check if document has not started yet
        if ($this->Vald_From && $this->Vald_From > $today) {
            return false;
        }

        // Check if document is expired
        if ($this->Vald_Upto && $this->Vald_Upto < $today) {
            return false;
        }

        return true;
    }

    /**
     * Get expiry status with details.
     */
    public function getExpiryStatus(): string
    {
        if (!$this->Vald_Upto) {
            return 'No Expiry Date';
        }

        $today = now();
        $daysUntilExpiry = $today->diffInDays($this->Vald_Upto, false);

        if ($daysUntilExpiry < 0) {
            return 'Expired (' . abs($daysUntilExpiry) . ' days ago)';
        } elseif ($daysUntilExpiry == 0) {
            return 'Expires Today';
        } elseif ($daysUntilExpiry <= 30) {
            return 'Expiring Soon (' . $daysUntilExpiry . ' days)';
        } else {
            return 'Valid';
        }
    }

    /**
     * Get expiry status badge color.
     */
    public function getExpiryStatusColor(): string
    {
        if (!$this->Vald_Upto) {
            return 'gray';
        }

        $today = now();
        $daysUntilExpiry = $today->diffInDays($this->Vald_Upto, false);

        if ($daysUntilExpiry < 0) {
            return 'red'; // Expired
        } elseif ($daysUntilExpiry <= 30) {
            return 'yellow'; // Expiring soon
        } else {
            return 'green'; // Valid
        }
    }

    /**
     * Scope to get only active documents.
     */
    public function scopeActive($query)
    {
        return $query->where('Stau', 100201);
    }

    /**
     * Scope to get only verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('VrBy');
    }

    /**
     * Scope to get only pending verification documents.
     */
    public function scopePendingVerification($query)
    {
        return $query->where('Stau', 100201)->whereNull('VrBy');
    }

    /**
     * Scope to get expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->where('Vald_Upto', '<', now());
    }

    /**
     * Scope to get documents expiring soon (within 30 days).
     */
    public function scopeExpiringsoon($query)
    {
        return $query->whereBetween('Vald_Upto', [now(), now()->addDays(30)]);
    }

    /**
     * Get percentage of document completion.
     */
    public function getCompletionPercentage(): int
    {
        $fields = [
            'Auth_Issd',
            'Regn_Numb',
            'Vald_From',
            'Vald_Upto',
            'Frnt_Side_Path',
            'Back_Side_Path',
            'Docu_Atch_Path'
        ];

        $filledFields = 0;
        foreach ($fields as $field) {
            if (!is_null($this->{$field}) && $this->{$field} !== '') {
                $filledFields++;
            }
        }

        return (int) (($filledFields / count($fields)) * 100);
    }
}
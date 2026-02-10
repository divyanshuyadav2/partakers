<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admn_Docu_Type_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_docu_type_mast';
    protected $primaryKey = 'Admn_Docu_Type_Mast_UIN';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'Admn_Docu_Type_Mast_UIN',
        'Docu_Name',
        'Stau',
        'CrBy',
        'MoBy',
        'VrBy',
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VrOn' => 'datetime',
    ];

    /**
     * Get all documents of this type.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Admn_Docu_Mast::class, 'Admn_Docu_Type_Mast_UIN', 'Admn_Docu_Type_Mast_UIN');
    }

    /**
     * Scope to get only active document types.
     */
    public function scopeActive($query)
    {
        return $query->where('Stau', 100201);
    }

    /**
     * Get count of active documents for this type.
     */
    public function getActiveDocumentsCount(): int
    {
        return $this->documents()->where('Stau', 100201)->count();
    }

    /**
     * Get count of verified documents for this type.
     */
    public function getVerifiedDocumentsCount(): int
    {
        return $this->documents()
            ->where('Stau', 100201)
            ->whereNotNull('VrBy')
            ->count();
    }
}
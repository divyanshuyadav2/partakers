<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Admn_Grup_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_grup_mast';
    protected $primaryKey = 'Admn_Grup_Mast_UIN';

    // Since the primary key is not auto-incrementing, we set this to false.
    public $incrementing = false;

    protected $fillable = [
        'Admn_Grup_Mast_UIN',
        'Admn_Orga_Mast_UIN',
        'Parent_Grup_UIN',
        'Name',
        'Is_Actv',
        'CrBy',
        'MoBy',
    ];

    /**
     * Get the parent group.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Admn_Grup_Mast::class, 'Parent_Grup_UIN', 'Admn_Grup_Mast_UIN');
    }

    /**
     * Get the child groups.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Admn_Grup_Mast::class, 'Parent_Grup_UIN', 'Admn_Grup_Mast_UIN');
    }

    /**
     * Get the users (contacts) in this group.
     */
    public function users(): HasMany
    {
        return $this->hasMany(Admn_User_Mast::class, 'Admn_Grup_Mast_UIN', 'Admn_Grup_Mast_UIN');
    }

    // You might need a relationship to the Organization model as well
    // public function organization(): BelongsTo
    // {
    //     return $this->belongsTo(AdmnOrgaMast::class, 'Admn_Orga_Mast_UIN', 'Orga_UIN');
    // }
}

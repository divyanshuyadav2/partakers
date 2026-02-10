<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Admn_Cnta_Addr_Mast extends Model
{
    protected $table = 'admn_cnta_addr_mast';
    protected $primaryKey = 'Admn_Cnta_Addr_Mast_UIN';
    public $incrementing = false;
    
    // MODIFIED: Changed from 'string' to 'integer' to match the 'unsignedBigInteger' in the migration.
    protected $keyType = 'integer'; 
    
    public $timestamps = false; // Using custom CrOn / MoOn fields

    // ADDED: A boot method to programmatically set the timestamp-based UIN on model creation.
    // This complements the database-level default value set in your migration.
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                // Generates a UIN similar to the logic you might have in your GeneratesUin trait.
                // Example: timestamp (14 digits) + random (3 digits)
                $model->{$model->getKeyName()} = (int) (microtime(true) * 10000) . random_int(100, 999);
            }
        });
    }

    protected $fillable = [
        'Admn_Cnta_Addr_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Admn_Addr_Type_Mast_UIN',
        'Addr',
        'Loca',
        'Lndm', // Landmark
        'Is_Prmy',
        'Admn_Cutr_Mast_UIN',
        'Admn_Stat_Mast_UIN',
        'Admn_Dist_Mast_UIN',
        'Admn_PinCode_Mast_UIN',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
        'VfBy',
        'VfOn',
        'Del_By',
        'Del_On',
    ];

    protected $casts = [
        'Is_Prmy' => 'boolean',
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
        'Del_On' => 'datetime',
        'Admn_User_Mast_UIN' => 'integer',
        'Admn_Addr_Type_Mast_UIN' => 'integer',
        'Admn_Cutr_Mast_UIN' => 'integer',
        'Admn_Stat_Mast_UIN' => 'integer',
        'Admn_Dist_Mast_UIN' => 'integer',
        'Admn_PinCode_Mast_UIN' => 'integer',
    ];

    // 🔗 Relationships

    public function contact()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    public function type()
    {
        return $this->belongsTo(Admn_Addr_Type_Mast::class, 'Admn_Addr_Type_Mast_UIN', 'Admn_Addr_Type_Mast_UIN');
    }

    public function country()
    {
        return $this->belongsTo(Admn_Cutr_Mast::class, 'Admn_Cutr_Mast_UIN', 'Admn_Cutr_Mast_UIN');
    }

    public function state()
    {
        return $this->belongsTo(Admn_Stat_Mast::class, 'Admn_Stat_Mast_UIN', 'Admn_Stat_Mast_UIN');
    }

    public function district()
    {
        return $this->belongsTo(Admn_Dist_Mast::class, 'Admn_Dist_Mast_UIN', 'Admn_Dist_Mast_UIN');
    }

    public function pincode()
    {
        return $this->belongsTo(Admn_PinCode_Mast::class, 'Admn_PinCode_Mast_UIN', 'Admn_PinCode_Mast_UIN');
    }

    // 🔑 Accessors

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->Addr,
            $this->Loca,
            $this->Lndm,
            optional($this->district)->Name,
            optional($this->state)->Name,
            optional($this->pincode)->Code,
            optional($this->country)->Name,
        ]);

        return implode(', ', $parts);
    }

    public function getAddressTypeNameAttribute(): string
    {
        return $this->type?->Name ?? 'Unknown';
    }

    public function getLocationHierarchyAttribute(): string
    {
        $parts = array_filter([
            optional($this->district)->Name,
            optional($this->state)->Name,
            optional($this->pincode)->Code,
            optional($this->country)->Name,
        ]);
        return implode(', ', $parts);
    }

    public function getShortAddressAttribute(): string
    {
        $parts = array_filter([
            $this->Addr,
            $this->Loca,
        ]);
        return implode(', ', $parts);
    }

    // 🔎 Scopes

    public function scopePrimary($query)
    {
        return $query->where('Is_Prmy', true);
    }

    public function scopeForContact($query, int $contactId)
    {
        return $query->where('Admn_User_Mast_UIN', $contactId);
    }

    public function scopeWithLocationDetails($query)
    {
        return $query->with(['country', 'state', 'district', 'pincode', 'type']);
    }

    // Helpers

    public function isPrimary(): bool
    {
        return $this->Is_Prmy;
    }

    public function setPrimary(): self
    {
        static::where('Admn_User_Mast_UIN', $this->Admn_User_Mast_UIN)
            ->where('Admn_Cnta_Addr_Mast_UIN', '!=', $this->Admn_Cnta_Addr_Mast_UIN)
            ->update(['Is_Prmy' => false]);

        $this->Is_Prmy = true;
        $this->save();

        return $this;
    }

    public function getDisplayNameAttribute(): string
    {
        $type = $this->address_type_name;
        $short = $this->short_address;
        $primary = $this->Is_Prmy ? ' (Primary)' : '';
        return "{$type}: {$short}{$primary}";
    }
}
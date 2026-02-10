<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Admn_Tag_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_tag_mast';
    protected $primaryKey = 'Admn_Tag_Mast_UIN';

    protected $fillable = [
        'Name',        // tag name
        'Admn_Orga_Mast_UIN',//org uin
        'Colr',        // color
        'CrBy',        // Created By
        'MoBy',        // Modified By
        'VfBy',        // Verified By
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
    ];

    const CREATED_AT = 'CrOn';
    const UPDATED_AT = 'MoOn';


public function contacts()
{
    return $this->belongsToMany(
        Admn_User_Mast::class,
        'admn_cnta_tag_mast', // Pivot table
        'Admn_Tag_Mast_UIN',  // Foreign key on pivot table for current model
        'Admn_User_Mast_UIN'  // Foreign key on pivot table for related model
    );
}
    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('Sort_Ordr')->orderBy('Name');
    }

    public function scopeByName($query, $name)
    {
        return $query->where('Name', 'like', "%{$name}%");
    }


    public function getColorWithDefaultAttribute(): string
    {
        return $this->Colr ?: '#007bff';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->Name . ($this->contact_count > 0 ? " ({$this->contact_count})" : '');
    }

    // Methods
    public function attachContact($contactId, $attributes = [])
    {
        $defaultAttributes = [
            'CrBy' => auth()->user()?->name ?? 'System',
            'CrOn' => now(),
        ];

        return $this->contacts()->attach($contactId, array_merge($defaultAttributes, $attributes));
    }

    public function detachContact($contactId)
    {
        return $this->contacts()->detach($contactId);
    }

    // Backward compatibility
    public function getNameAttribute()
    {
        return $this->attributes['Name'] ?? '';
    }

    public function getColorAttribute()
    {
        return $this->Colr;
    }

      /**
     * Assign ByLink tag to contact
     */
    public static function assignByLinkTag($contactId)
    {
        $byLinkTag = Admn_Tag_Mast::where('Name', 'ByLink')->first();
        
        if ($byLinkTag) {
            return self::firstOrCreate([
                'Admn_User_Mast_UIN' => $contactId,
                'Admn_Tag_Mast_UIN' => $byLinkTag->Admn_Tag_Mast_UIN,
            ], [
                'CrBy' => 'System',
                'CrOn' => now(),
            ]);
        }
        
        return null;
    }
}
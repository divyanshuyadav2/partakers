<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admn_Cnta_Tag_Mast extends Model
{
    protected $table = 'admn_cnta_tag_mast';
    protected $primaryKey = 'Admn_Cnta_Tag_Mast_UIN';
    public $timestamps = false;

    protected $fillable = [
        'Admn_Cnta_Tag_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Admn_Tag_Mast_UIN',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
        'VfBy',
        'VfOn'
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN');
    }

    public function tag()
    {
        return $this->belongsTo(Admn_Tag_Mast::class, 'Admn_Tag_Mast_UIN');
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
                'CrBy' => 103,
                'CrOn' => now(),
            ]);
        }

        return null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AdmnCntaNoteMast extends Model
{
    protected $table = 'admn_cnta_note_mast';
    protected $primaryKey = 'Admn_Cnta_Note_Mast_UIN';
    public $timestamps = false;

    protected $fillable = [
        'Admn_Cnta_Note_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Admn_Orga_Mast_UIN',
        'Vertical_ID',
        'Note_Detl',
        'CrOn',
        'CrBy',
        'MoBy',
        'MoOn',
        'DelBy',
        'DelOn',
        'IsPinned',
        'PinnedOn',
        'PinnedBy',
    ];

    protected $casts = [
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'DelOn' => 'datetime',
        'PinnedOn' => 'datetime',
        'IsPinned' => 'boolean',
        'Vertical_ID' => 'integer',
    ];

    /**
     * Scope: Get notes for a specific contact
     */
    public function scopeForContact(Builder $query, int $contactUIN): Builder
    {
        return $query->where('Admn_User_Mast_UIN', $contactUIN)
                     ->whereNull('DelOn');  // Only non-deleted
    }

    /**
     * Scope: Get notes by vertical
     */
    public function scopeForVertical(Builder $query, int $verticalId): Builder
    {
        return $query->where('Vertical_ID', $verticalId);
    }

    /**
     * Scope: Get latest note first (pinned on top)
     */
    public function scopeLatestNote(Builder $query): Builder
    {
        return $query->orderByRaw('IsPinned DESC')
                     ->orderByDesc('CrOn');
    }

    /**
     * Check if authenticated user can manage (edit/delete) this note
     * Rules:
     * - Only if $userUIN == CrBy (creator)
     * - AND within 48 hours from CrOn
     */
    public function canBeManagedBy(?int $userUIN): bool
    {
        if (!$userUIN) {
            return false;
        }

        // Compare with CrBy field (string to int)
        if ((string)$userUIN !== (string)$this->CrBy) {
            return false;
        }

        // Check 48-hour window
        $createdAt = $this->CrOn instanceof Carbon
            ? $this->CrOn
            : Carbon::parse($this->CrOn);

        $hoursElapsed = $createdAt->diffInHours(now());

        return $hoursElapsed < 48;
    }

    /**
     * Check if note can be deleted (48-hour window)
     */
    public function canBeDeletedBy(?int $userUIN): bool
    {
        return $this->canBeManagedBy($userUIN);
    }

    /**
     * Check if note can be pinned
     */
    public function canBePinned(): bool
    {
        return !$this->IsPinned && $this->DelOn === null;
    }

    /**
     * Check if note can be unpinned
     */
    public function canBeUnpinned(): bool
    {
        return $this->IsPinned && $this->DelOn === null;
    }

    /**
     * Get remaining hours before deletion window closes
     */
    public function getRemainingHoursBeforeDeletion(): int
    {
        $createdAt = $this->CrOn instanceof Carbon
            ? $this->CrOn
            : Carbon::parse($this->CrOn);

        $hoursElapsed = $createdAt->diffInHours(now());
        $remaining = max(0, 48 - $hoursElapsed);

        return $remaining;
    }

    /**
     * Get creator name from database
     */
    public function getCreatorName(): string
    {
        try {
            $user = \DB::table('admn_user_mast')
                ->where('Admn_User_Mast_UIN', $this->CrBy)
                ->first();
            
            if ($user) {
                $name = trim(($user->FaNm ?? '') . ' ' . ($user->LaNm ?? ''));
                return !empty($name) ? $name : 'Unknown User';
            }
            return 'Unknown User';
        } catch (\Exception $e) {
            return 'Unknown User';
        }
    }

    /**
     * Get modifier name (last person to edit)
     */
    public function getModifierName(): ?string
    {
        if (!$this->MoBy) {
            return null;
        }

        try {
            $user = \DB::table('admn_user_mast')
                ->where('Admn_User_Mast_UIN', $this->MoBy)
                ->first();
            
            if ($user) {
                $name = trim(($user->FaNm ?? '') . ' ' . ($user->LaNm ?? ''));
                return !empty($name) ? $name : 'Unknown User';
            }
            return 'Unknown User';
        } catch (\Exception $e) {
            return 'Unknown User';
        }
    }

    /**
     * Get pinner name
     */
    public function getPinnerName(): ?string
    {
        if (!$this->PinnedBy) {
            return null;
        }

        try {
            $user = \DB::table('admn_user_mast')
                ->where('Admn_User_Mast_UIN', $this->PinnedBy)
                ->first();
            
            if ($user) {
                $name = trim(($user->FaNm ?? '') . ' ' . ($user->LaNm ?? ''));
                return !empty($name) ? $name : 'Unknown User';
            }
            return 'Unknown User';
        } catch (\Exception $e) {
            return 'Unknown User';
        }
    }
}
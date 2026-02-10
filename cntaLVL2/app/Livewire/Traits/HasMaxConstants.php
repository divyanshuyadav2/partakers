<?php

namespace App\Livewire\Traits;

/**
 * Trait HasMaxConstants
 *
 * Centralized MAX_* constants and helper methods to check/add multivalue sections.
 */
trait HasMaxConstants
{

    // ============================================
    // CONSTANTS
    // ============================================
    const STATUS_ACTIVE = 100201;
    const STATUS_VERIFIED = 100207;
    const COUNTRY_INDIA_UIN = 101107;
    const PHONE_CODE_INDIA = '91';
    const BASE_UIN = 15000000000;
    const MAX_EMAILS = 5;
    const MAX_PHONES = 5;
    const MAX_LANDLINES = 5;
    const MAX_ADDRESSES = 5;
    const MAX_REFERENCES = 5;
    const MAX_BANKS = 5;
    const MAX_DOCUMENTS = 5;
    const MAX_EDUCATIONS = 10;
    const MAX_SKILLS = 5;
    const MAX_WORK_EXPERIENCES = 5;
    const MAX_NOTES = 50;
    const MAX_BANK_FILE_SIZE = 102400;
    const MAX_DOCUMENT_FILE_SIZE = 204800;
    const MAX_PROFILE_FILE_SIZE = 204800;

    // createByLinkConsts
    const STATUS_UNVERIFIED = 100206;
    const BY_LINK_TAG_ID = 12;
    const DEFAULT_CREATOR = 1;
    const ERROR_TYPE_INVALID = 'error';
    const ERROR_TYPE_USED = 'used';
    const ERROR_TYPE_EXPIRED = 'expired';
    const ERROR_TYPE_INACTIVE = 'inactive';

    /**
     * Map section keys -> property names used in your Livewire component.
     * Adjust these values if your component uses different property names.
     */
    protected static array $SECTION_PROPERTY_MAP = [
        'emails'             => 'emails',
        'phones'             => 'phones',
        'landlines'          => 'landlines',
        'addresses'          => 'addresses',
        'references'         => 'references',
        'banks'              => 'bankAccounts',
        'documents'          => 'documents',
        'educations'         => 'educations',
        'skills'             => 'skills',
        'workExperiences'   => 'workExperiences',
    ];

    /**
     * Static helper to fetch the MAX value for a given section (uses trait constants).
     * Example: self::maxLimit('emails')
     *
     * @param string $section
     * @return int
     */
    public static function maxLimit(string $section): int
    {
        switch ($section) {
            case 'emails':             return (int) static::MAX_EMAILS;
            case 'phones':             return (int) static::MAX_PHONES;
            case 'landlines':          return (int) static::MAX_LANDLINES;
            case 'addresses':          return (int) static::MAX_ADDRESSES;
            case 'references':         return (int) static::MAX_REFERENCES;
            case 'banks':              return (int) static::MAX_BANKS;
            case 'documents':          return (int) static::MAX_DOCUMENTS;
            case 'educations':         return (int) static::MAX_EDUCATIONS;
            case 'skills':             return (int) static::MAX_SKILLS;
            case 'workExperiences':   return (int) static::MAX_WORK_EXPERIENCES;
            default:                   return 0;
        }
    }

    /**
     * Instance helper to fetch the max (wraps static maxLimit).
     * Use $this->getMax('emails') inside your component methods.
     */
    public function getMax(string $section): int
    {
        return static::maxLimit($section);
    }

    /**
     * Return property name for a given section key, or null if unknown.
     */
    public function getPropertyFor(string $section): ?string
    {
        return static::$SECTION_PROPERTY_MAP[$section] ?? null;
    }

    /**
     * Universal checker: returns true if the given section can accept another item.
     * Usage in blade: @if($this->canAdd('banks')) ... @endif
     *
     * This does defensive checks: property existence and that it's an array.
     *
     * @param string $section
     * @return bool
     */
    public function canAdd(string $section): bool
    {
        $prop = $this->getPropertyFor($section);
        if (! $prop) {
            return false;
        }

        if (! property_exists($this, $prop)) {
            return false;
        }

        $value = $this->{$prop};

        if (! is_array($value)) {
            return false;
        }

        return count($value) < $this->getMax($section);
    }
}

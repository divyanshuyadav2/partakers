<?php

namespace App\Livewire\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait FormatsDates
{
    /**
     * Normalize date input for database storage (Y-m-d)
     */
    protected function formatDateForDatabase($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            Log::warning(
                'Date parsing failed',
                ['value' => $value, 'exception' => $e->getMessage()]
            );
            return null;
        }
    }
}

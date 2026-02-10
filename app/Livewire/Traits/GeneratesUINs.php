<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait GeneratesUINs
{
    /**
     * Generate a unique UIN using hybrid timestamp strategy
     */
    private function generateUniqueUIN(string $table, string $pkColumn): int
    {
        for ($i = 0; $i < 100; $i++) {
            $newUIN = $this->generateHybridTimestampUIN();

            if (! DB::table($table)->where($pkColumn, $newUIN)->exists()) {
                Log::info("Generated UIN: {$newUIN} for table: {$table}");

                return $newUIN;
            }

            usleep(mt_rand(1000, 5000));
        }

        throw new \Exception(
            "Failed to generate unique UIN after 100 attempts for table: {$table}"
        );
    }

    /**
     * Generate sequential batch UINs
     */
    private function generateBatchUINs(string $table, string $pkColumn, int $count): array
    {
        $lastUIN = DB::table($table)->max($pkColumn);

        $startUIN = (! $lastUIN || $lastUIN < self::BASE_UIN)
            ? self::BASE_UIN
            : $lastUIN + 1;

        $uins = [];

        for ($i = 0; $i < $count; $i++) {
            $uins[] = $startUIN + $i;
        }

        if (! empty($uins) && max($uins) > 99999999999) {
            throw new \Exception("UIN limit exceeded for table: {$table}");
        }

        return $uins;
    }

    /**
     * Hybrid timestamp-based UIN generator
     */
    private function generateHybridTimestampUIN(): int
    {
        $microtime = microtime(true);
        $timestamp = (int) ($microtime * 1_000_000);

        $timestampPart = $timestamp % 1_000_000;
        $randomPart = mt_rand(100, 999);

        $uin = self::BASE_UIN + ($timestampPart * 1000) + $randomPart;

        // fallback safety
        if ($uin > 99999999999) {
            $simpleTimestamp = time() % 100000;
            $simpleRandom = mt_rand(1000, 9999);

            $uin = self::BASE_UIN + ($simpleTimestamp * 10000) + $simpleRandom;
        }

        return $uin;
    }
}

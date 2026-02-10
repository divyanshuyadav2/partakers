<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasCustomUin
{
    protected static function bootHasCustomUin()
    {
        static::creating(function ($model) {
            // Automatically generate UIN if not set
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = self::generateUniqueUin($model);
            }
        });
    }

    protected static function generateUniqueUin($model)
    {
        $table = $model->getTable();
        $pk = $model->getKeyName();
        $baseUin = 15000000000;

        // Try 100 times to find a unique ID
        for ($i = 0; $i < 100; $i++) {
            $microtime = microtime(true);
            $timestamp = (int) ($microtime * 1000000); // Microsecond precision
            $timestampPart = $timestamp % 1000000;
            $randomPart = mt_rand(100, 999);

            $newUin = $baseUin + ($timestampPart * 1000) + $randomPart;

            // Simple overflow check
            if ($newUin > 99999999999) {
                $newUin = $baseUin + (time() % 100000 * 10000) + mt_rand(1000, 9999);
            }

            if (!DB::table($table)->where($pk, $newUin)->exists()) {
                return $newUin;
            }
            
            usleep(mt_rand(1000, 5000)); // Sleep 1-5ms to prevent collision
        }

        throw new \Exception("Failed to generate unique UIN for table: {$table}");
    }
}
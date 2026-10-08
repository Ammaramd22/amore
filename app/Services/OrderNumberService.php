<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderNumberService
{
    /**
     * Short unique numbers, e.g. INV-0014 / KOT-0003 (no long date embedded).
     * Serializes allocation with a row lock and bumps until free.
     */
    public static function generate(
        string $prefix,
        string $table,
        bool $resetDaily = true,
        ?string $type = null,
        string $numberColumn = 'order_number'
    ): string {
        return DB::transaction(function () use ($prefix, $table, $resetDaily, $type, $numberColumn) {
            // Serialize concurrent generators on this table
            $lock = DB::table($table)->orderByDesc('id');
            if ($type) {
                $lock->where('type', $type);
            }
            $lock->lockForUpdate()->value('id');

            $sequence = self::nextSequence($prefix, $table, $resetDaily, $type, $numberColumn);

            for ($i = 0; $i < 50; $i++) {
                $candidate = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

                $exists = DB::table($table)->where($numberColumn, $candidate);
                if ($type) {
                    // order_number is globally unique; type filter only for kot_number scopes
                    if ($numberColumn !== 'order_number') {
                        $exists->where('type', $type);
                    }
                }

                if (! $exists->exists()) {
                    return $candidate;
                }

                $sequence++;
            }

            // Extremely unlikely fallback
            return $prefix.str_pad((string) (time() % 10000), 4, '0', STR_PAD_LEFT);
        });
    }

    /** Preview the next number without allocating (for forms). */
    public static function peekNext(
        string $prefix,
        string $table,
        bool $resetDaily = false,
        ?string $type = null,
        string $numberColumn = 'order_number'
    ): string {
        $sequence = self::nextSequence($prefix, $table, $resetDaily, $type, $numberColumn);

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    protected static function nextSequence(
        string $prefix,
        string $table,
        bool $resetDaily,
        ?string $type,
        string $numberColumn
    ): int {
        // Always scan enough history so short numbers stay unique across days
        $query = DB::table($table);
        if ($type && $numberColumn !== 'order_number') {
            $query->where('type', $type);
        }

        // Daily reset only affects the preferred starting point when today already has rows
        if ($resetDaily) {
            $todayMax = self::maxTrailing($query->clone()->whereDate('created_at', today())->pluck($numberColumn), $prefix);
            if ($todayMax > 0) {
                return $todayMax + 1;
            }
        }

        return self::maxTrailing($query->pluck($numberColumn), $prefix) + 1;
    }

    /**
     * @param  iterable<int, mixed>  $numbers
     */
    protected static function maxTrailing(iterable $numbers, string $prefix): int
    {
        $max = 0;
        foreach ($numbers as $num) {
            $num = (string) $num;
            if ($num === '' || ! str_starts_with($num, $prefix)) {
                continue;
            }
            if (preg_match('/(\d+)$/', $num, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max;
    }
}

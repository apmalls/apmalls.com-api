<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

class NumberHelper
{
    /**
     * Generate Running Number
     *
     * Example:
     * PO-2026-000001
     * SO-2026-000001
     * SUP-2026-000001
     * CUS-2026-000001
     */
    public static function generate(
        string $model,
        string $column,
        string $prefix,
        int $length = 6
    ): string {

        /** @var Model $model */

        $year = now()->format('Y');

        $query = $model::query();

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        $last = $query->where($column, 'like', "{$prefix}-{$year}-%")
            ->latest('id')
            ->first();

        $next = $last
            ? ((int) substr($last->{$column}, -$length)) + 1
            : 1;

        return sprintf(
            '%s-%s-%0' . $length . 'd',
            strtoupper($prefix),
            now()->format('Y'),
            $next
        );
    }
}

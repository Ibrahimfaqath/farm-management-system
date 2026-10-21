<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates the next readable code in a prefix sequence.
 *
 * Master data used to ask the user to invent a code such as `FRM-001` or
 * `KAND-A`. That produced duplicates, gaps and codes that did not sort. Codes
 * are now assigned by the application, and the next number is derived from the
 * numeric suffix of the existing codes with the same prefix.
 *
 * Legacy codes that do not end in digits (for example `KAND-A` or `BRD-HRF`)
 * are deliberately ignored. They stay valid, but they do not move the numeric
 * sequence; the first generated barn is `KAND-001`, not `KAND-B`.
 */
class CodeGenerator
{
    /**
     * Return the next code for a table and column.
     *
     * The row lock is what keeps two simultaneous saves from choosing the same
     * number. Callers should wrap the generated code and the insert in one
     * database transaction so the lock is held until the row exists.
     */
    public function next(string $table, string $column, string $prefix, int $digits = 3): string
    {
        return DB::transaction(function () use ($table, $column, $prefix, $digits): string {
            $codes = DB::table($table)
                ->where($column, 'like', $prefix.'%')
                ->lockForUpdate()
                ->pluck($column);

            $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';
            $highest = 0;

            foreach ($codes as $code) {
                if (preg_match($pattern, (string) $code, $matches) === 1) {
                    $highest = max($highest, (int) $matches[1]);
                }
            }

            return $prefix.str_pad((string) ($highest + 1), $digits, '0', STR_PAD_LEFT);
        });
    }
}

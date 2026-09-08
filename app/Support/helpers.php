<?php

if (! function_exists('formatNaira')) {
    /**
     * Format a numeric amount as Nigerian Naira, e.g. formatNaira(150000) -> "₦150,000".
     *
     * @param  int|float|string|null  $amount
     */
    function formatNaira(int|float|string|null $amount, int $decimals = 0): string
    {
        return '₦' . number_format((float) ($amount ?? 0), $decimals);
    }
}

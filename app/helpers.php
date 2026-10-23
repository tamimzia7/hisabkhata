<?php

if (! function_exists('localize_digits')) {
    /**
     * Convert Latin digits to Bengali digits when the active locale is Bengali.
     */
    function localize_digits(int|float|string $value): string
    {
        $value = (string) $value;

        if (app()->getLocale() !== 'bn') {
            return $value;
        }

        return strtr($value, [
            '0' => '০',
            '1' => '১',
            '2' => '২',
            '3' => '৩',
            '4' => '৪',
            '5' => '৫',
            '6' => '৬',
            '7' => '৭',
            '8' => '৮',
            '9' => '৯',
        ]);
    }
}

if (! function_exists('localize_number')) {
    /**
     * Format a number with thousands separators and localized digits.
     */
    function localize_number(int|float $number): string
    {
        return localize_digits(number_format($number));
    }
}

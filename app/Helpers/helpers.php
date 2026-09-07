<?php

use Carbon\Carbon;

if (! function_exists('format_rupiah')) {
    /**
     * Format an integer amount into Indonesian Rupiah (e.g. Rp 1.500.000).
     */
    function format_rupiah(?int $amount, bool $withPrefix = true): string
    {
        $value = $amount ?? 0;
        $formatted = number_format($value, 0, ',', '.');

        return $withPrefix ? 'Rp '.$formatted : $formatted;
    }
}

if (! function_exists('parse_rupiah')) {
    /**
     * Parse formatted currency string or number to integer Rupiah.
     */
    function parse_rupiah(string|int|float|null $value): int
    {
        if ($value === null) {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        $cleaned = preg_replace('/[^\d-]/', '', $value);

        return (int) $cleaned;
    }
}

if (! function_exists('format_date')) {
    /**
     * Format a date into standard d/m/Y.
     */
    function format_date(mixed $date, string $format = 'd/m/Y'): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)->format($format);
    }
}

if (! function_exists('format_date_indonesian')) {
    /**
     * Format a date into Indonesian readable format (e.g. 10 September 2026).
     */
    function format_date_indonesian(mixed $date, bool $withTime = false): string
    {
        if (! $date) {
            return '-';
        }

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $carbon = Carbon::parse($date);
        $day = $carbon->format('d');
        $month = $months[(int) $carbon->format('n')];
        $year = $carbon->format('Y');

        $result = "{$day} {$month} {$year}";

        if ($withTime) {
            $result .= ' '.$carbon->format('H:i');
        }

        return $result;
    }
}

if (! function_exists('format_interest_rate')) {
    /**
     * Format interest rate stored in basis points (e.g. 200 bps -> 2%) or percentage.
     */
    function format_interest_rate(int|float $rate, string $frequency = 'bulan'): string
    {
        // If rate > 50, it is likely basis points (e.g., 200 = 2%)
        $percentage = $rate > 50 ? ($rate / 100) : $rate;
        $formatted = (floor($percentage) == $percentage) ? number_format($percentage, 0) : number_format($percentage, 2);

        return "{$formatted}% per {$frequency}";
    }
}

<?php

/**
 * Formatting helpers shared by every view.
 * Loaded globally via Config\Autoload::$helpers = ['format'].
 */

if (! function_exists('inr')) {
    /**
     * Format a money value as Indian rupees with 2 decimals.
     */
    function inr(float|int|string|null $amount, bool $withSymbol = true): string
    {
        return ($withSymbol ? '₹' : '') . number_format((float) $amount, 2);
    }
}

if (! function_exists('fpct')) {
    /**
     * Format a percentage without trailing zeros: 12.50 -> "12.5%", 10.00 -> "10%".
     */
    function fpct(float|int|string|null $value): string
    {
        $formatted = rtrim(rtrim(number_format((float) $value, 2), '0'), '.');

        return $formatted . '%';
    }
}

if (! function_exists('fdate')) {
    /**
     * Format a stored date/datetime for display.
     */
    function fdate(?string $date, string $format = 'd M Y'): string
    {
        if ($date === null || $date === '' || str_starts_with($date, '0000-00-00')) {
            return '—';
        }

        return date($format, strtotime($date));
    }
}

if (! function_exists('status_badge')) {
    /**
     * Render a bootstrap badge for any status used in the app.
     */
    function status_badge(?string $status): string
    {
        $map = [
            'active'    => 'success',
            'inactive'  => 'secondary',
            'available' => 'info',
            'withdrawn' => 'primary',
            'completed' => 'dark',
            'cancelled' => 'danger',
            'paid'      => 'success',
            'pending'   => 'warning',
            'approved'  => 'info',
            'rejected'  => 'danger',
        ];

        $lower = strtolower((string) $status);
        $class = $map[$lower] ?? 'secondary';

        return '<span class="badge text-bg-' . $class . '">' . esc(ucfirst($lower)) . '</span>';
    }
}

if (! function_exists('schedule_label')) {
    /**
     * Human readable withdrawal frequency, e.g. "Monthly".
     */
    function schedule_label(?string $type): string
    {
        return ucfirst(strtolower((string) $type));
    }
}

if (! function_exists('old_value')) {
    /**
     * Repopulate a form field after a failed validation round-trip.
     */
    function old_value(array $row, string $field, mixed $default = ''): string
    {
        return (string) esc(old($field, $row[$field] ?? $default));
    }
}

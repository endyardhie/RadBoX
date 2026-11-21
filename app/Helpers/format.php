<?php

function formatRupiah(float|int|string|null $value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function formatBytes(float|int|null $bytes): string
{
    $bytes = (float) ($bytes ?? 0);
    foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
        if ($bytes < 1024 || $unit === 'TB') {
            return number_format($bytes, $unit === 'B' ? 0 : 2) . ' ' . $unit;
        }
        $bytes /= 1024;
    }
    return '0 B';
}

function formatDateTime(?string $value): string
{
    return $value ? date('d M Y H:i', strtotime($value)) : '-';
}

function minutesToHuman(?int $minutes): string
{
    $minutes = (int) $minutes;
    if ($minutes <= 0) {
        return 'Selamanya';
    }
    $days = intdiv($minutes, 1440);
    $hours = intdiv($minutes % 1440, 60);
    $mins = $minutes % 60;
    $parts = [];
    if ($days) {
        $parts[] = $days . ' hari';
    }
    if ($hours) {
        $parts[] = $hours . ' jam';
    }
    if ($mins || !$parts) {
        $parts[] = $mins . ' menit';
    }
    return implode(' ', $parts);
}

function secondsToHuman(?int $seconds): string
{
    $seconds = (int) $seconds;
    if ($seconds <= 0) {
        return '0 menit';
    }
    return minutesToHuman((int) max(1, ceil($seconds / 60)));
}

function durationInputValue(?int $minutes): string
{
    $minutes = (int) $minutes;
    if ($minutes <= 0) {
        return '';
    }
    if ($minutes % 1440 === 0) {
        return (string) intdiv($minutes, 1440) . 'h';
    }
    if ($minutes % 60 === 0) {
        return (string) intdiv($minutes, 60) . 'j';
    }
    return (string) $minutes . 'm';
}

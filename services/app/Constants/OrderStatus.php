<?php

namespace App\Constants;

class OrderStatus
{
    public const PENDING = 'pending';
    public const COMPLETE = 'complete';
    public const CANCEL = 'cancel';
    public const LOOKING = 'looking';
    public const RUNNING = 'running';

    // Backward compatibility aliases
    public const COMPLETED = self::COMPLETE;
    public const PROCESSING = self::RUNNING;
    public const AUTOPROCESSING = self::LOOKING;

    public const ORDERLIST = [
        self::PENDING,
        self::COMPLETE,
        self::CANCEL,
        self::LOOKING,
        self::RUNNING,
    ];

    public static function options(): array
    {
        return [
            self::PENDING => 'Pending',
            self::COMPLETE => 'Complete',
            self::CANCEL => 'Cancel',
            self::LOOKING => 'Looking',
            self::RUNNING => 'Running',
        ];
    }

    public static function color($status): string
    {
        return match (self::normalize($status)) {
            self::COMPLETE => 'text-success',
            self::RUNNING => 'text-primary',
            self::LOOKING => 'text-info',
            self::PENDING => 'text-warning',
            self::CANCEL => 'text-danger',
            default => 'text-secondary',
        };
    }

    public static function adminColor($status): string
    {
        return match (self::normalize($status)) {
            self::COMPLETE => 'success',
            self::RUNNING => 'info',
            self::LOOKING => 'primary',
            self::PENDING => 'warning',
            self::CANCEL => 'danger',
            default => 'gray',
        };
    }

    public static function text($status): string
    {
        return self::options()[self::normalize($status)] ?? ucfirst((string) $status);
    }

    public static function normalize($status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'complete', 'completed', 'success', 'delivered', 'finish', 'done' => self::COMPLETE,
            'running', 'processing', 'process', 'in_progress' => self::RUNNING,
            'looking', 'auto-processing', 'autoprocessing', 'queued', 'queue' => self::LOOKING,
            'pending' => self::PENDING,
            'cancel', 'cancelled', 'canceled', 'failed', 'fail', 'refunded' => self::CANCEL,
            default => $normalized,
        };
    }
}
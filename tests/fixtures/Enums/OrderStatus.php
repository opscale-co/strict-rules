<?php

namespace Opscale\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function getDisplayName(): string
    {
        return match($this) {
            self::PENDING => 'Pending Payment',
            self::PROCESSING => 'Processing Order',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::PROCESSING, self::CANCELLED]),
            self::PROCESSING => in_array($newStatus, [self::SHIPPED, self::CANCELLED]),
            self::SHIPPED => in_array($newStatus, [self::DELIVERED, self::CANCELLED]),
            self::DELIVERED => in_array($newStatus, [self::REFUNDED]),
            self::CANCELLED => false,
            self::REFUNDED => false,
        };
    }

    public function isActive(): bool
    {
        return !in_array($this, [self::CANCELLED, self::REFUNDED]);
    }

    public static function getActiveStatuses(): array
    {
        return array_filter(self::cases(), fn($status) => $status->isActive());
    }
}
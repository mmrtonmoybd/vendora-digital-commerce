<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending payment',
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'danger',
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Cancelled => 'gray',
            self::Refunded => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PendingPayment => 'heroicon-o-banknotes',
            self::Pending => 'heroicon-o-clock',
            self::Processing => 'heroicon-o-cog-6-tooth',
            self::Completed => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Refunded => 'heroicon-o-arrow-uturn-left',
        };
    }

    /**
     * The only transitions the system allows.
     *
     * Every service that moves an order checks this map first, so an order can never reach an
     * impossible state through a stray admin action or a replayed gateway callback.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::Pending, self::Processing, self::Cancelled],
            self::Pending => [self::Processing, self::Completed, self::Cancelled, self::Refunded],
            self::Processing => [self::Completed, self::Cancelled, self::Refunded],
            self::Completed => [self::Refunded],
            self::Cancelled => [],
            self::Refunded => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::PendingPayment, self::Pending, self::Processing], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Refunded], true);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /**
     * The queues staff actually work from, in the order they work them.
     *
     * "Awaiting verification" is first after All because it is the one queue where a
     * customer is waiting on a human before anything else can happen.
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')->badge(Order::count()),

            'awaiting_verification' => Tab::make('Awaiting verification')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_status', OrderPaymentStatus::Submitted->value))
                ->badge(Order::where('payment_status', OrderPaymentStatus::Submitted->value)->count())
                ->badgeColor('warning'),

            'pending_payment' => Tab::make('Pending payment')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::PendingPayment->value))
                ->badge(Order::where('status', OrderStatus::PendingPayment->value)->count()),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Pending->value))
                ->badge(Order::where('status', OrderStatus::Pending->value)->count())
                ->badgeColor('warning'),

            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Processing->value))
                ->badge(Order::where('status', OrderStatus::Processing->value)->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Completed->value)),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Cancelled->value)),

            'refunded' => Tab::make('Refunded')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Refunded->value)),
        ];
    }
}

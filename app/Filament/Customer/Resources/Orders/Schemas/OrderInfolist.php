<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Orders\Schemas;

use App\Models\OrderItem;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * What the customer sees about their own order.
 *
 * Deliberately narrower than the staff view: no internal access reference, no staff notes,
 * no attribution data.
 */
final class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->columns(4)
                ->schema([
                    TextEntry::make('order_number')->label('Order number')->copyable()->weight('bold'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('payment_status')->label('Payment')->badge(),
                    TextEntry::make('total')->formatStateUsing(fn (int $state): string => Money::format($state))->weight('bold'),
                    TextEntry::make('delivery_email')->label('Access email')->copyable(),
                    TextEntry::make('paymentMethod.name')->label('Paid with')->placeholder('—'),
                    TextEntry::make('created_at')->label('Placed')->dateTime(),
                    TextEntry::make('completed_at')->label('Completed')->dateTime()->placeholder('Not yet'),
                ]),

            Section::make('Your access')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('product_snapshot.name')->label('Product')->weight('semibold'),
                            TextEntry::make('delivery_email')->label('Granted to')->copyable(),
                            TextEntry::make('access_status')->label('Status')->badge(),
                            TextEntry::make('instructions')
                                ->label('What to do next')
                                ->columnSpanFull()
                                ->placeholder('We will email you as soon as access is granted.')
                                ->state(fn (OrderItem $record): ?string => $record->access_status->isLive()
                                    ? strip_tags((string) ($record->inviteInstructions() ?: 'Check your inbox for the invitation and accept it.'))
                                    : null),
                            TextEntry::make('admin_delivery_note')->label('Note from our team')->html()->placeholder('—')->columnSpanFull(),
                            TextEntry::make('warranty_until')->label('Replacement window ends')->date()->placeholder('—'),
                            TextEntry::make('granted_at')->label('Access granted')->dateTime()->placeholder('—'),
                        ]),
                ]),

            Section::make('Totals')
                ->columns(4)
                ->schema([
                    TextEntry::make('subtotal')->formatStateUsing(fn (int $state): string => Money::format($state)),
                    TextEntry::make('discount')->formatStateUsing(fn (int $state): string => Money::format($state)),
                    TextEntry::make('fee')->label('Processing fee')->formatStateUsing(fn (int $state): string => Money::format($state)),
                    TextEntry::make('refunded_amount')->label('Refunded')->formatStateUsing(fn (int $state): string => Money::format($state)),
                ]),

            Section::make('Progress')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('histories')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('created_at')->label('When')->dateTime(),
                            TextEntry::make('to_status')->label('Status')->badge(),
                            TextEntry::make('note')->label('Note')->placeholder('—'),
                        ]),
                ]),
        ]);
    }
}

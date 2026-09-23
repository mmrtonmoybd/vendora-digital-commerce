<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\URL;

/**
 * What an operator needs to fulfil or investigate one order, on one screen.
 *
 * The access email is given the most prominent treatment on the page and is copyable,
 * because it is the single value the whole fulfilment step turns on.
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
                    TextEntry::make('delivery_email')
                        ->label('Access email')
                        ->copyable()
                        ->weight('bold')
                        ->size(TextSize::Large)
                        ->helperText('Access is granted to this address.'),
                    TextEntry::make('user.name')->label('Customer')->placeholder('Guest checkout'),
                    TextEntry::make('customer_phone')->label('Phone')->copyable()->placeholder('—'),
                    TextEntry::make('paymentMethod.name')->label('Paid with')->badge()->color('gray'),
                    TextEntry::make('created_at')->label('Placed')->dateTime(),
                    TextEntry::make('paid_at')->label('Paid')->dateTime()->placeholder('Not yet'),
                    TextEntry::make('completed_at')->label('Completed')->dateTime()->placeholder('Not yet'),
                    TextEntry::make('assignedAdmin.name')->label('Assigned to')->placeholder('Nobody'),
                ]),

            Section::make('Attribution')
                ->columns(4)
                ->collapsed()
                ->schema([
                    TextEntry::make('orderSource.name')->label('Source')->badge()->color('info')->placeholder('Direct'),
                    TextEntry::make('utm_campaign')->label('Campaign')->placeholder('—'),
                    TextEntry::make('utm_medium')->label('Medium')->placeholder('—'),
                    TextEntry::make('utm_source')->label('utm_source')->placeholder('—'),
                    TextEntry::make('referrer')->label('Referrer')->placeholder('—')->columnSpan(2),
                    TextEntry::make('landing_url')->label('Landing page')->placeholder('—')->columnSpan(2),
                ]),

            Section::make('Items and access')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('product_snapshot.name')->label('Product')->weight('semibold')->columnSpan(2),
                            TextEntry::make('quantity')->label('Qty'),
                            TextEntry::make('line_total')->formatStateUsing(fn (int $state): string => Money::format($state)),
                            TextEntry::make('delivery_email')->label('Access email')->copyable(),
                            TextEntry::make('access_status')->label('Access')->badge(),
                            TextEntry::make('granted_at')->label('Granted')->dateTime()->placeholder('—'),
                            TextEntry::make('warranty_until')->label('Warranty until')->date()->placeholder('—'),
                            TextEntry::make('field_values')
                                ->label('Details collected at checkout')
                                ->columnSpanFull()
                                ->placeholder('None')
                                ->state(fn (OrderItem $record): ?string => collect($record->field_values ?? [])
                                    ->filter()
                                    ->map(fn ($value, $key): string => str($key)->headline().': '.$value)
                                    ->implode(' · ') ?: null)
                                ->copyable(),
                            TextEntry::make('access_reference')
                                ->label('Internal reference')
                                ->placeholder('—')
                                ->columnSpan(2)
                                // Names an internal asset; never shown to the customer and
                                // gated behind its own permission for staff too.
                                ->visible(fn (): bool => auth('admin')->user()?->can('view_access_reference') ?? false),
                            TextEntry::make('admin_delivery_note')->label('Note to customer')->html()->placeholder('—')->columnSpan(2),
                        ]),
                ]),

            Section::make('Manual payment proof')
                ->columns(3)
                ->visible(fn (Order $record): bool => filled($record->manual_transaction_id) || filled($record->manual_sender_number))
                ->schema([
                    TextEntry::make('manual_sender_number')->label('Sender number')->copyable(),
                    TextEntry::make('manual_transaction_id')->label('Transaction ID')->copyable(),
                    TextEntry::make('manual_screenshot')
                        ->label('Screenshot')
                        ->placeholder('Not provided')
                        ->formatStateUsing(fn (?string $state): string => $state ? 'Open screenshot' : '—')
                        // Private disk: the link is signed and expires.
                        ->url(fn (?string $state): ?string => $state
                            ? URL::temporarySignedRoute('files.show', now()->addMinutes(15), ['path' => $state])
                            : null, shouldOpenInNewTab: true),
                ]),

            Section::make('Totals')
                ->columns(4)
                ->schema([
                    TextEntry::make('subtotal')->formatStateUsing(fn (int $state): string => Money::format($state)),
                    TextEntry::make('discount')->formatStateUsing(fn (int $state): string => Money::format($state)),
                    TextEntry::make('fee')->formatStateUsing(fn (int $state): string => Money::format($state)),
                    TextEntry::make('refunded_amount')->label('Refunded')->formatStateUsing(fn (int $state): string => Money::format($state)),
                    TextEntry::make('coupon.code')->label('Coupon')->badge()->placeholder('None'),
                    TextEntry::make('customer_note')->label('Customer note')->placeholder('—')->columnSpan(3),
                ]),

            Section::make('History')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('histories')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('created_at')->label('When')->dateTime(),
                            TextEntry::make('to_status')->label('Status')->badge(),
                            TextEntry::make('actor')->label('By')->state(fn ($record): string => $record->actorName()),
                            TextEntry::make('note')->label('Note')->placeholder('—'),
                        ]),
                ]),

            Section::make('Gateway payments')
                ->collapsed()
                ->visible(fn (Order $record): bool => $record->payments()->exists())
                ->schema([
                    RepeatableEntry::make('payments')
                        ->hiddenLabel()
                        ->columns(5)
                        ->schema([
                            TextEntry::make('merchant_order_id')->label('Reference')->copyable(),
                            TextEntry::make('sp_order_id')->label('Gateway ID')->copyable()->placeholder('—'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('method')->label('Paid via')->placeholder('—'),
                            TextEntry::make('sp_message')->label('Gateway message')->placeholder('—'),
                        ]),
                ]),
        ]);
    }
}

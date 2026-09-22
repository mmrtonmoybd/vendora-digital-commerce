<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Customer\Concerns\BelongsToCustomer;
use App\Filament\Customer\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Customer\Resources\Tickets\Pages\ListTickets;
use App\Filament\Customer\Resources\Tickets\Pages\ViewTicket;
use App\Models\Ticket;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

final class TicketResource extends Resource
{
    use BelongsToCustomer;

    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|null|UnitEnum $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Support tickets';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('How can we help?')->schema([
                Select::make('order_id')
                    ->label('Is this about an order?')
                    ->placeholder('Not about a specific order')
                    // Only the customer's own orders. Anything else would let a ticket
                    // become a way to read somebody else's order.
                    ->options(fn (): array => \App\Models\Order::query()
                        ->where('user_id', auth()->id())
                        ->latest()
                        ->limit(50)
                        ->pluck('order_number', 'id')
                        ->all())
                    ->searchable(),
                TextInput::make('subject')->required()->maxLength(190),
                Select::make('priority')
                    ->options(TicketPriority::class)
                    ->default(TicketPriority::Normal)
                    ->required()
                    ->native(false),
                Textarea::make('message')
                    ->label('Tell us what happened')
                    ->required()
                    ->rows(6)
                    ->helperText('Include your order number and anything you have already tried.'),
                FileUpload::make('attachments')
                    ->label('Screenshots (optional)')
                    ->multiple()
                    ->maxFiles(3)
                    ->maxSize(5120)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                    ->disk('local')
                    ->directory('tickets'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->columns([
                TextColumn::make('ticket_number')->label('Ticket')->copyable()->weight('semibold'),
                TextColumn::make('subject')->searchable()->wrap()->limit(60),
                TextColumn::make('order.order_number')->label('Order')->placeholder('—')->toggleable(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_reply_at')->label('Last reply')->since()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(TicketStatus::class)->multiple()])
            ->recordActions([ActionGroup::make([ViewAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }
}

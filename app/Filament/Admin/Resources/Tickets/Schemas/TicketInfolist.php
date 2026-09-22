<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tickets\Schemas;

use App\Models\TicketReply;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')
                ->columns(4)
                ->schema([
                    TextEntry::make('ticket_number')->label('Number')->copyable()->weight('bold'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('priority')->badge(),
                    TextEntry::make('assignedAdmin.name')->label('Assigned to')->placeholder('Nobody'),
                    TextEntry::make('subject')->columnSpanFull()->weight('semibold'),
                    TextEntry::make('user.name')->label('Customer')
                        ->helperText(fn ($record): ?string => $record->user?->email),
                    TextEntry::make('order.order_number')->label('About order')->placeholder('Not about an order'),
                    TextEntry::make('created_at')->label('Opened')->dateTime(),
                    TextEntry::make('first_responded_at')->label('First reply')->dateTime()->placeholder('Not yet'),
                ]),

            Section::make('Conversation')
                ->schema([
                    RepeatableEntry::make('replies')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('author')
                                ->label('From')
                                ->state(fn (TicketReply $record): string => $record->authorName())
                                ->badge()
                                ->color(fn (TicketReply $record): string => $record->isFromStaff() ? 'primary' : 'gray'),
                            TextEntry::make('created_at')->label('When')->dateTime(),
                            TextEntry::make('is_internal_note')
                                ->label('Visibility')
                                ->badge()
                                ->state(fn (TicketReply $record): string => $record->is_internal_note ? 'Internal note' : 'Customer sees this')
                                ->color(fn (TicketReply $record): string => $record->is_internal_note ? 'warning' : 'success'),
                            TextEntry::make('attachments')
                                ->label('Files')
                                ->state(fn (TicketReply $record): string => count($record->attachments ?? []).' attached')
                                ->placeholder('None'),
                            TextEntry::make('message')->hiddenLabel()->html()->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}

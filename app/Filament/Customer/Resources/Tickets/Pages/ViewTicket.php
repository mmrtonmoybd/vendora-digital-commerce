<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Tickets\Pages;

use App\Exceptions\DomainException;
use App\Filament\Customer\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\TicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** @property-read Ticket $record */
final class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function getTitle(): string
    {
        return $this->record->subject;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->schema([
                    TextEntry::make('ticket_number')->label('Ticket')->copyable(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('priority')->badge(),
                    TextEntry::make('order.order_number')->label('Order')->placeholder('—'),
                ]),

            Section::make('Conversation')
                ->schema([
                    // publicReplies, not replies: internal staff notes are not the
                    // customer's to read.
                    RepeatableEntry::make('publicReplies')
                        ->hiddenLabel()
                        ->columns(2)
                        ->schema([
                            TextEntry::make('author')
                                ->label('From')
                                ->state(fn (TicketReply $r): string => $r->isFromStaff() ? 'Support team' : 'You')
                                ->badge()
                                ->color(fn (TicketReply $r): string => $r->isFromStaff() ? 'primary' : 'gray'),
                            TextEntry::make('created_at')->label('When')->dateTime(),
                            TextEntry::make('message')->hiddenLabel()->html()->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn (Ticket $record): bool => ! $record->isClosed())
                ->schema([
                    Textarea::make('message')->required()->rows(5)->hiddenLabel(),
                    FileUpload::make('attachments')
                        ->multiple()
                        ->maxFiles(3)
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                        ->disk('local')
                        ->directory('tickets'),
                ])
                ->action(function (Ticket $record, array $data): void {
                    try {
                        app(TicketService::class)->reply(
                            $record,
                            auth()->user(),
                            $data['message'],
                            $data['attachments'] ?? [],
                        );

                        Notification::make()->success()->title('Reply sent')->send();
                    } catch (DomainException $e) {
                        Notification::make()->danger()->title('Could not reply')->body($e->getMessage())->send();
                    }
                }),
        ];
    }
}

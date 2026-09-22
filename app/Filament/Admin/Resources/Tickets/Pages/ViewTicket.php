<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Exceptions\DomainException;
use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Models\CannedResponse;
use App\Models\Ticket;
use App\Services\TicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Set;

final class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function getTitle(): string
    {
        return $this->record->ticket_number.' — '.$this->record->subject;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Reply')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('primary')
                ->schema([
                    Select::make('canned_response')
                        ->label('Insert a canned response')
                        ->options(fn (): array => CannedResponse::where('is_active', true)->pluck('title', 'id')->all())
                        ->searchable()
                        ->live()
                        ->native(false)
                        // Fills the editor and then clears itself, so it reads as an
                        // insert rather than a selection that stays stuck on screen.
                        ->afterStateUpdated(function (Set $set, $state): void {
                            if ($state === null) {
                                return;
                            }

                            $set('message', CannedResponse::find($state)?->body ?? '');
                            $set('canned_response', null);
                        }),
                    RichEditor::make('message')->required()->hiddenLabel(),
                    FileUpload::make('attachments')
                        ->multiple()
                        ->maxFiles(3)
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                        // Private disk: attachments can carry receipts and personal details.
                        ->disk('local')
                        ->directory('tickets'),
                    Toggle::make('is_internal_note')
                        ->label('Internal note')
                        ->helperText('Visible to staff only. The customer is not notified.'),
                ])
                ->action(function (Ticket $record, array $data): void {
                    try {
                        app(TicketService::class)->reply(
                            $record,
                            auth('admin')->user(),
                            $data['message'],
                            $data['attachments'] ?? [],
                            (bool) ($data['is_internal_note'] ?? false),
                        );

                        Notification::make()->success()->title('Reply sent')->send();
                    } catch (DomainException $e) {
                        Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
                    }
                }),

            Action::make('assignToMe')
                ->label('Assign to me')
                ->icon('heroicon-o-user-plus')
                ->visible(fn (Ticket $record): bool => $record->assigned_admin_id !== auth('admin')->id())
                ->action(function (Ticket $record): void {
                    app(TicketService::class)->assign($record, auth('admin')->user());

                    Notification::make()->success()->title('Assigned to you')->send();
                }),

            Action::make('close')
                ->label('Close ticket')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Ticket $record): bool => ! $record->isClosed())
                ->action(function (Ticket $record): void {
                    app(TicketService::class)->close($record, auth('admin')->user());

                    Notification::make()->success()->title('Ticket closed')->send();
                }),

            Action::make('reopen')
                ->label('Reopen')
                ->icon('heroicon-o-lock-open')
                ->visible(fn (Ticket $record): bool => $record->isClosed())
                ->action(function (Ticket $record): void {
                    app(TicketService::class)->reopen($record);

                    Notification::make()->success()->title('Ticket reopened')->send();
                }),

            Action::make('viewOrder')
                ->label('Open the order')
                ->icon('heroicon-o-shopping-bag')
                ->visible(fn (Ticket $record): bool => $record->order !== null)
                ->url(fn (Ticket $record): string => \App\Filament\Admin\Resources\Orders\OrderResource::getUrl('view', ['record' => $record->order])),
        ];
    }
}

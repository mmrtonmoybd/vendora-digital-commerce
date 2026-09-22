<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ContactMessages;

use App\Enums\ContactMessageStatus;
use App\Filament\Admin\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

final class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|null|UnitEnum $navigationGroup = 'Support';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Contact messages';

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::where('status', ContactMessageStatus::New->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold')
                    ->description(fn (ContactMessage $r): string => $r->email),
                TextColumn::make('subject')->searchable()->badge()->color('gray'),
                TextColumn::make('message')->wrap()->limit(80),
                TextColumn::make('phone')->copyable()->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(ContactMessageStatus::class)])
            ->recordActions([
                ActionGroup::make([
                    Action::make('reply')
                        ->label('Reply by email')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        // Opens the operator's own mail client: replies to a contact form
                        // are a conversation, not something this shop needs to host.
                        ->url(fn (ContactMessage $record): string => 'mailto:'.$record->email
                            .'?subject='.rawurlencode('Re: '.$record->subject))
                        ->after(fn (ContactMessage $record) => $record->update([
                            'status' => ContactMessageStatus::Replied,
                            'replied_at' => now(),
                        ])),
                    Action::make('markRead')
                        ->label('Mark as read')
                        ->icon('heroicon-o-envelope-open')
                        ->visible(fn (ContactMessage $r): bool => $r->status === ContactMessageStatus::New)
                        ->action(function (ContactMessage $record): void {
                            $record->update(['status' => ContactMessageStatus::Read]);

                            Notification::make()->success()->title('Marked as read')->send();
                        }),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListContactMessages::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

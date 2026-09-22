<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Admins\Tables;

use App\Enums\UserStatus;
use App\Models\Admin;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AdminsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                ImageColumn::make('avatar_url')->label('')->circular()->disk('public'),
                TextColumn::make('name')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('roles.name')->label('Roles')->badge()->color('primary'),
                TextColumn::make('status')->badge(),
                TextColumn::make('two_factor_confirmed_at')
                    ->label('2FA')
                    ->badge()
                    ->state(fn (Admin $record): string => $record->two_factor_confirmed_at ? 'On' : 'Off')
                    ->color(fn (Admin $record): string => $record->two_factor_confirmed_at ? 'success' : 'gray'),
                TextColumn::make('last_login_at')->label('Last seen')->dateTime()->sortable()->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('status')->options(UserStatus::class),
                SelectFilter::make('roles')->relationship('roles', 'name')->preload()->multiple(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('toggleStatus')
                        ->label(fn (Admin $record): string => $record->status === UserStatus::Active ? 'Suspend' : 'Activate')
                        ->icon(fn (Admin $record): string => $record->status === UserStatus::Active ? 'heroicon-o-pause-circle' : 'heroicon-o-check-circle')
                        ->color(fn (Admin $record): string => $record->status === UserStatus::Active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        // Locking yourself out of the only panel that can unlock you is a
                        // support ticket nobody can answer.
                        ->visible(fn (Admin $record): bool => $record->isNot(auth('admin')->user()))
                        ->action(function (Admin $record): void {
                            $record->update([
                                'status' => $record->status === UserStatus::Active ? UserStatus::Suspended : UserStatus::Active,
                            ]);

                            Notification::make()->success()->title('Status updated')->send();
                        }),
                    DeleteAction::make()->visible(fn (Admin $record): bool => $record->isNot(auth('admin')->user())),
                ]),
            ]);
    }
}

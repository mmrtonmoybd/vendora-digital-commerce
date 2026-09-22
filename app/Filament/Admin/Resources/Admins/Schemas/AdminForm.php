<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Admins\Schemas;

use App\Enums\UserStatus;
use App\Models\Admin;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

final class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(190)
                        ->unique(Admin::class, 'email', ignoreRecord: true),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        // Leaving the field blank on edit must not blank the password.
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->helperText('Leave blank to keep the current password.'),
                    Select::make('status')
                        ->options(UserStatus::class)
                        ->default(UserStatus::Active)
                        ->required()
                        ->helperText('Only an active account can sign in.'),
                    FileUpload::make('avatar_url')
                        ->label('Avatar')
                        ->avatar()
                        ->disk('public')
                        ->directory('avatars')
                        ->visibility('public'),
                ]),

            Section::make('Roles')
                ->description('What this person is allowed to do. Permissions come from the role.')
                ->schema([
                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->required()
                        ->hiddenLabel()
                        // Only a super admin may hand out roles at all, and the role list
                        // is where every permission in the panel ultimately comes from.
                        ->disabled(fn (): bool => ! (auth('admin')->user()?->isSuperAdmin() ?? false))
                        ->helperText(fn (): ?string => (auth('admin')->user()?->isSuperAdmin() ?? false)
                            ? null
                            : 'Only a super admin can change roles.'),
                ]),
        ]);
    }
}

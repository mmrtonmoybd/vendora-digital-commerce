<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\RelationManagers\DepositsRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\WalletTransactionsRelationManager;
use App\Filament\Admin\Resources\Users\Schemas\UserInfolist;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|null|UnitEnum $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    // The people who buy are "customers" throughout the panel; "user" is a database word,
    // not something an operator should ever have to read. Setting the model labels renames
    // the navigation item, the page titles, the breadcrumbs and every generated button.
    protected static ?string $modelLabel = 'customer';

    protected static ?string $pluralModelLabel = 'customers';

    protected static ?string $slug = 'customers';

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            DepositsRelationManager::class,
            WalletTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'referral_code'];
    }

    public static function canCreate(): bool
    {
        // Customers register themselves. Creating one here would skip email verification
        // and the referral attribution that comes with signing up.
        return false;
    }
}

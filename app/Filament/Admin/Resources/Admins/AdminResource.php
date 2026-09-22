<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Admins;

use App\Filament\Admin\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Admin\Resources\Admins\Pages\EditAdmin;
use App\Filament\Admin\Resources\Admins\Pages\ListAdmins;
use App\Filament\Admin\Resources\Admins\Schemas\AdminForm;
use App\Filament\Admin\Resources\Admins\Tables\AdminsTable;
use App\Models\Admin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Staff accounts.
 *
 * Deliberately separate from customers: different table, different guard, different
 * password broker. A customer session can never be mistaken for a staff session.
 */
final class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Staff accounts';

    protected static ?int $navigationSort = 89;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
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
        return ['name', 'email'];
    }
}

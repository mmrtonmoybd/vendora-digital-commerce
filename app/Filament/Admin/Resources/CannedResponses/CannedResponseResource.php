<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CannedResponses;

use App\Filament\Admin\Resources\CannedResponses\Pages\CreateCannedResponse;
use App\Filament\Admin\Resources\CannedResponses\Pages\EditCannedResponse;
use App\Filament\Admin\Resources\CannedResponses\Pages\ListCannedResponses;
use App\Models\CannedResponse;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Saved replies, offered in the ticket reply box. */
final class CannedResponseResource extends Resource
{
    protected static ?string $model = CannedResponse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static string|null|UnitEnum $navigationGroup = 'Support';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('title')->required()->maxLength(180),
                RichEditor::make('body')->required(),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->searchable()->weight('semibold'),
                TextColumn::make('body')->html()->limit(80)->wrap(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Active')])
            ->recordActions([ActionGroup::make([EditAction::make()])])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCannedResponses::route('/'),
            'create' => CreateCannedResponse::route('/create'),
            'edit' => EditCannedResponse::route('/{record}/edit'),
        ];
    }
}

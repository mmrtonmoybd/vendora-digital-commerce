<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdminMessages;

use App\Enums\MessageAudience;
use App\Filament\Admin\Resources\AdminMessages\Pages\CreateAdminMessage;
use App\Filament\Admin\Resources\AdminMessages\Pages\EditAdminMessage;
use App\Filament\Admin\Resources\AdminMessages\Pages\ListAdminMessages;
use App\Models\AdminMessage;
use App\Models\Category;
use App\Models\Product;
use App\Services\AdminMessageService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Announcements to customers.
 *
 * The audience is resolved at send time, not at compose time, so a message aimed at
 * "buyers of X" reaches everyone who qualifies the moment it goes out. Sending is a
 * separate, deliberate action — a draft is never delivered by saving it.
 */
final class AdminMessageResource extends Resource
{
    protected static ?string $model = AdminMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|null|UnitEnum $navigationGroup = 'Support';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'subject';

    protected static ?string $navigationLabel = 'Announcements';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Message')->columns(2)->schema([
                TextInput::make('subject')->required()->maxLength(190)->columnSpanFull(),
                RichEditor::make('body')->required()->columnSpanFull(),
                Toggle::make('send_email')
                    ->label('Also send as email')
                    ->helperText('Otherwise it only appears in the customer\'s notification bell.'),
            ]),

            Section::make('Audience')->columns(2)->schema([
                Select::make('audience')
                    ->options(MessageAudience::class)
                    ->default(MessageAudience::All)
                    ->required()
                    ->live()
                    ->native(false),
                Select::make('audience_meta.user_ids')
                    ->label('Customers')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->relationship('users', 'name')
                    ->visible(fn (Get $get): bool => $get('audience') === MessageAudience::SelectedUsers->value),
                Select::make('audience_meta.product_ids')
                    ->label('Products')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Product::pluck('name', 'id')->all())
                    ->visible(fn (Get $get): bool => $get('audience') === MessageAudience::BuyersOfProduct->value),
                Select::make('audience_meta.category_ids')
                    ->label('Categories')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Category::pluck('name', 'id')->all())
                    ->visible(fn (Get $get): bool => $get('audience') === MessageAudience::BuyersOfCategory->value),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject')->searchable()->weight('semibold')->wrap()->limit(60),
                TextColumn::make('audience')->badge(),
                TextColumn::make('recipient_count')->label('Recipients')->badge()->color('gray'),
                IconColumn::make('send_email')->label('Emailed')->boolean(),
                TextColumn::make('sent_at')->label('Sent')->dateTime()->placeholder('Draft')->sortable(),
                TextColumn::make('sentBy.name')->label('By')->placeholder('—')->toggleable(),
            ])
            ->filters([SelectFilter::make('audience')->options(MessageAudience::class)])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->visible(fn (AdminMessage $record): bool => ! $record->isSent()),

                    Action::make('previewAudience')
                        ->label('Preview audience')
                        ->icon('heroicon-o-eye')
                        ->action(function (AdminMessage $record): void {
                            $count = app(AdminMessageService::class)->previewCount($record);

                            Notification::make()
                                ->info()
                                ->title("This would reach {$count} customers")
                                ->send();
                        }),

                    Action::make('send')
                        ->label('Send now')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn (AdminMessage $record): bool => ! $record->isSent())
                        ->requiresConfirmation()
                        ->modalDescription(fn (AdminMessage $record): string => 'This reaches '
                            .app(AdminMessageService::class)->previewCount($record)
                            .' customers and cannot be taken back.')
                        ->action(function (AdminMessage $record): void {
                            $sent = app(AdminMessageService::class)->send($record);

                            Notification::make()->success()->title("Sent to {$sent} customers")->send();
                        }),

                    DeleteAction::make()->visible(fn (AdminMessage $record): bool => ! $record->isSent()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminMessages::route('/'),
            'create' => CreateAdminMessage::route('/create'),
            'edit' => EditAdminMessage::route('/{record}/edit'),
        ];
    }
}

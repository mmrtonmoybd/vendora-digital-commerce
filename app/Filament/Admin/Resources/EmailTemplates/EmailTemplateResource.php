<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EmailTemplates;

use App\Filament\Admin\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Filament\Admin\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * The wording of every transactional email.
 *
 * The key is fixed because the code sends by key; the subject and body are the operator's.
 * Saving clears the template cache so the next email uses the new wording.
 */
final class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|null|UnitEnum $navigationGroup = 'Content';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Email templates';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(120),
                    TextInput::make('key')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Fixed: the application sends by this key.'),
                    TextInput::make('subject')->required()->maxLength(190)->columnSpanFull(),
                    Textarea::make('body')
                        ->required()
                        ->rows(12)
                        ->columnSpanFull()
                        ->helperText('Markdown. Use the placeholders below; anything else is sent literally.'),
                    Placeholder::make('variables')
                        ->label('Available placeholders')
                        ->columnSpanFull()
                        ->content(fn (?EmailTemplate $record): HtmlString => new HtmlString(
                            collect($record?->available_variables ?? [])
                                ->map(fn (string $v): string => '<code class="mr-2">{{ '.e($v).' }}</code>')
                                ->implode(' ') ?: '<span class="text-gray-500">None</span>'
                        )),
                    Toggle::make('is_active')
                        ->helperText('When off, this email is not sent at all.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold'),
                TextColumn::make('key')->badge()->color('gray')->copyable(),
                TextColumn::make('subject')->wrap()->limit(60),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Active')])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('sendTest')
                        ->label('Send a test')
                        ->icon('heroicon-o-paper-airplane')
                        ->schema([
                            TextInput::make('to')->email()->required()
                                ->default(fn (): ?string => auth('admin')->user()?->email),
                        ])
                        // Placeholders are filled with their own names, so the operator can
                        // see at a glance which ones actually resolved.
                        ->action(function (EmailTemplate $record, array $data): void {
                            $sample = collect($record->available_variables ?? [])
                                ->mapWithKeys(fn (string $v): array => [$v => '['.$v.']'])
                                ->all();

                            Mail::to($data['to'])->send(new TemplatedMail(
                                $record->render('subject', $sample),
                                $record->render('body', $sample),
                                $sample,
                            ));

                            Notification::make()->success()->title('Test email sent')->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // Templates exist because code sends them. A new one nothing sends is dead weight.
        return false;
    }
}

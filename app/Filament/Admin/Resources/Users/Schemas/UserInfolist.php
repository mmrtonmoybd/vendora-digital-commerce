<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Customer')
                ->columns(4)
                ->schema([
                    TextEntry::make('name')->weight('bold'),
                    TextEntry::make('email')->copyable(),
                    TextEntry::make('phone')->copyable()->placeholder('—'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('balance')
                        ->label('Wallet balance')
                        ->formatStateUsing(fn (int $state): string => Money::format($state))
                        ->weight('bold'),
                    TextEntry::make('referral_code')->label('Referral code')->copyable(),
                    TextEntry::make('referrer.name')->label('Referred by')->placeholder('Nobody'),
                    TextEntry::make('signupSource.name')->label('Signed up from')->badge()->color('info')->placeholder('Direct'),
                    TextEntry::make('email_verified_at')->label('Email verified')->dateTime()->placeholder('Not verified'),
                    TextEntry::make('created_at')->label('Joined')->dateTime(),
                    TextEntry::make('last_login_at')->label('Last seen')->dateTime()->placeholder('Never'),
                    TextEntry::make('last_login_ip')->label('Last IP')->placeholder('—'),
                ]),
        ]);
    }
}

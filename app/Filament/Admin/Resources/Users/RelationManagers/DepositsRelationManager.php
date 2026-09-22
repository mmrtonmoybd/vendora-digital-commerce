<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Support\MoneyColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DepositsRelationManager extends RelationManager
{
    protected static string $relationship = 'deposits';

    protected static ?string $title = 'Deposits';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('deposit_number')->label('Deposit')->searchable()->copyable(),
                TextColumn::make('paymentMethod.name')->label('Method')->badge()->color('gray'),
                MoneyColumn::make('amount'),
                TextColumn::make('transaction_id')->label('TrxID')->copyable()->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }
}

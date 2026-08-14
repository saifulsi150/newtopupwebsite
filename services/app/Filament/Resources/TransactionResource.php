<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales & Orders';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Record')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Trx ID / Gateway Ref')
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (৳)')
                            ->numeric()
                            ->prefix('৳')
                            ->required(),

                        Forms\Components\TextInput::make('method')
                            ->label('Payment Method')
                            ->placeholder('bKash / Nagad / Rocket / UddoktaPay'),

                        Forms\Components\TextInput::make('user_gmail')
                            ->label('Customer Email'),

                        Forms\Components\TextInput::make('order_id')
                            ->label('Associated Order ID'),

                        Forms\Components\TextInput::make('unpaid')
                            ->label('Status (0 = Paid, 1 = Unpaid)')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Trx ID')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user_gmail')
                    ->label('Customer Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unpaid')
                    ->label('Payment Status')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'Paid' : 'Unpaid')
                    ->badge()
                    ->color(fn ($state) => $state == 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}

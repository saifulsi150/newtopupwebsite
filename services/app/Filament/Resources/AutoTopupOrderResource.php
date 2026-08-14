<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutoTopupOrderResource\Pages;
use App\Models\AutoTopupOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutoTopupOrderResource extends Resource
{
    protected static ?string $model = AutoTopupOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Sales & Orders';

    protected static ?string $modelLabel = 'Auto Topup Order';

    protected static ?string $pluralModelLabel = 'Auto Topup Logs';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Auto Topup Execution')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('Order ID')
                            ->relationship('order', 'track_id')
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('provider_name')
                            ->label('Provider')
                            ->required(),

                        Forms\Components\TextInput::make('status')
                            ->label('Execution Status')
                            ->required(),

                        Forms\Components\Textarea::make('response_payload')
                            ->label('API Response')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('error_message')
                            ->label('Error (if any)')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.track_id')
                    ->label('Order Track ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider_name')
                    ->label('Provider')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'success' => 'success',
                        'failed', 'error' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListAutoTopupOrders::route('/'),
            'edit' => Pages\EditAutoTopupOrder::route('/{record}/edit'),
        ];
    }
}

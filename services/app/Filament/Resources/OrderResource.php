<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Sales & Orders';

    protected static ?string $modelLabel = 'Order';

    protected static ?string $pluralModelLabel = 'Orders';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Order Information')
                            ->schema([
                                Forms\Components\TextInput::make('track_id')
                                    ->label('Track ID')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Select::make('user_id')
                                    ->label('Customer')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'title')
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('variation_id')
                                    ->label('Package / Variation')
                                    ->relationship('variation', 'title')
                                    ->searchable()
                                    ->required(),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Total Amount (৳)')
                                    ->numeric()
                                    ->prefix('৳')
                                    ->required(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->label('Order Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'auto-processing' => 'Auto Processing',
                                        'completed' => 'Completed',
                                        'cancel' => 'Cancelled',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('voucher_code')
                                    ->label('Redeemed / Voucher Code')
                                    ->maxLength(255),
                            ])->columns(2),

                        Forms\Components\Section::make('Delivery & Notes')
                            ->schema([
                                Forms\Components\Textarea::make('delivery_message')
                                    ->label('Customer Delivery Message / Passcode / Link')
                                    ->placeholder('Information visible to customer upon completion')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('admin_note')
                                    ->label('Admin Internal Note')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('error_message')
                                    ->label('Error Message (if auto topup failed)')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Customer Account Info')
                            ->schema([
                                Forms\Components\KeyValue::make('account_info')
                                    ->label('Player ID / Login Data')
                                    ->keyLabel('Field')
                                    ->valueLabel('Value'),
                            ]),

                        Forms\Components\Section::make('Provider / Gateway Data')
                            ->schema([
                                Forms\Components\TextInput::make('external_ref')
                                    ->label('Payment Trx / External Ref')
                                    ->disabled(),

                                Forms\Components\KeyValue::make('provider_data')
                                    ->label('API Provider Data'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('track_id')
                    ->label('Track ID')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Order $record): ?string => $record->user?->email),

                Tables\Columns\TextColumn::make('product.title')
                    ->label('Product')
                    ->searchable()
                    ->description(fn (Order $record): ?string => $record->variation?->title),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'auto-processing' => 'info',
                        'pending' => 'gray',
                        'cancel' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ordered At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'auto-processing' => 'Auto Processing',
                        'completed' => 'Completed',
                        'cancel' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'title'),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status !== 'completed')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'completed']);
                        Notification::make()
                            ->title('Order marked as Completed')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status !== 'cancel')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'cancel']);
                        Notification::make()
                            ->title('Order marked as Cancelled')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

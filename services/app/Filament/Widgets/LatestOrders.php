<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest()->limit(8)
            )
            ->heading('Recent Orders')
            ->columns([
                Tables\Columns\TextColumn::make('track_id')
                    ->label('Track ID')
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer'),

                Tables\Columns\TextColumn::make('product.title')
                    ->label('Product'),

                Tables\Columns\TextColumn::make('variation.title')
                    ->label('Package'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT'),

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
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->since(),
            ])
            ->paginated(false);
    }
}

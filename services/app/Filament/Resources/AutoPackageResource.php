<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutoPackageResource\Pages;
use App\Models\AutoPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutoPackageResource extends Resource
{
    protected static ?string $model = AutoPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Shop Management';

    protected static ?string $modelLabel = 'Auto Package';

    protected static ?string $pluralModelLabel = 'Auto Packages';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Auto Topup Mapping')
                    ->schema([
                        Forms\Components\Select::make('variation_id')
                            ->label('Linked Package / Variation')
                            ->relationship('variation', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('provider_id')
                            ->label('Provider Identifier')
                            ->placeholder('e.g. smileone / unipin')
                            ->required(),

                        Forms\Components\TextInput::make('provider_product_id')
                            ->label('Provider Product ID')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('variation.title')
                    ->label('Linked Package')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider_id')
                    ->label('Provider')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('provider_product_id')
                    ->label('Provider Product ID')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
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
            'index' => Pages\ListAutoPackages::route('/'),
            'create' => Pages\CreateAutoPackage::route('/create'),
            'edit' => Pages\EditAutoPackage::route('/{record}/edit'),
        ];
    }
}

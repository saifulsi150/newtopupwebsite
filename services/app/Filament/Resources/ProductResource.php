<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Categorie;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Shop Management';

    protected static ?string $modelLabel = 'Product';

    protected static ?string $pluralModelLabel = 'Products';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('General Information')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Product Title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

                                Forms\Components\TextInput::make('slug')
                                    ->label('URL Slug')
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Forms\Components\Select::make('categorie_id')
                                    ->label('Category')
                                    ->relationship('categorie', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('type')
                                    ->label('Product / Topup Type')
                                    ->options([
                                        'IDCODE' => 'ID Code (Player ID / UID)',
                                        'INGAME' => 'In Game (Login & Password)',
                                        'VOUCHER' => 'Voucher / Redeem Code',
                                        'SUBSCRIPTION' => 'Subscription (Weekly/Monthly)',
                                    ])
                                    ->default('IDCODE')
                                    ->required(),

                                Forms\Components\RichEditor::make('content')
                                    ->label('Instructions / Description')
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Image & Settings')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Product Image')
                                    ->image()
                                    ->directory('products')
                                    ->required(),

                                Forms\Components\TextInput::make('input')
                                    ->label('Input Label')
                                    ->placeholder('e.g. Player ID / Zone ID')
                                    ->helperText('Placeholder shown in topup input box'),

                                Forms\Components\TextInput::make('slot')
                                    ->label('Display Order')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\TextInput::make('percentage')
                                    ->label('Bonus / Discount (%)')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\Select::make('uid_checker')
                                    ->label('UID Validator')
                                    ->options([
                                        0 => 'Disabled',
                                        1 => 'Free Fire UID Check',
                                        2 => 'PUBG UID Check',
                                        3 => 'Mobile Legends UID Check',
                                    ])
                                    ->default(0),

                                Forms\Components\Toggle::make('status')
                                    ->label('Active Status')
                                    ->default(true),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Thumbnail')
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('categorie.title')
                    ->label('Category')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'IDCODE' => 'success',
                        'INGAME' => 'warning',
                        'VOUCHER' => 'info',
                        'SUBSCRIPTION' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('slot')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('slot', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('categorie_id')
                    ->label('Category')
                    ->relationship('categorie', 'title'),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'IDCODE' => 'ID Code',
                        'INGAME' => 'In Game',
                        'VOUCHER' => 'Voucher',
                        'SUBSCRIPTION' => 'Subscription',
                    ]),

                Tables\Filters\TernaryFilter::make('status')
                    ->label('Active Status'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

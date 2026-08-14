<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Users & Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers & Users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Profile')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone / WhatsApp')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),

                        Forms\Components\TextInput::make('balance')
                            ->label('Wallet Balance (৳)')
                            ->numeric()
                            ->prefix('৳')
                            ->default(0),

                        Forms\Components\TextInput::make('coins')
                            ->label('Reward Coins')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_reseller')
                            ->label('Reseller Account')
                            ->default(false),

                        Forms\Components\Toggle::make('status')
                            ->label('Active / Unbanned')
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('BDT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('coins')
                    ->label('Coins')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_reseller')
                    ->label('Reseller')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Account Status'),
                Tables\Filters\TernaryFilter::make('is_reseller')
                    ->label('Reseller Only'),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_balance')
                    ->label('Adjust Balance')
                    ->icon('heroicon-m-banknotes')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('action_type')
                            ->label('Action')
                            ->options([
                                'add' => 'Add Balance (+)',
                                'deduct' => 'Deduct Balance (-)',
                            ])
                            ->default('add')
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (৳)')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $current = (float) $record->balance;
                        $amount = (float) $data['amount'];
                        $new = $data['action_type'] === 'add' ? ($current + $amount) : max(0, $current - $amount);
                        $record->update(['balance' => (string) $new]);
                        Notification::make()
                            ->title("Balance updated for {$record->name}: ৳{$new}")
                            ->success()
                            ->send();
                    }),

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

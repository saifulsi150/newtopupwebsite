<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageGeneralSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'General Site Settings';

    protected static ?int $navigationSort = 2;

    protected static string $settings = GeneralSettings::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('update_system')
                ->label('Update System')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Update System from GitHub')
                ->modalDescription('Are you sure you want to update? This will pull the latest code from GitHub (newtopupwebsite.git) and update the database.')
                ->action(function () {
                    try {
                        $projectRoot = base_path('../..');
                        $repo = 'https://github.com/saifulsi150/newtopupwebsite.git';
                        
                        // force pull
                        $output = shell_exec("git -C " . escapeshellarg($projectRoot) . " pull $repo main 2>&1");
                        
                        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();
                        
                        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                        
                        \Filament\Notifications\Notification::make()
                            ->title('System Updated Successfully')
                            ->body("Git: $output \nMigrate: $migrateOutput")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Update Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General & Branding')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')
                                    ->label('Website Name')
                                    ->default('TAST Topup'),

                                Forms\Components\TextInput::make('site_title')
                                    ->label('Site Title / Tagline')
                                    ->default('Best Gaming Topup in Bangladesh'),

                                Forms\Components\FileUpload::make('logo')
                                    ->label('Website Logo')
                                    ->image()
                                    ->directory('settings'),

                                Forms\Components\FileUpload::make('favicon')
                                    ->label('Website Favicon')
                                    ->image()
                                    ->directory('settings'),

                                Forms\Components\TextInput::make('currency_symbol')
                                    ->label('Currency Symbol')
                                    ->default('৳'),

                                Forms\Components\TextInput::make('base_currency')
                                    ->label('Base Currency Code')
                                    ->default('BDT'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Support & Contact')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\TextInput::make('telegram_link')
                                    ->label('Telegram Support Link')
                                    ->placeholder('https://t.me/yourusername'),

                                Forms\Components\TextInput::make('whatsapp_number')
                                    ->label('WhatsApp Number')
                                    ->placeholder('+8801...'),

                                Forms\Components\TextInput::make('support_number')
                                    ->label('Support Helpline Number'),

                                Forms\Components\TextInput::make('facebook_link')
                                    ->label('Facebook Page Link'),

                                Forms\Components\TextInput::make('youtube_link')
                                    ->label('YouTube Channel Link'),

                                Forms\Components\TextInput::make('email_address')
                                    ->label('Support Email Address'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('UddoktaPay Payment')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\TextInput::make('uddoktapay_api_key')
                                    ->label('UddoktaPay API Key')
                                    ->password(),

                                Forms\Components\TextInput::make('uddoktapay_api_url')
                                    ->label('UddoktaPay Base URL')
                                    ->placeholder('https://sandbox.uddoktapay.com/api/checkout-v2'),

                                Forms\Components\TextInput::make('uddoktapay_min_amount')
                                    ->label('Minimum Add Money (৳)')
                                    ->numeric()
                                    ->default(10),

                                Forms\Components\TextInput::make('uddoktapay_max_amount')
                                    ->label('Maximum Add Money (৳)')
                                    ->numeric()
                                    ->default(50000),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Notice & Alerts')
                            ->icon('heroicon-o-megaphone')
                            ->schema([
                                Forms\Components\Toggle::make('enable_notice')
                                    ->label('Show Website Notice Banner')
                                    ->default(false),

                                Forms\Components\TextInput::make('notice_title')
                                    ->label('Notice Title')
                                    ->placeholder('Special Notice'),

                                Forms\Components\Textarea::make('notice_content')
                                    ->label('Notice Message')
                                    ->placeholder('Type the announcement here...')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Topup & Automation')
                            ->icon('heroicon-o-bolt')
                            ->schema([
                                Forms\Components\Toggle::make('enable_auto_topup')
                                    ->label('Enable Auto Topup API')
                                    ->default(true),

                                Forms\Components\Toggle::make('enable_uid_checker')
                                    ->label('Enable Free Fire UID Validation')
                                    ->default(true),

                                Forms\Components\TextInput::make('website_api_url')
                                    ->label('Topup Server API URL'),

                                Forms\Components\TextInput::make('website_api_key')
                                    ->label('Topup Server API Key')
                                    ->password(),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('User Frontend')
                            ->icon('heroicon-o-window')
                            ->schema([
                                Forms\Components\Toggle::make('slider_enabled')
                                    ->label('Show Slider')
                                    ->default(true),

                                Forms\Components\Toggle::make('top_support_enabled')
                                    ->label('Show Top Support Buttons')
                                    ->default(true),

                                Forms\Components\Toggle::make('category_enabled')
                                    ->label('Show Categories Section')
                                    ->default(true),

                                Forms\Components\Toggle::make('latest_orders_enabled')
                                    ->label('Show Latest Orders')
                                    ->default(true),

                                Forms\Components\Toggle::make('pgw_app_enabled')
                                    ->label('Enable PGW App Link')
                                    ->default(true),

                                Forms\Components\Toggle::make('detect_popup_enabled')
                                    ->label('Show Detect User Popup')
                                    ->default(false),

                                Forms\Components\Toggle::make('home_page_popup_enabled')
                                    ->label('Show Home Page Popup')
                                    ->default(false),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }
}

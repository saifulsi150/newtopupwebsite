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
                ->modalHeading('Update System to Latest Version')
                ->modalDescription('Are you sure you want to update the system? This will automatically download the latest updates, apply database changes, and clear caches. The system may take a few moments to restart.')
                ->action(function () {
                    try {
                        $projectRoot = base_path('..');
                        $updateScript = $projectRoot . '/update.sh';
                        
                        if (file_exists($updateScript)) {
                            shell_exec("nohup bash " . escapeshellarg($updateScript) . " > " . escapeshellarg($projectRoot . "/update.log") . " 2>&1 &");
                        } else {
                            $repo = 'https://github.com/saifulsi150/newtopupwebsite.git';
                            shell_exec("nohup sh -c 'git -C " . escapeshellarg($projectRoot) . " fetch $repo main && git -C " . escapeshellarg($projectRoot) . " reset --hard FETCH_HEAD && php " . escapeshellarg($projectRoot . "/services/artisan") . " migrate --force && php " . escapeshellarg($projectRoot . "/services/artisan") . " optimize:clear' > " . escapeshellarg($projectRoot . "/update.log") . " 2>&1 &");
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Update Started')
                            ->body('The system is updating in the background. It may take 1-2 minutes to complete.')
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

                        Forms\Components\Tabs\Tab::make('Theme & Colors')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Forms\Components\ColorPicker::make('theme_color')
                                    ->label('Primary / Theme Color')
                                    ->default('#0d682f'),

                                Forms\Components\ColorPicker::make('background_color')
                                    ->label('Website Background Color')
                                    ->default('#f1f6fc'),

                                Forms\Components\ColorPicker::make('navigation_background_color')
                                    ->label('Header / Navbar Background Color')
                                    ->default('#ffffff'),

                                Forms\Components\ColorPicker::make('notice_background_color')
                                    ->label('Notice Banner Background Color')
                                    ->default('#0d682f'),

                                Forms\Components\ColorPicker::make('notice_font_color')
                                    ->label('Notice Text Color')
                                    ->default('#ffffff'),

                                Forms\Components\ColorPicker::make('footer_color')
                                    ->label('Footer & Support Background Color')
                                    ->default('#030d36'),

                                Forms\Components\ColorPicker::make('footer_font_color')
                                    ->label('Footer Text Color')
                                    ->default('#ffffff'),
                            ])->columns(2),

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
                                Forms\Components\Toggle::make('top_support_enabled')
                                    ->label('Show Top Support Buttons')
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

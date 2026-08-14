<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.paginate_per_page', 10);
        $this->migrator->add('general.wallet', false);
        $this->migrator->add('general.uddoktapay_min_amount', 10);
        $this->migrator->add('general.uddoktapay_max_amount', 10000);
        $this->migrator->add('general.background_image', false);
        $this->migrator->add('general.footer_menu', false);
        $this->migrator->add('general.enable_notice', false);
        $this->migrator->add('general.enable_pwa', false);
        $this->migrator->add('general.enable_uid_checker', false);
        $this->migrator->add('general.enable_auto_topup', false);
    }
};

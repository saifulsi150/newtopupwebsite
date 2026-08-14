<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.slider_enabled', true);
        $this->migrator->add('general.top_support_enabled', true);
        $this->migrator->add('general.category_enabled', true);
        $this->migrator->add('general.latest_orders_enabled', true);
        $this->migrator->add('general.detect_popup_enabled', false);
        $this->migrator->add('general.home_page_popup_enabled', false);
        $this->migrator->add('general.pgw_app_enabled', true);
    }
};

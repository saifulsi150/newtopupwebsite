<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        try {
            $this->migrator->add('general.enable_page_loading_bar', true);
        } catch (\Throwable) {
            // Setting already exists in some environments.
        }
    }
};

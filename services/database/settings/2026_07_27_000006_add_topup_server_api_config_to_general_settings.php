<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        try {
            $this->migrator->add('general.website_api_url', '');
        } catch (\Throwable) {
            // Setting already exists.
        }

        try {
            $this->migrator->add('general.website_api_key', '');
        } catch (\Throwable) {
            // Setting already exists.
        }

        try {
            $this->migrator->add('general.automation_enabled', true);
        } catch (\Throwable) {
            // Setting already exists.
        }
    }
};

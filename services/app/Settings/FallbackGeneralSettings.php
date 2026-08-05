<?php

namespace App\Settings;

class FallbackGeneralSettings
{
    public $currency_symbol = '$';
    public $site_name = 'LX TOPUP';
    public $site_logo = '';
    public $site_favicon = '';
    public $site_description = '';
    public $admin_email = 'admin@example.com';
    public $support_email = 'support@example.com';
    public $enable_page_loading_bar = true;

    public function __get($name)
    {
        return $this->$name ?? null;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __call($name, $arguments)
    {
        return null;
    }
}

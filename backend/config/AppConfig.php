<?php

namespace App\config;

class AppConfig extends Config
{
    private const CONFIG_FILE = __DIR__ . '/properties/app-properties.ini';
    public static function get_setting($setting_name): int|string|null
    {
        return parent::get_setting_for_file(setting_name: $setting_name, file_path: self::CONFIG_FILE);
    }
}

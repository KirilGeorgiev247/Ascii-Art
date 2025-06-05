<?php

namespace App\config;

class Config
{
    protected static function get_setting_for_file($setting_name, $file_path): int | string | null
    {
        if (!file_exists($file_path)) {
            throw new \Exception("Configuration file not found: " . $file_path);
        }
        $config = parse_ini_file(filename: realpath(path: $file_path));
        return isset($config[$setting_name]) ? $config[$setting_name] : null;
    }
}

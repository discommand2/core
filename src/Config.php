<?php

namespace Discommand2\Core;

class Config
{
    public static function get(string $name, string $key = null): mixed
    {
        $path = __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . 'configs' .
            DIRECTORY_SEPARATOR . $name . '.json';

        if (!file_exists($path)) return null;
        $config = json_decode(file_get_contents($path), true);
        if ($key === null) return $config;
        return $config[$key] ?? null;
    }

    public static function set(string $name, string|array $key, mixed $value = null): bool
    {
        $path = __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . 'configs' .
            DIRECTORY_SEPARATOR . $name . '.json';

        if (!file_exists($path)) return false;
        $config = json_decode(file_get_contents($path), true);
        if (is_array($key)) {
            $config = array_merge($config, $key);
        } else {
            $config[$key] = $value;
        }
        file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT));
        return true;
    }
}

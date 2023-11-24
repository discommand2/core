<?php

namespace Discommand2\Core;

class Config
{
    public static function get(string $name, string $key = null): mixed
    {
        // expects to be installed under vendor/discommand2/core/src
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

    public static function set(string $name, array $keys, mixed $value = null): bool
    {
        // expects to be installed under vendor/discommand2/core/src
        $path = __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . 'configs' .
            DIRECTORY_SEPARATOR . $name . '.json';

        if (!file_exists($path)) return false;
        $config = json_decode(file_get_contents($path), true);

        $temp = &$config;
        foreach ($keys as $key) {
            if (!isset($temp[$key])) $temp[$key] = [];
            $temp = &$temp[$key];
        }

        $temp = $value;

        file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT));
        return true;
    }
}

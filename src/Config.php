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
            DIRECTORY_SEPARATOR . 'config' .
            DIRECTORY_SEPARATOR . $name . '.json';

        if (!file_exists($path)) return null;
        $config = json_decode(file_get_contents($path), true);
        if ($key === null) return $config;
        return $config[$key] ?? null;
    }
}

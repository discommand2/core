<?php

namespace Discommand2\Core\Validators;

use Discommand2\Core\Config;

class SkullValidators
{
    public static function validateUpgrade($argv): array
    {
        $plugin = '';
        $force = false;
        if (isset($argv[2]) && $argv[2] != '') {
            if ($argv[2] !== 'force') {
                $force = isset($argv[3]) && $argv[3] === 'force';
                if (strpos($argv[2], '/') === false) $argv[2] = 'discommand2/' . $argv[2];
                $plugin = ' ' . $argv[2];
            } else {
                $force = true;
            }
        }
        return [$plugin, $force];
    }

    public static function validateCreate($argv): array
    {
        if (!isset($argv[2])) throw new \Exception("Brain name not specified!");
        if (!self::validateBrainName($argv[2])) throw new \Exception("Invalid brain name!");
        $brainName = $argv[2];
        $basePath = Config::get('discommand2', 'brains');
        $brainPath = $basePath . '/' . $brainName;
        if (file_exists($brainPath)) throw new \Exception("$brainName already exists! use config, start, or delete instead.");
        return [$brainName, $brainPath];
    }

    public static function createFromTemplate($argv): string
    {
        if (!isset($argv[3]) || $argv[3] === '') $argv[3] = "brain-template";
        if (strpos($argv[3], '/') === false) $argv[3] = 'discommand2/' . $argv[3];
        if (strpos($argv[3], 'https://') === 0) $url = $argv[3];
        else if (strpos($argv[3], 'git@github:') === 0) $url = $argv[3];
        else if (strpos($argv[3], 'bitbucket.org:') === 0) $url = $argv[3];
        else $url = 'git@github.com:' . $argv[3] . '.git';
        return $url;
    }

    public static function validateDelete($argv): array
    {
        if (!isset($argv[2])) throw new \Exception("Brain name not specified!");
        if (!self::validateBrainName($argv[2])) throw new \Exception("Invalid brain name!");
        $brainName = $argv[2];
        $brainPath = self::getPath($brainName);
        if (!file_exists($brainPath)) throw new \Exception("$brainName doesn't exist to begin with!");
        $force = isset($argv[3]) && $argv[3] === 'force';
        return [$brainName, $brainPath, $force];
    }

    public static function getPath($brainName): string
    {
        $basePath = Config::get('discommand2', 'brains');
        return $basePath . '/' . $brainName;
    }

    public static function validateBrainName($name): bool
    {
        // must be a valid linux username/foldername (no spaces, no special characters except _ and -)
        return preg_match('/^[a-z0-9_-]+$/i', $name);
    }

    public static function validatePluginName(string $homeDir, string $pluginName): bool
    {
        $composerLock = json_decode(file_get_contents($homeDir . '/composer.lock'), true);
        foreach ($composerLock['packages'] as $package) {
            $lsl = strrpos($package['name'], '/');
            $name = substr($package['name'], $lsl + 1);
            if ($name === $pluginName) return true;
            return false;
        }
    }
}

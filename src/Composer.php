<?php

namespace Discommand2\Core;

class Composer
{
    static function which_composer(): string
    {
        if (file_exists(__DIR__ . '/composer.phar')) return __DIR__ . '/composer.phar';
        if (file_exists(__DIR__ . '/composer')) return __DIR__ . '/../composer';
        $composer = trim(shell_exec('which composer') ?? '');
        if ($composer === '') $composer = trim(shell_exec('which composer.phar') ?? '');
        if ($composer === '') $composer = self::download_composer();
        return $composer;
    }

    static function download_composer(): string
    {
        $last_line = exec('curl -sS https://getcomposer.org/installer | php 2>&1', $output, $exit_code);
        if ($exit_code !== 0) throw new \Exception("Composer download failed: $last_line");
        if (!file_exists(__DIR__ . '/composer.phar')) throw new \Exception("Composer download failed!");
        return __DIR__ . '/composer.phar';
    }

    static function command(string $wdir, string $command): bool
    {
        $composer = self::which_composer();
        $wdir = escapeshellarg($wdir);
        exec("cd " . $wdir . "/.. && export COMPOSER_ALLOW_SUPERUSER=1 && export COMPOSER_NO_INTERACTION=1 && $composer $command 2>&1", $output, $exit_code);
        if ($exit_code !== 0) {
            // remove the first 3 lines and the last 4 lines
            array_splice($output, 0, 3);
            array_splice($output, -4);
            // trim each line
            $output = array_map('trim', $output);
            throw new \Exception(implode(" ", $output));
        }
        self::check_autoload_exists();
        return true;
    }

    static function check_autoload_exists(): void
    {
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) throw new \Exception("Composer command failed!");
    }
}

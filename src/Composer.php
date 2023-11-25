<?php

namespace Discommand2\Core;

class Composer
{
    static function which_composer(string $wdir): string
    {
        if (file_exists("$wdir/composer.phar")) return "$wdir/composer.phar";
        if (file_exists("$wdir/composer")) return "$wdir/composer";
        $composer = trim(shell_exec('which composer') ?? '');
        if ($composer !== '') return $composer;
        $composer = trim(shell_exec('which composer.phar') ?? '');
        if ($composer !== '') return $composer;
        echo ("\n[INFO] composer not found, attempting to download...\n");
        return self::download_composer($wdir);
    }

    static function download_composer(string $wdir): string
    {
        $wdir = escapeshellarg($wdir);
        exec("cd $wdir && curl -sS https://getcomposer.org/installer | php 2>&1", $output, $exit_code);
        if ($exit_code !== 0) throw new \Exception("Composer download failed: " . implode("\n", $output));
        if (!file_exists("$wdir/composer.phar")) throw new \Exception("404 composer.phar not found");
        return "$wdir/composer.phar";
    }

    static function command(string $wdir, string $command): bool
    {
        $composer = self::which_composer($wdir);
        $wdir = escapeshellarg($wdir);
        exec("cd $wdir && export COMPOSER_ALLOW_SUPERUSER=1 && export COMPOSER_NO_INTERACTION=1 && $composer $command 2>&1", $output, $exit_code);
        if ($exit_code !== 0) {
            // remove the first 3 lines and the last 4 lines
            array_splice($output, 0, 3);
            array_splice($output, -4);
            // trim each line
            $output = array_map('trim', $output);
            throw new \Exception(implode(" ", $output));
        }
        return self::check_autoload_exists($wdir);
    }

    static function check_autoload_exists(string $wdir): bool
    {
        return file_exists("$wdir/vendor/autoload.php");
    }
}

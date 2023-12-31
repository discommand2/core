<?php

namespace Discommand2\Core;

use Exception;
use Monolog\Level;
use Monolog\Handler\StreamHandler;

class LogFactory
{
    static function create($name): Log
    {
        $log = self::pushHandlers(self::createLogger($name), self::getConfigs());
        $log->debug("Log initialized!");
        return $log;
    }

    static function pushHandlers($log, $configs): Log
    {
        foreach ($configs as $config) {
            self::validateConfig($config);
            $path = self::validatePath($config['path']);
            $level = self::validateLevel($config['level']);
            $log->pushHandler(new StreamHandler($path, $level));
        }
        return $log;
    }

    static function getBasePath(): string
    {
        // expects to be installed under vendor/discommand2/core/src
        return __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..';
    }

    static function createLogger($name): Log
    {
        return new Log($name);
    }

    static function getConfigs(): array
    {
        return Config::get('monolog');
    }

    static function validateConfig(array $config): void
    {
        if (!isset($config['path'], $config['level'])) {
            throw new Exception("Logging path or level not defined in config/log.json!");
        }
    }

    static function validatePath($path)
    {
        if ($path === 'php://stdout' || $path === 'php://stderr') {
            return $path;
        }
        if (substr($path, 0, 1) != '/') {
            $path = self::getBasePath() . DIRECTORY_SEPARATOR . $path;
        }
        if (!is_dir(dirname($path))) {
            shell_exec("mkdir -p " . dirname($path));
        }
        return $path;
    }

    static function validateLevel($level)
    {
        return match ($level) {
            'DEBUG' => Level::Debug,
            'INFO' => Level::Info,
            'NOTICE' => Level::Notice,
            'WARNING' => Level::Warning,
            'ERROR' => Level::Error,
            'CRITICAL' => Level::Critical,
            'ALERT' => Level::Alert,
            'EMERGENCY' => Level::Emergency,
            default => throw new Exception("Invalid logging level ($level) set in config/logging.json!"),
        };
    }
}

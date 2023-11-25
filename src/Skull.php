<?php

namespace Discommand2\Core;

class Skull
{

    private ?Brain $brain = null;
    public function __construct(private Log $log, private string $myName)
    {
        $this->log->debug("$myName's skull initialized!");
    }

    public function run(array $argv): bool
    {
        $this->log->debug("skull run()", ["argv" => $argv]);
        switch ($argv[1] ?? '') {
            case 'start':
                return $this->start($argv);
            case 'update':
                return $this->update($argv);
            case 'upgrade':
                return $this->upgrade($argv);
            case 'install':
                return $this->install($argv);
            case 'remove':
                return $this->remove($argv);
            case 'config':
                return $this->config($argv);
            default:
                echo "Usage:\n";
                echo "brain start";
                echo "brain update [pluginName]\n";
                echo "brain upgrade [pluginName] [force]\n";
                echo "brain install [pluginName]\n";
                echo "brain remove [pluginName]\n";
                echo "brain config [pluginName] [setting] [key/value] [key/value]\n";
        }
        return true;
    }

    public function update($argv): bool
    {
        if (isset($argv[2]) && $argv[2] != '') {
            $plugin = ' discommand2/' . $argv[2];
            $this->log->info("Updating $plugin...");
        } else {
            $this->log->info("Updating everything...");
            $plugin = '';
        }
        return Composer::command('update' . $plugin);
    }

    public function upgrade($argv): bool
    {
        [$plugin, $force] = $this->validateUpgrade($argv);
        if (!$force) {
            $confirmation = readline("[WARNING] Are you sure you want to upgrade$plugin beyond the current stable version? Please type 'yes' exactly to confirm: ");
            if ($confirmation !== 'yes') {
                $this->log->error("Upgrade Aborted!");
                return false;
            }
        }
        $this->log->info("Upgrading$plugin...");
        return Composer::command('upgrade' . $plugin);
    }

    public function validateUpgrade($argv): array
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

    public function install($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Plugin name not specified!");
        $this->log->info("Installing " . $argv[2] . "...");
        // if the argument doesn't already include a / then prepend discommand2/
        if (strpos($argv[2], '/') === false) $argv[2] = 'discommand2/' . $argv[2];
        return Composer::command('require ' . ($argv[2]));
    }

    public function remove($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Plugin name not specified!");
        $this->log->info("Removing " . $argv[2] . "...");
        return Composer::command('remove discommand2/' . ($argv[2]));
    }

    public function start($argv): bool
    {
        $this->log->info("Starting {$this->myName}...");
        $this->brain = new Brain($this->log, $this->myName);
        $this->brain->think() or throw new \Exception("{$this->myName} failed to think!");
        $this->log->info("{$this->myName} started successfully!");
        return true;
    }

    public function config($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Plugin name not specified!");
        if (!$this->validatePluginName($argv[2])) throw new \Exception("Plugin not installed!");
        $pluginName = $argv[2];

        if (count($argv) < 5) throw new \Exception("Insufficient arguments!");

        $this->log->info("Configuring {$this->myName}...");

        $value = array_pop($argv);
        $keys = array_slice($argv, 3);

        $config = [];
        $temp = &$config;
        foreach ($keys as $key) {
            $temp[$key] = [];
            $temp = &$temp[$key];
        }

        $temp = $value;

        Config::set($pluginName, $config);

        $this->log->info($this->myName . " configured successfully!");
        return true;
    }

    public function validatePluginName($pluginName): bool
    {
        $composerLock = json_decode(file_get_contents(__DIR__ . '/../composer.lock'), true);
        foreach ($composerLock['packages'] as $package) {
            $lsl = strrpos($package['name'], '/');
            $name = substr($package['name'], $lsl + 1);
            if ($name === $pluginName) return true;
        }
        return false;
    }
}

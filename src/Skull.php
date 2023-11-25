<?php

namespace Discommand2\Core;

use Discommand2\Core\Validators\SkullValidators;

class Skull
{
    private array $brains = [];

    public function __construct(private Log $log, private string $rootDir, private string $myName)
    {
        $this->log->debug("skull initialized");
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
            case 'create':
                return $this->create($argv);
            case 'delete':
                return $this->delete($argv);
            case 'clean':
                return $this->clean($argv);
            default:
                return $this->help($argv);
        }
        return true;
    }

    public function create($argv): bool
    {
        [$brainName, $brainPath] = SkullValidators::validateCreate($argv);
        $url = SkullValidators::createFromTemplate($argv);
        Git::command($this->rootDir, "submodule add -b main -f $url $brainPath") or throw new \Exception("Failed to clone $url");
        Composer::command("install --working-dir=$brainPath") or throw new \Exception("Failed to install dependencies for $brainName");
        $this->log->info("$brainName created successfully");
        return true;
    }

    public function delete($argv): bool
    {
        [$brainName, $brainPath, $force] = SkullValidators::validateDelete($argv);

        if (!$force) {
            $confirmation = readline("[WARNING] Are you sure you want to delete " . $brainName . " including their home directory, sql database(s), message history, and settings? Please type 'yes' exactly to confirm: ");
            if ($confirmation !== 'yes') {
                $this->log->error("Delete Aborted");
                return false;
            }
        }

        $this->log->info("Deleting $brainName...");
        Git::command($this->rootDir, "submodule deinit -f $brainPath") or throw new \Exception("Failed to deinit $brainName");
        Git::command($this->rootDir, "rm -f $brainPath") or throw new \Exception("Failed to git remove $brainName");
        shell_exec("rm -rf {$this->rootDir}/.git/modules/$brainName 2>&1");
        Git::command($this->rootDir, "gc --aggressive --prune=now") or throw new \Exception("Failed to git gc");
        $this->log->info("$brainName deleted successfully");
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
        [$plugin, $force] = SkullValidators::validateUpgrade($argv);
        if (!$force) {
            $confirmation = readline("[WARNING] Are you sure you want to upgrade$plugin beyond the current stable version? Please type 'yes' exactly to confirm: ");
            if ($confirmation !== 'yes') {
                $this->log->error("Upgrade Aborted");
                return false;
            }
        }
        $this->log->info("Upgrading$plugin...");
        return Composer::command('upgrade' . $plugin);
    }

    public function install($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Plugin name not specified");
        $this->log->info("Installing " . $argv[2] . "...");
        // if the argument doesn't already include a / then prepend discommand2/
        if (strpos($argv[2], '/') === false) $argv[2] = 'discommand2/' . $argv[2];
        return Composer::command('require ' . ($argv[2]));
    }

    public function remove($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Plugin name not specified");
        $this->log->info("Removing " . $argv[2] . "...");
        return Composer::command('remove discommand2/' . ($argv[2]));
    }

    public function start($argv): bool
    {
        $this->brains[] = new Brain($this->log, $this->myName);
        foreach ($this->brains as $brain) $brain->think() or throw new \Exception("{$this->myName} failed to think");
        return true;
    }

    public function config($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Plugin name not specified");
        if (!SkullValidators::validatePluginName($this->rootDir, $argv[2])) throw new \Exception("Plugin not installed");
        $pluginName = $argv[2];
        if (count($argv) < 5) throw new \Exception("Insufficient arguments");
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
        $this->log->info("configured successfully");
        return true;
    }

    public function clean($argv): bool
    {
        $this->log->info("Cleaning...");
        $this->log->info("Cleaning composer cache...");
        Composer::command('clear-cache') or throw new \Exception("Failed to clean composer cache");
        $this->log->info("Cleaning git cache...");
        Git::command($this->rootDir, 'gc --aggressive --prune=now') or throw new \Exception("Failed to clean git cache");
        $this->log->info("Cleaning complete");
        return true;
    }

    public function help($argv): bool
    {
        $this->log->info("Usage: {$argv[0]} <command> [options]");
        return true;
    }
}

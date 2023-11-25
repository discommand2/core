<?php

namespace Discommand2\Core;

class Skull
{
    private ?Brain $brain = null;

    public function __construct(private Log $log, private string $myName)
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
            case 'help':
                return $this->help($argv);
            default:
                $this->log->error("Invalid command");
                return false;
        }
        return true;
    }

    public function create($argv): bool
    {
        [$brainName, $brainPath] = $this->validateCreate($argv);
        $url = $this->createFromTemplate($argv);
        Git::command("submodule add -b main -f $url $brainPath") or throw new \Exception("Failed to clone $url");
        Composer::command("install --working-dir=$brainPath") or throw new \Exception("Failed to install dependencies for $brainName");
        $this->log->info("$brainName created successfully");
        return true;
    }

    public function delete($argv): bool
    {
        [$brainName, $brainPath, $force] = $this->validateDelete($argv);

        if (!$force) {
            $confirmation = readline("[WARNING] Are you sure you want to delete " . $brainName . " including their home directory, sql database(s), message history, and settings? Please type 'yes' exactly to confirm: ");
            if ($confirmation !== 'yes') {
                $this->log->error("Delete Aborted");
                return false;
            }
        }

        $this->log->info("Deleting $brainName...");
        Git::command("submodule deinit -f $brainPath") or throw new \Exception("Failed to deinit $brainName");
        Git::command("rm -f $brainPath") or throw new \Exception("Failed to git remove $brainName");
        Git::command("gc --aggressive --prune=now") or throw new \Exception("Failed to git gc");
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
        [$plugin, $force] = $this->validateUpgrade($argv);
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
        $this->log->info("Starting {$this->myName}...");
        $this->brain = new Brain($this->log, $this->myName);
        $this->brain->think() or throw new \Exception("{$this->myName} failed to think");
        $this->log->info("{$this->myName} started successfully");
        return true;
    }

    public function config($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Plugin name not specified");
        if (!$this->validatePluginName($argv[2])) throw new \Exception("Plugin not installed");
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

    // Additional methods from Discommand2 class
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

    public function validateCreate($argv): array
    {
        if (!isset($argv[2])) throw new \Exception("Brain name not specified!");
        if (!$this->validateBrainName($argv[2])) throw new \Exception("Invalid brain name!");
        $brainName = $argv[2];
        $basePath = Config::get('discommand2', 'brains');
        $brainPath = $basePath . '/' . $brainName;
        if (file_exists($brainPath)) throw new \Exception("$brainName already exists! use config, start, or delete instead.");
        return [$brainName, $brainPath];
    }

    public function createFromTemplate($argv): string
    {
        if (!isset($argv[3]) || $argv[3] === '') $argv[3] = "brain-template";
        if (strpos($argv[3], '/') === false) $argv[3] = 'discommand2/' . $argv[3];
        if (strpos($argv[3], 'https://') === 0) $url = $argv[3];
        else if (strpos($argv[3], 'git@github:') === 0) $url = $argv[3];
        else if (strpos($argv[3], 'bitbucket.org:') === 0) $url = $argv[3];
        else $url = 'git@github.com:' . $argv[3] . '.git';
        $this->log->info("Creating {$argv[2]} from template " . $url);
        return $url;
    }

    public function validateDelete($argv): array
    {
        if (!isset($argv[2])) throw new \Exception("Brain name not specified!");
        if (!$this->validateBrainName($argv[2])) throw new \Exception("Invalid brain name!");
        $brainName = $argv[2];
        $brainPath = $this->getPath($brainName);
        if (!file_exists($brainPath)) throw new \Exception("$brainName doesn't exist to begin with!");
        $force = isset($argv[3]) && $argv[3] === 'force';
        return [$brainName, $brainPath, $force];
    }

    public function getPath($brainName): string
    {
        $basePath = Config::get('discommand2', 'brains');
        return $basePath . '/' . $brainName;
    }

    public function validateBrainName($name): bool
    {
        // must be a valid linux username/foldername (no spaces, no special characters except _ and -)
        return preg_match('/^[a-z0-9_-]+$/i', $name);
    }

    // Additional methods from Skull class
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

    public function help($argv)
    {
        $this->log->info("Usage: {$argv[0]} <command> [options]");
        $this->log->info("Commands:");
        $this->log->info("  start\t\t\tStarts the brain(s)");
        $this->log->info("  update [plugin]\tUpdates the brain or a plugin");
        $this->log->info("  upgrade [plugin]\tUpgrades the brain or a plugin");
        $this->log->info("  install <plugin>\tInstalls a plugin");
        $this->log->info("  remove <plugin>\tRemoves a plugin");
        $this->log->info("  config <plugin> <key> <value>\tSets a config value for a plugin");
        $this->log->info("  create <brain> [template]\tCreates a brain from a template");
        $this->log->info("  delete <brain>\tDeletes a brain");
        $this->log->info("  help\t\t\tShows this help message");
        return true;
    }
}

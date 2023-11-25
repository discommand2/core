<?php

namespace Discommand2\Core;

class Discommand2
{
    public function __construct(private Log $log)
    {
        $this->log->debug("Discommand2 initialized!");
    }

    public function run(array $argv): bool
    {
        $this->log->debug("Discommand2 run()", ["argv" => $argv]);
        switch ($argv[1] ?? '') {
            case 'start':
                return $this->start($argv);
            case 'update':
                return $this->update($argv);
            case 'upgrade':
                return $this->upgrade($argv);
            case 'create':
                return $this->create($argv);
            case 'config':
                return $this->config($argv);
            case 'delete':
                return $this->delete($argv);
            default:
                echo "Usage: discommand2 [start|update|upgrade|create|config|delete] [brain] [force]\n";
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

    public function create($argv): bool
    {
        [$brainName, $brainPath] = $this->validateCreate($argv);
        $url = $this->createFromTemplate($argv);
        Git::command("submodule add -b main -f $url $brainPath") or throw new \Exception("Failed to clone $url");
        Composer::command("install --working-dir=$brainPath") or throw new \Exception("Failed to install dependencies for $brainName");
        $this->log->info("$brainName created successfully!");
        return true;
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

    public function start($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Brain name not specified!");
        if (!$this->validateBrainName($argv[2])) throw new \Exception("Invalid brain name!");
        $brainName = $argv[2];
        $cmd = $this->getPath($brainName) . '/brain';
        if (!file_exists($cmd)) throw new \Exception("Could not find $brainName's brain!");
        $cmd .= ' start';
        $this->log->info("Waking $brainName...");
        $this->log->debug("passthru", ["cmd" => $cmd]);
        passthru($cmd, $exitCode);
        $this->log->info("$brainName stopped with exit code $exitCode!");
        return $exitCode === 0;
    }

    public function config($argv): bool
    {
        if (!isset($argv[2]) || $argv[2] === '') throw new \Exception("Brain name not specified!");
        if (!$this->validateBrainName($argv[2])) throw new \Exception("Invalid brain name!");
        $brainName = $argv[2];
        $brainPath = $this->getPath($brainName);
        if (!file_exists($brainPath)) throw new \Exception("$brainName doesn't exist to begin with!");
        $this->log->info("Configuring $brainName...");
        $cmd = $brainPath . '/brain config';
        $this->log->debug("exec", ["cmd" => $cmd]);
        exec($cmd, $output, $exitCode);
        $output = array_map('trim', $output);
        if ($exitCode !== 0) throw new \Exception("Config command failed: " . implode(" ", $output));
        $this->log->info("$brainName configured successfully!");
        return $exitCode === 0;
    }

    public function delete($argv): bool
    {
        [$brainName, $brainPath, $force] = $this->validateDelete($argv);

        if (!$force) {
            $confirmation = readline("[WARNING] Are you sure you want to delete " . $brainName . " including their home directory, sql database(s), message history, and settings? Please type 'yes' exactly to confirm: ");
            if ($confirmation !== 'yes') {
                $this->log->error("Delete Aborted!");
                return false;
            }
        }

        $this->log->info("Deleting $brainName...");
        Git::command("submodule deinit -f $brainPath") or throw new \Exception("Failed to deinit $brainName");
        Git::command("rm -f $brainPath") or throw new \Exception("Failed to git remove $brainName");
        Git::command("gc --aggressive --prune=now") or throw new \Exception("Failed to git gc");
        $this->log->info("$brainName deleted successfully!");
        return true;
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
}

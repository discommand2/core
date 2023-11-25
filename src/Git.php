<?php

namespace Discommand2\Core;

class Git
{
    static function which_git(): string
    {
        $git = trim(shell_exec('which git') ?? '');
        if ($git === '') throw new \Exception("Git not found, Please use your package manager to install git!");
        return $git;
    }

    static function command(string $command): bool
    {
        $git = self::which_git();
        exec("$git $command 2>&1", $output, $exit_code);
        $output = array_map('trim', $output);
        if ($exit_code !== 0) throw new \Exception("Git command failed: " . implode(" ", $output));
        return true;
    }
}

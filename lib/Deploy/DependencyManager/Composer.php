<?php
namespace Deploy\DependencyManager;

class Composer {
    private $config;

    public function __construct($config) {
        $this->config = $config;
        $composer = exec('which composer');

        if (empty($composer)) {
            throw new \Exception("Please install composer");
        }
    }

    public function run() {
        $color = new \Colors\Color();
        echo $color("Install Dependencies via composer")->white->bold->bg_yellow . "\n";

        if (!file_exists($this->config->install->dir . '/current/composer.json')) {
            throw new \Exception("Unable to find composer.json in: " . $this->config->install->dir . "/current");
        }

        $cmd = sprintf('bin/composer.sh %s/current', $this->config->install->dir);
        echo "Running command: " . $cmd . "\n";
        exec(escapeshellcmd($cmd), $output);

        foreach ($output as $line) {
            echo $line . "\n";
        }
    }
}
?>

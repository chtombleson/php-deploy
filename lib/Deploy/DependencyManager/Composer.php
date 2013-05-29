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

        if (!file_exists($this->config->install->dir . '/vendor')) {
            if (!mkdir($this->config->install->dir . '/vendor', 0755)) {
                throw new \Exception("Unable to create vendors directory");
            }
        }

        if (!symlink($this->config->install->dir . '/vendor', $this->config->install->dir . '/current/vendor')) {
            throw new \Exception("Unable to symlink vendors directory to current");
        }

        if (file_exists($this->config->install->dir . '/current/composer.lock')) {
            echo "Running command: composer update\n";
            $cmd = new \Deploy\Command($this->config->install->dir . '/current/');
            $cmd->run('composer update');
        } else {
            echo "Running command: composer install\n";
            $cmd = new \Deploy\Command($this->config->install->dir . '/current/');
            $cmd->run('composer install');
        }
    }
}
?>

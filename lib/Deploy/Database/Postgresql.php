<?php
namespace Deploy\Database;

class Postgresql {
    private $config;

    public function __construct($config) {
        $this->config = $config;
        $psql = exec('which psql');

        if (empty($psql)) {
            throw new \Exception("Please install PostgreSQL");
        }

        if (!isset($this->config->database->name)) {
            throw new \Exception("Database requires a name");
        }

        if (!isset($this->config->database->username)) {
            throw new \Exception("Database requires a username");
        }

        if (!isset($this->config->database->password)) {
            throw new \Exception("Database requires a password");
        }
    }

    public function run() {
        $color = new \Colors\Color();
        echo $color("Setting up PostgreSQL Database")->white->bold->bg_yellow . "\n";
        $cmd = sprintf('bin/postgresql.sh %s %s %s', $this->config->database->username, $this->config->database->password, $this->config->database->name);
        echo "Running command: " . $cmd . "\n";
        exec(escapeshellcmd($cmd), $output);

        foreach ($output as $line) {
            echo $line . "\n";
        }
    }
}
?>

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

        // Create User
        $command = 'psql postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname=\'%s\'" | grep -q 1 || psql template1 -c "CREATE USER %s WITH PASSWORD \'%s\'"';
        $command = sprintf($command, $this->config->database->username, $this->config->database->username, $this->config->database->password);

        echo "Running command: " . $command . "\n";
        $cmd = new \Deploy\Command();
        $cmd->run($command);

        // Create Database
        $command = 'psql template1 -t -c "SELECT 1 FROM pg_catalog.pg_database WHERE datname = \'%s\'" | grep -q 1 || psql template1 -t -c "CREATE DATABASE %s WITH OWNER %s"';
        $command = sprintf($command, $this->config->database->name, $this->config->database->name, $this->config->database->username);

        echo "Running command: " . $command . "\n";
        $cmd->run($command);

        // Grant Privilleges
        $command = 'psql template1 -c "GRANT ALL PRIVILEGES ON DATABASE %s to %s"';
        $command = sprintf($command, $this->config->database->name, $this->config->database->username);

        echo "Running command: " . $command . "\n";
        $cmd->run($command);
    }
}
?>

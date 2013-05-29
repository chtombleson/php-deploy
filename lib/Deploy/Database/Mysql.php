<?php
namespace Deploy\Database;

class Mysql {
    private $config;

    public function __construct($config) {
        $this->config = $config;
        $mysql = exec('which mysql');

        if (empty($mysql)) {
            throw new \Exception("Please install MySQL");
        }

        if (!isset($this->config['database']['name'])) {
            throw new \Exception("Database requires a name");
        }

        if (!isset($this->config['database']['username'])) {
            throw new \Exception("Database requires a username");
        }

        if (!isset($this->config['database']['password'])) {
            throw new \Exception("Database requires a password");
        }
    }

    public function run() {
        $color = new \Colors\Color();
        echo $color("Setting up MySQL Database")->white->bold->bg_yellow . "\n";

        $cli = new \FusePump\Cli\Inputs();
        $mysqluser = $cli->prompt('MySQL user: ');
        $mysqlpass = $cli->prompt('MySQL password: ');

        // Create User
        $command = 'mysql -u %s --password=%s -e "SELECT 1 FROM mysql.user WHERE User=\'%s\'" | grep -q 1 || mysql -u %s --password=%s -e "CREATE USER %s IDENTIFIED BY \'%s\'';
        $command = sprintf($command, $mysqluser, $mysqlpass, $this->config['database']['username'], $mysqluser, $mysqlpass, $this->config['database']['username'], $this->config['database']['password']);

        echo "Running command: " . $command . "\n";
        $cmd = new \Deploy\Command();
        $cmd->run($command);

        //Create Database
        $command = 'mysql -u %s --password=%s -e "SHOW DATABASES LIKE \'%s\'" | grep -q %s || mysql -u %s --password=%s -e "CREATE DATABASE %s"';
        $command = sprintf($command, $mysqluser, $mysqlpass, $this->config['database']['name'], $this->config['database']['name'], $mysqluser, $mysqlpass, $this->config['database']['name']);

        echo "Running command: " . $command . "\n";
        $cmd->run($command);

        // Grant Privilliges
        $command = 'mysql -u %s --password=%s -e "GRANT ALL ON %s.* TO %s IDENTIFIED BY %s"';
        $command = sprintf($command, $mysqluser, $mysqlpass, $this->config['database']['username'], $this->config['database']['name'], $this->config['database']['password']);

        echo "Running command: " . $command . "\n";
        $cmd->run($command);
    }
}
?>

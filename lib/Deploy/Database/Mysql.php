<?php
/*
* The MIT License (MIT)
*
* Copyright (c) 2013 Christopher Tombleson <chris@cribznetwork.com>
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*/
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

    public function rollback() {
        $color = new \Colors\Color();
        echo $color("Rolling back MySQL Database")->white->bold->bg_yellow . "\n";

        $cli = new \FusePump\Cli\Inputs();
        $mysqluser = $cli->prompt('MySQL user: ');
        $mysqlpass = $cli->prompt('MySQL password: ');

        // Remove Database
        $command = 'mysql -u %s --password=%s -e "DROP DATABASE %s"';
        $command = sprintf($command, $mysqluser, $mysqlpass, $this->config['database']['name']);

        echo "Running command: " . $command . "\n";
        $cmd = new \Deploy\Command();
        $cmd->run($command);

        // Remove User
        $command = 'mysql -u %s --password=%s -e "DROP USER %s"';
        $command = sprintf($command, $mysqluser, $mysqlpass, $this->config['database']['username']);

        echo "Running command: " . $command . "\n";
        $cmd->run($command);
    }
}
?>

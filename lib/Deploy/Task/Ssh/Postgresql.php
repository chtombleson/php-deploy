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
namespace Deploy\Task\Ssh;

class Postgresql {
    private $config;

    public function __construct($config) {
        $this->config = $config;
        $psql = exec('which psql');

        if (empty($psql)) {
            throw new \Exception("Please install PostgreSQL");
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
        echo $color("Setting up PostgreSQL Database")->white->bold->bg_yellow . "\n";

        // Create User
        $command = 'psql postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname=\'%s\'" | grep -q 1 || psql template1 -c "CREATE USER %s WITH PASSWORD \'%s\'"';
        $command = sprintf($command, $this->config['database']['username'], $this->config['database']['username'], $this->config['database']['password']);

        echo "Running command: " . $command . "\n";
        $cmd = new \Deploy\Command();
        $cmd->run($command);

        // Create Database
        $command = 'psql template1 -t -c "SELECT 1 FROM pg_catalog.pg_database WHERE datname = \'%s\'" | grep -q 1 || psql template1 -t -c "CREATE DATABASE %s WITH OWNER %s"';
        $command = sprintf($command, $this->config['database']['name'], $this->config['database']['name'], $this->config['database']['username']);

        echo "Running command: " . $command . "\n";
        $cmd->run($command);

        // Grant Privilleges
        $command = 'psql template1 -c "GRANT ALL PRIVILEGES ON DATABASE %s to %s"';
        $command = sprintf($command, $this->config['database']['name'], $this->config['database']['username']);

        echo "Running command: " . $command . "\n";
        $cmd->run($command);
    }

    public function rollback() {
        $color = new \Colors\Color();
        echo $color("Rolling back PostgreSQL Databse")->white->bold->bg_yellow . "\n";

        // Remove database
        $command = 'psql template1 -t -c "DROP DATABASE %s"';
        $command = sprintf($command, $this->config['database']['name']);

        echo "Running command: " . $command . "\n";
        $cmd = new \Deploy\Command();
        $cmd->run($command);

        // Remove User
        $command = 'psql template1 -c "DROP USER %s"';
        $command = sprintf($command, $this->config['database']['username']);

        echo "Running command: " . $command . "\n";
        $cmd->run($command);
    }
}
?>

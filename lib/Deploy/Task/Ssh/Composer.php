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

        if (!file_exists($this->config['install']['dir'] . '/current/composer.json')) {
            throw new \Exception("Unable to find composer.json in: " . $this->config['install']['dir'] . "/current");
        }

        if (!file_exists($this->config['install']['dir'] . '/vendor')) {
            if (!mkdir($this->config['install']['dir'] . '/vendor', 0755)) {
                throw new \Exception("Unable to create vendors directory");
            }
        }

        //if (!symlink($this->config['install']['dir'] . '/vendor', $this->config['install']['dir'] . '/current/vendor')) {
        //    throw new \Exception("Unable to symlink vendors directory to current");
        //}

        $cmd = new \Deploy\Command();
        $cmd->run('ln -s ' . $this->config['install']['dir'] . '/vendor ' . $this->config['install']['dir'] . '/current/vendor');

        if (!file_exists($this->config['install']['dir'] . '/current/vendor')) {
            throw new \Exception("Unable to symlink vendors directory to current");
        }

        if (file_exists($this->config['install']['dir'] . '/current/composer.lock')) {
            echo "Running command: composer update\n";
            $cmd = new \Deploy\Command($this->config['install']['dir'] . '/current/');
            $cmd->run('composer update');
        } else {
            echo "Running command: composer install\n";
            $cmd = new \Deploy\Command($this->config['install']['dir'] . '/current/');
            $cmd->run('composer install');
        }

        if (isset($this->config['hooks']['after_composer'])) {
            if (is_array($this->config['hooks']['after_composer'])) {
                echo $color("Executing Hooks, after_composer")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_composer'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                echo $color("Executing Hooks, after_composer: " . $this->config['hooks']['after_composer'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();
                $cmd->run($this->config['hooks']['after_composer']);
            }
        }
    }

    public function rollback() {
        $color = new \Colors\Color();
        echo $color("Rolling back Composer")->white->bold->bg_yellow . "\n";

        echo "Removing vendor directory\n";
        $cmd = new \Deploy\Command();
        $cmd->run('rm -r ' . $this->config['install']['dir'] . '/vendor/');
        $cmd->run('rm ' . $this->config['install']['dir'] . '/current/vendor');

        echo "Creating new vendor directory\n";
        $cmd->run('mkdir ' . $this->config['install']['dir'] . '/vendor/');
        $cmd->run('ln -s ' . $this->config['install']['dir'] . '/vendor/ ' . $this->config['install']['dir'] . '/current/vendor');

        echo "Run composer install\n";
        $cmd->run('composer install', $this->config['install']['dir'] . '/current/');

        if (isset($this->config['hooks']['after_composer_rollback'])) {
            if (is_array($this->config['hooks']['after_composer_rollback'])) {
                echo $color("Executing Hooks, after_composer_rollback")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_composer_rollback'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                echo $color("Executing Hooks, after_composer_rollback: " . $this->config['hooks']['after_composer_rollback'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();
                $cmd->run($this->config['hooks']['after_composer_rollback']);
            }
        }
    }
}
?>

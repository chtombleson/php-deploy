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
namespace Deploy\Task\Local;

class Svn {
    protected $site;
    protected $env;
    protected $branch;
    private $config;
    private $time;

    public function __construct($config, $site, $env) {
        $this->config = $config;
        $this->site = $site;
        $this->env = $env;
        $svn = exec('which svn');

        if (empty($svn)) {
            throw new \Exception("Please install Subversion (SVN)");
        }

        if (!isset($this->config['version_control']['url'])) {
            throw new \Exception("No URL for the svn repo was set in the config file");
        }
    }

    public function run() {
        if (!file_exists('/tmp/deployments/' . $this->site . '-' . $this->env)) {
            $this->cloneRepo();
        } else {
            $this->updateRepo();
        }

        $this->getArchive();
        $this->untar();
        $this->setCurrent();

        if (isset($this->config['hooks']['after_svn'])) {
            if (is_array($this->config['hooks']['after_svn'])) {
                $color = new \Colors\Color();
                echo $color("Executing Hook, after_svn")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_svn'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                $color = new \Colors\Color();
                echo $color("Executing Hook, after_svn: " . $this->config['hooks']['after_svn'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();
                $cmd->run($this->config['hooks']['after_svn']);
            }
        }
    }

    public function rollback() {
        $color = new \Colors\Color();
        echo $color("Rolling back release")->white->bold->bg_yellow . "\n";

        $currelease = readlink($this->config['install']['dir'] . '/current');
        $releases = scandir($this->config['install']['dir'] . '/releases/');
        unset($releases[0]);
        unset($releases[1]);

        sort($releases, SORT_NUMERIC);

        if (count($releases) < 2) {
            throw new \Exception("No other release to rollback to");
        }

        $newrelease = $this->config['install']['dir'] . '/releases/' . $releases[(count($releases) - 1) - 1];
        echo "Rollback to " . $newrelease . "\n";

        $cmd = new \Deploy\Command();
        echo "Rolling back to previous release\n";
        $cmd->run('rm ' . $this->config['install']['dir'] . '/current');
        $cmd->run('ln -s ' . $newrelease . ' ' . $this->config['install']['dir'] . '/current');

        echo "Removing rollbacked release\n";
        $cmd->run('rm -r ' . $currelease);

        if (isset($this->config['hooks']['after_svn_rollback'])) {
            if (is_array($this->config['hooks']['after_svn_rollback'])) {
                $color = new \Colors\Color();
                echo $color("Executing Hook, after_svn_rollback")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_svn_rollback'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                $color = new \Colors\Color();
                echo $color("Executing Hook, after_svn_rollback: " . $this->config['hooks']['after_svn_rollback'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();
                $cmd->run($this->config['hooks']['after_svn_rollback']);
            }
        }
    }

    private function cloneRepo() {
        if (!file_exists('/tmp/deployments/')) {
            if (!mkdir('/tmp/deployments/', 0755)) {
                throw new \Exception("Unabled to create directory: /tmp/deployments/");
            }
        }

        $color = new \Colors\Color();
        echo $color("Cloning SVN Repo: " . $this->config['version_control']['url'])->white->bold->bg_yellow . "\n";

        $cmd = new \Deploy\Command('/tmp/deployments/');
        $cmd->run(sprintf('svn checkout %s %s', $this->config['version_control']['url'], $this->site . '-' . $this->env));
    }

    private function updateRepo() {
        $color = new \Colors\Color();
        echo $color("Updating SVN Repo: " . $this->config['version_control']['url'])->white->bold->bg_yellow . "\n";

        $cmd = new \Deploy\Command('/tmp/deployments/' . $this->site . '-' . $this->env);
        $cmd->run('svn update');
    }

    private function getArchive() {
        $color = new \Colors\Color();
        echo $color("Creating Git Archive")->white->bold->bg_yellow . "\n";
        $this->time = time();
        $cmd = 'tar zcvf --exclude=".svn" --transform "s/^\./%s/" %s/releases/%s.tar.gz .';
        $cmd = sprintf($cmd, $this->time, $this->config['install']['dir'], $this->site);
        echo "Running command: " . $cmd . "\n";
        $command = new \Deploy\Command('/tmp/deployments/' . $this->site . '-' . $this->env . '/');
        $command->run($cmd);
    }

    private function untar() {
        $color = new \Colors\Color();
        echo $color("Extracting Git Archive")->white->bold->bg_yellow . "\n";
        echo "Running command: tar -xvf " . $this->site . ".tar.gz\n";
        $cmd = new \Deploy\Command($this->config['install']['dir'] . '/releases/');
        $cmd->run('tar -xvf ' . $this->site . '.tar.gz');
        $cmd->run('rm ' . $this->site . '.tar.gz');
    }

    private function setCurrent() {
        $color = new \Colors\Color();
        echo $color("Symlinking release to current")->white->bold->bg_yellow . "\n";

        if (file_exists($this->config['install']['dir'] . '/current')) {
            unlink($this->config['install']['dir'] . '/current');
        }

        if (!symlink($this->config['install']['dir'] . '/releases/' . $this->time, $this->config['install']['dir'] . '/current')) {
            throw new \Exception("Unable to symlink release to current");
        }
    }
}
?>

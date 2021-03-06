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
namespace Deploy\VersionControl;

class Git {
    protected $site;
    protected $env;
    protected $branch;
    private $config;
    private $time;

    public function __construct($config, $site, $env) {
        $this->config = $config;
        $this->site = $site;
        $this->env = $env;
        $git = exec('which git');

        if (empty($git)) {
            throw new \Exception("Please install Git");
        }

        if (!isset($this->config['version_control']['url'])) {
            throw new \Exception("No URL for the git repo was set in the config file");
        }

        if (!isset($this->config['version_control']['branch'])) {
            $this->branch = 'master';
        } else {
            $this->branch = $this->config['version_control']['branch'];
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

        if (isset($this->config['hooks']['after_git'])) {
            if (is_array($this->config['hooks']['after_git'])) {
                $color = new \Colors\Color();
                echo $color("Executing Hook, after_git")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_git'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                $color = new \Colors\Color();
                echo $color("Executing Hook, after_git: " . $this->config['hooks']['after_git'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();
                $cmd->run($this->config['hooks']['after_git']);
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

        if (isset($this->config['hooks']['after_git_rollback'])) {
            if (is_array($this->config['hooks']['after_git_rollback'])) {
                $color = new \Colors\Color();
                echo $color("Executing Hook, after_git_rollback")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_git_rollback'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                $color = new \Colors\Color();
                echo $color("Executing Hook, after_git_rollback: " . $this->config['hooks']['after_git_rollback'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();
                $cmd->run($this->config['hooks']['after_git_rollback']);
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
        echo $color("Cloning Git Repo: " . $this->config['version_control']['url'])->white->bold->bg_yellow . "\n";

        $gitwrap = new\GitWrapper\GitWrapper();
        $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
        $git->clone($this->config['version_control']['url']);
        echo $git->getOutput();
        $this->switchBranch();
    }

    private function switchBranch() {
        if ($this->getBranch() != $this->branch && !$this->branchExists()) {
            $color = new \Colors\Color();
            $gitwrap = new \GitWrapper\GitWrapper();
            echo $color("Checkout branch: " . $this->branch . ", " . $this->config['version_control']['url'])->white->bold->bg_yellow . "\n";
            $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
            $git->checkout('origin/' . $this->branch, array('b' => $this->branch, 'track' => true));
            echo $git->getOutput();
        }

        if ($this->branchExists() && $this->getBranch() != $this->branch) {
            $color = new \Colors\Color();
            $gitwrap = new \GitWrapper\GitWrapper();
            echo $color("Checkout branch: " . $this->branch . ", " . $this->config['version_control']['url'])->white->bold->bg_yellow . "\n";
            $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
            $git->checkout($this->branch);
            echo $git->getOutput();
        }
    }

    private function updateRepo() {
        $this->switchBranch();
        $color = new \Colors\Color();
        $gitwrap = new \GitWrapper\GitWrapper();
        echo $color("Updating Git Repo: " . $this->config['version_control']['url'])->white->bold->bg_yellow . "\n";
        $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
        $this->switchBranch();
        $git->pull();
        echo $git->getOutput();
    }

    private function getBranch() {
        $gitwrap = new \GitWrapper\GitWrapper();
        $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
        $git->branch();
        $output = $git->getOutput();

        preg_match('#\*\s([a-z A-Z 0-9 \S]+)#', $output, $matches);
        return trim($matches[1]);
    }

    private function branchExists() {
        $gitwrap = new \GitWrapper\GitWrapper();
        $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
        $git->branch();
        $output = $git->getOutput();

        if (preg_match('#' . preg_quote($this->branch) . '#', $output)) {
            return true;
        }

        return false;
    }

    private function getArchive() {
        $color = new \Colors\Color();
        echo $color("Creating Git Archive")->white->bold->bg_yellow . "\n";
        $this->time = time();
        //$cmd = 'git archive --format tar --prefix %d/ %s --output %s/releases/%s.tar.gz';
        $cmd = 'git archive --format tar --prefix %d/ %s | gzip > %s/releases/%s.tar.gz';
        $cmd = sprintf($cmd, $this->time, $this->branch, $this->config['install']['dir'], $this->site);
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

<?php
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

        if (!isset($this->config->version_control->url)) {
            throw new \Exception("No URL for the git repo was set in the config file");
        }

        if (!isset($this->config->version_control->branch)) {
            $this->branch = 'master';
        } else {
            $this->branch = $this->config->version_control->branch;
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

        if (isset($this->config->hooks->after_git_cmd)) {
            $color = new \Colors\Color();
            echo $color("Executing Hook, after_git_cmd: " . $this->config->hooks->after_git_cmd)->white->bold->bg_yellow . "\n";
            exec(escapeshellcmd($this->config->hooks->after_git_cmd), $output);

            foreach ($output as $line) {
                echo $line . "\n";
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
        echo $color("Cloning Git Repo: " . $this->config->version_control->url)->white->bold->bg_yellow . "\n";

        $gitwrap = new\GitWrapper\GitWrapper();
        $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
        $git->clone($this->config->version_control->url);
        echo $git->getOutput();
        $this->switchBranch();
    }

    private function switchBranch() {
        if ($this->getBranch() != $this->branch && !$this->branchExists()) {
            $color = new \Colors\Color();
            $gitwrap = new \GitWrapper\GitWrapper();
            echo $color("Checkout branch: " . $this->branch . ", " . $this->config->version_control->url)->white->bold->bg_yellow . "\n";
            $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
            $git->checkout('origin/' . $this->branch, array('b' => $this->branch, 'track' => true));
            echo $git->getOutput();
        }

        if ($this->branchExists() && $this->getBranch() != $this->branch) {
            $color = new \Colors\Color();
            $gitwrap = new \GitWrapper\GitWrapper();
            echo $color("Checkout branch: " . $this->branch . ", " . $this->config->version_control->url)->white->bold->bg_yellow . "\n";
            $git = $gitwrap->workingCopy('/tmp/deployments/' . $this->site . '-' . $this->env);
            $git->checkout($this->branch);
            echo $git->getOutput();
        }
    }

    private function updateRepo() {
        $this->switchBranch();
        $color = new \Colors\Color();
        $gitwrap = new \GitWrapper\GitWrapper();
        echo $color("Updating Git Repo: " . $this->config->version_control->url)->white->bold->bg_yellow . "\n";
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
        $command = 'bin/git-archive.sh /tmp/deployments/%s-%s %d %s/releases/%s.tar %s';
        $cmd = sprintf($command, $this->site, $this->env, $this->time, $this->config->install->dir, $this->site, $this->branch);
        echo "Running command: " . $cmd . "\n";
        exec(escapeshellcmd($cmd)) . "\n";
    }

    private function untar() {
        $color = new \Colors\Color();
        echo $color("Extracting Git Archive")->white->bold->bg_yellow . "\n";
        $command = 'bin/untar.sh %s/releases %s.tar';
        $cmd = sprintf($command, $this->config->install->dir, $this->site);
        echo "Running command: " . $cmd . "\n";
        exec(escapeshellcmd($cmd), $output);

        foreach ($output as $line) {
            echo $line . "\n";
        }
    }

    private function setCurrent() {
        $color = new \Colors\Color();
        echo $color("Symlinking release to current")->white->bold->bg_yellow . "\n";

        if (file_exists($this->config->install->dir . '/current')) {
            unlink($this->config->install->dir . '/current');
        }

        if (!symlink($this->config->install->dir . '/releases/' . $this->time, $this->config->install->dir . '/current')) {
            throw new \Exception("Unable to symlink release to current");
        }
    }
}
?>

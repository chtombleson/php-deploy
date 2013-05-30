<?php
namespace Deploy;

class Rollback {
    protected $site;
    protected $env;
    protected $config;
    protected $setup;

    public function __construct($site, $env, $config = null, $setup = false) {
        $conf = new Config($site, $env, $config);
        $this->config = $conf->get();
        $this->site = $site;
        $this->env = $env;
        $this->setup = $setup;
    }

    public function run() {
        $color = new \Colors\Color();
        $cli = new \FusePump\Cli\Inputs();

        echo $color('Rolling back deployment of site: ' . $this->site . ', environment: ' . $this->env)->white->bold->bg_green . "\n";
        if ($cli->confirm('Are you sure you want to rollback this site [y/n]? ')) {
            if (empty($this->config['version_control']['type'])) {
                throw new \Exception("A version control type was not set in the config file");
            }

            if (empty($this->config['install']['dir'])) {
                throw new \Exception("A install dir was not set in the config file");
            }

            if (empty($this->config['webserver']['type'])) {
                throw new \Exception("A webserver type was not set in the config file");
            }

            if (!is_writeable(dirname($this->config['install']['dir']))) {
                throw new \Exception(dirname($this->config['install']['dir']) . " is not writeable");
            }

            if ($this->setup) {
                echo $color("Removing install directory: " . $this->config['install']['dir'])->white->bold->bg_yellow . "\n";
                $this->removeInstallDir();
            }

            if (!$this->setup) {
                $vcs_class = 'Deploy\VersionControl\\' . ucfirst($this->config['version_control']['type']);

                if (!file_exists('lib/' . str_replace('\\', '/', $vcs_class) . '.php')) {
                    throw new \Exception("Class: " . $vcs_class . " does not exist");
                }

                $vcs = new $vcs_class($this->config, $this->site, $this->env);
                $vcs->rollback();
            }

            if (isset($this->config['dependency_manager']) && !$this->setup) {
                if (empty($this->config['dependency_manager']['type'])) {
                    throw new \Exception("A dependy manager type is required");
                }

                $dep_class = 'Deploy\DependencyManager\\' . ucfirst($this->config['dependency_manager']['type']);

                if (!file_exists('lib/' . str_replace('\\', '/', $dep_class) . '.php')) {
                    throw new \Exception("Class: " . $dep_class . " does not exist");
                }

                $dep = new $dep_class($this->config);
                $dep->rollback();
            }

            if (isset($this->config['database']) && $this->setup) {
                if (empty($this->config['database']['type'])) {
                    throw new \Exception("Database type required");
                }

                $db_class = 'Deploy\Database\\' . ucfirst($this->config['database']['type']);

                if (!file_exists('lib/' . str_replace('\\', '/', $db_class) . '.php')) {
                    throw new \Exception("Class: " . $db_class . " does not exist");
                }

                $db = new $db_class($this->config);
                $db->rollback();
            }

            if ($this->setup) {
                $web_class = 'Deploy\WebServer\\' . ucfirst($this->config['webserver']['type']);

                if (!file_exists('lib/' . str_replace('\\', '/', $web_class) . '.php')) {
                    throw new \Exception("Class: " . $web_class . " does not exist");
                }

                $web = new $web_class($this->config, $this->site, $this->env);
                $web->rollback();
            }

            switch (strtolower($this->config['webserver']['type'])) {
                case 'nginx':
                    echo $color("Reloading Nginx: nginx -s reload")->white->bold->bg_yellow . "\n";
                    $cmd = new \Deploy\Command();
                    $cmd->run('nginx -s reload');
                    break;

                case 'apache':
                    echo $color("Reloading Apache: apache2ctl graceful")->white->bold->bg_yellow . "\n";
                    $cmd = new \Deploy\Command();
                    $cmd->run('apach2ctl graceful');
                    break;
            }

            if (isset($this->config['hooks']['after_rollback']) && !empty($this->config['hooks']['after_rollback'])) {
                if (is_array($this->config['hooks']['after_rollback'])) {
                    echo $color("Executing after_rollback hooks")->white->bold->bg_yellow . "\n";
                    $cmd = new \Deploy\Command();

                    foreach ($this->config['hooks']['after_rollback'] as $hook) {
                        $cmd->run($hook);
                    }
                } else {
                    echo $color("Executing after_rollback hook: " . $this->config['hooks']['after_rollback'])->white->bold->bg_yellow . "\n";
                    $cmd = new \Deploy\Command();
                    $cmd->run($this->config['hooks']['after_rollback']);
                }
            }

            echo $color('Finished rolling back of site: ' . $this->site . ', environment: ' . $this->env)->white->bold->bg_green . "\n";
        } else {
            echo $color('Rollback of site: ' . $this->site . ', environment: ' . $this->env . ' was abandoned')->white->bold->bg_yellow . "\n";
            exit(0);
        }
    }

    private function removeInstallDir() {
        $cmd = new \Deploy\Command();
        $cmd->run('rm -r ' . $this->config['install']['dir']);
    }
}
?>

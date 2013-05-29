<?php
namespace Deploy;

class Deployer {
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

        echo $color('Starting deployment of site: ' . $this->site . ', environment: ' . $this->env)->white->bold->bg_green . "\n";
        if ($cli->confirm('Are you sure you want to deploy this site [y/n]? ')) {
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

            if (!file_exists($this->config['install']['dir']) && $this->setup) {
                echo $color("Building install directory: " . $this->config['install']['dir'])->white->bold->bg_yellow . "\n";
                $this->buildInstallDir();
            }

            echo $color("Removing any old releases")->white->bold->bg_yellow . "\n";
            $this->cleanReleases();

            $vcs_class = 'Deploy\VersionControl\\' . ucfirst($this->config['version_control']['type']);

            if (!file_exists('lib/' . str_replace('\\', '/', $vcs_class) . '.php')) {
                throw new \Exception("Class: " . $vcs_class . " does not exist");
            }

            $vcs = new $vcs_class($this->config, $this->site, $this->env);
            $vcs->run();

            if (isset($this->config['dependency_manager'])) {
                if (empty($this->config['dependency_manager']['type'])) {
                    throw new \Exception("A dependy manager type is required");
                }

                $dep_class = 'Deploy\DependencyManager\\' . ucfirst($this->config['dependency_manager']['type']);

                if (!file_exists('lib/' . str_replace('\\', '/', $dep_class) . '.php')) {
                    throw new \Exception("Class: " . $dep_class . " does not exist");
                }

                $dep = new $dep_class($this->config);
                $dep->run();
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
                $db->run();
            }

            if ($this->setup) {
                $web_class = 'Deploy\WebServer\\' . ucfirst($this->config['webserver']['type']);

                if (!file_exists('lib/' . str_replace('\\', '/', $web_class) . '.php')) {
                    throw new \Exception("Class: " . $web_class . " does not exist");
                }

                $web = new $web_class($this->config, $this->site, $this->env);
                $web->run();
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

            if (isset($this->config['hooks']['after_deploy']) && !empty($this->config['hooks']['after_deploy'])) {
                if (is_array($this->config['hooks']['after_deploy'])) {
                    echo $color("Executing after_deply hooks")->white->bold->bg_yellow . "\n";
                    $cmd = new \Deploy\Command();

                    foreach ($this->config['hooks']['after_deploy'] as $hook) {
                        $cmd->run($hook);
                    }
                } else {
                    echo $color("Executing after_deploy hook: " . $this->config['hooks']['after_deploy'])->white->bold->bg_yellow . "\n";
                    $cmd = new \Deploy\Command();
                    $cmd->run($this->config['hooks']['after_deploy']);
                }
            }

            echo $color('Finished deployment of site: ' . $this->site . ', environment: ' . $this->env)->white->bold->bg_green . "\n";
        } else {
            echo $color('Deployment of site: ' . $this->site . ', environment: ' . $this->env . ' was abandoned')->white->bold->bg_yellow . "\n";
            exit(0);
        }
    }

    private function buildInstallDir() {
        $structure = array('log', 'releases');
        $chmod = 0755;

        if (!mkdir($this->config['install']['dir'], $chmod)) {
            throw new \Exception("Unable to create install dir " . $this->config['install']['dir']);
        }

        foreach ($structure as $struct) {
            $base = realpath($this->config['install']['dir']);

            if (!mkdir($base . '/' . $struct, $chmod)) {
                throw new \Exception("Unbale to create " . $base . "/" . $struct . " dir");
            }
        }
    }

    private function cleanReleases() {
        $items = scandir($this->config['install']['dir'] . '/releases/');
        unset($items[0]);
        unset($items[1]);

        $dirs = array();
        foreach ($items as $item) {
            if (is_dir($this->config['install']['dir'] . '/releases/' . $item)) {
                $dirs[] = $this->config['install']['dir'] . '/releases/' . $item;
            }
        }

        if (count($dirs) > 1) {
            $remove = null;
            $prev = null;

            foreach ($dirs as $dir) {
                $time = (int) basename($dir);

                if (is_null($prev) && is_null($remove)) {
                    $prev = $dir;
                    $remove = $dir;
                } else {
                    if ($time < (int) basename($prev)) {
                        $prev = $dir;
                        $remove = $dir;
                    } else {
                        $prev = $dir;
                    }
                }
            }

            echo "Removing: " . $remove . "\n";
            $cmd = new \Deploy\Command();
            $cmd->run('rm -r ' . $remove);
        }
    }
}
?>

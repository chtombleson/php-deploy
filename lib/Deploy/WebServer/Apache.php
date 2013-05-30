<?php
namespace Deploy\WebServer;

class Apache {
    protected $site;
    protected $env;
    private $config;

    public function __construct($config, $site, $env) {
        $this->config = $config;
        $this->site = $site;
        $this->env = $env;

        $apache = exec('which apache2ctl');
        if (empty($apache)) {
            throw new \Exception("Apache is not installed");
        }

        if (!isset($this->config['webserver']['servername'])) {
            throw new \Exception("Config for webserver requires servername");
        }
    }

    public function run() {
        $color = new \Colors\Color();
        echo $color("Setting up Apache")->white->bold->bg_yellow . "\n";
        $this->parseConf();

        if (isset($this->config['hooks']['after_apache']) && !empty($this->config['hooks']['after_apache'])) {
            if (is_array($this->config['hooks']['after_apache'])) {
                echo $color("Executing Hook, after_apache")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_apache'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                echo $color("Executing Hook, after_apache: " . $this->config['hooks']['after_apache'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Commad();
                $cmd->run($this->config['hooks']['after_apache']);
            }
        }

        $msg  = "Symlinking /etc/apache2/sites-available/" . $this->site . "-" . $this->env . ".conf to ";
        $msg .= "/etc/apache2/sites-enabled/" . $this->site . "-" . $this->env . ".conf";
        echo $color($msg)->white->bold->bg_yellow . "\n";

        if (file_exists('/etc/apache2/sites-enabled/' . $this->site . '-' . $this->env . '.conf')) {
            unlink('/etc/apache2/sites-enabled/' . $this->site . '-' . $this->env . '.conf');
        }

        if (!symlink('/etc/apache2/sites-available/' . $this->site . '-' . $this->env . '.conf', '/etc/apache2/sites-enabled/' . $this->site . '-' . $this->env . '.conf')) {
            throw new \Exception("Unable to symlink apache conf");
        }

        echo $color("Reloading apache")->white->bold->bg_yellow . "\n";
        $cmd = new \Deploy\Command();
        $cmd->run('apache2ctl graceful');
    }

    public function rollback() {
        $color = new \Colors\Color();
        echo $color("Rolling back Apache")->white->bold->bg_yellow . "\n";

        $cmd = new \Deploy\Command();
        echo "Removing Apache config\n";
        $cmd->run('rm /etc/apache2/sites-enabled/' . $this->site . '-' . $this->env . '.conf');
        $cmd->run('rm /etc/apache2/sites-available/' . $this->site . '-' . $this->env . '.conf');

        echo "Reloading Apache\n";
        $cmd->run('apache2ctl graceful');

        if (isset($this->config['hooks']['after_apache_rollback']) && !empty($this->config['hooks']['after_apache_rollback'])) {
            if (is_array($this->config['hooks']['after_apache_rollback'])) {
                echo $color("Executing Hook, after_apache_rollback")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_apache_rollback'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                echo $color("Executing Hook, after_apache_rollback: " . $this->config['hooks']['after_apache_rollback'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Commad();
                $cmd->run($this->config['hooks']['after_apache_rollback']);
            }
        }
    }

    private function parseConf() {
        if (isset($this->config['webserver']['conf'])) {
            $conf = $this->config['webserver']['conf'];
        } else {
            $conf = 'apache.conf';
        }

        if (!file_exists('sites/' . $this->site . '/' . $this->env . '/' . $conf)) {
            throw new \Exception("Apache config file does not exist, " . 'sites/' . $this->site . "/" . $this->env . "/" . $conf);
        }

        $config = file_get_contents('sites/' . $this->site . '/' . $this->env . '/' . $conf);
        preg_match_all('#\[\[([^\]]+)\]\]#', $config, $matches);

        if (empty($matches)) {
            throw new \Exception("No place holder sfound in apache config. Required placeholder are [[SERVERNAME]] & [[WEBROOT]]");
        }

        $mcount = count($matches[0]);
        for ($i=0; $i < $mcount; $i++) {
            if ($matches[1][$i] == 'WEBROOT') {
                $config = preg_replace('#' . preg_quote($matches[0][$i]) . '#', $this->config['install']['dir'] . '/current', $config);
            } else {
                $key = strtolower($matches[1][$i]);
                if (!isset($this->config['webserver'][$key])) {
                    throw new \Exception("No config data for apache config placeholder " . $matches[0][$i]);
                }

                $config = preg_replace('#' . preg_quote($matches[0][$i]) . '#', $this->config['webserver'][$key], $config);
            }
        }

        $write = file_put_contents('/etc/apache2/sites-available/' . $this->site . '-' . $this->env . '.conf', $config);

        if ($write === false) {
            throw new \Exception("Unable to write apache conf to: /etc/apache2/sites-available/" . $this->site . "/" . $this->env . "/" . $this->site . "-" . $this->env . ".conf");
        }
    }
}
?>

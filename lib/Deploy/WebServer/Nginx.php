<?php
namespace Deploy\WebServer;

class Nginx {
    protected $site;
    protected $env;
    private $config;

    public function __construct($config, $site, $env) {
        $this->config = $config;
        $this->site = $site;
        $this->env = $env;

        $nginx = exec('which nginx');
        if (empty($nginx)) {
            throw new \Exception("Nginx is not installed");
        }

        if (!isset($this->config['webserver']['servername'])) {
            throw new \Exception("Config for webserver requires servername");
        }
    }

    public function run() {
        $color = new \Colors\Color();
        echo $color("Setting up Nginx")->white->bold->bg_yellow . "\n";
        $this->parseConf();

        if (isset($this->config['hooks']['after_nginx']) && !empty($this->config['hooks']['after_nginx'])) {
            if (is_array($this->config['hooks']['after_nginx'])) {
                echo $color("Executing Hooks, after_nginx")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_nginx'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                echo $color("Executing Hook, after_nginx: " . $this->config['hooks']['after_nginx'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();
                $cmd->run($this->config['hooks']['after_nginx']);
            }
        }

        $msg  = "Symlinking /etc/nginx/sites-available/" . $this->site . "-" . $this->env . ".conf to ";
        $msg .= "/etc/nginx/sites-enabled/" . $this->site . "-" . $this->env . ".conf";
        echo $color($msg)->white->bold->bg_yellow . "\n";

        if (file_exists('/etc/nginx/sites-enabled/' . $this->site . '-' . $this->env . '.conf')) {
            unlink('/etc/nginx/sites-enabled/' . $this->site . '-' . $this->env . '.conf');
        }

        if (!symlink('/etc/nginx/sites-available/' . $this->site . '-' . $this->env . '.conf', '/etc/nginx/sites-enabled/' . $this->site . '-' . $this->env . '.conf')) {
            throw new \Exception("Unable to symlink nginx conf");
        }

        echo $color("Reloading nginx")->white->bold->bg_yellow . "\n";
        $cmd = new \Deploy\Command();
        $cmd->run('nginx -s reload');
    }

    public function rollback() {
        $color = new \Colors\Color();
        echo $color("Rolling back Nginx")->white->bold->bg_yellow . "\n";

        echo "Rolling back Nginx config\n";
        $cmd = new \Deploy\Command();
        $cmd->run('rm /etc/nginx/sites-enabled/' . $this->site . '-' . $this->env . '.conf');
        $cmd->run('rm /etc/nginx/sites-available/' . $this->site . '-' . $this->env . '.conf');

        echo "Reloading Nginx\n";
        $cmd->run('nginx -s reload');

        if (isset($this->config['hooks']['after_nginx_rollback']) && !empty($this->config['hooks']['after_nginx_rollback'])) {
            if (is_array($this->config['hooks']['after_nginx_rollback'])) {
                echo $color("Executing Hooks, after_nginx_rollback")->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();

                foreach ($this->config['hooks']['after_nginx_rollback'] as $hook) {
                    echo "Running command: " . $hook . "\n";
                    $cmd->run($hook);
                }
            } else {
                echo $color("Executing Hook, after_nginx_rollback: " . $this->config['hooks']['after_nginx_rollback'])->white->bold->bg_yellow . "\n";
                $cmd = new \Deploy\Command();
                $cmd->run($this->config['hooks']['after_nginx_rollback']);
            }
        }
    }

    private function parseConf() {
        if (isset($this->config['webserver']['conf'])) {
            $conf = $this->config['webserver']['conf'];
        } else {
            $conf = 'nginx.conf';
        }

        if (!file_exists('sites/' . $this->site . '/' . $this->env . '/' . $conf)) {
            throw new \Exception("Nginx config file does not exist, " . 'sites/' . $this->site . "/" . $this->env . "/" . $conf);
        }

        $config = file_get_contents('sites/' . $this->site . '/' . $this->env . '/' . $conf);
        preg_match_all('#\[\[([^\]]+)\]\]#', $config, $matches);

        if (empty($matches)) {
            throw new \Exception("No place holder sfound in nginx config. Required placeholder are [[SERVERNAME]] & [[WEBROOT]]");
        }

        $mcount = count($matches[0]);
        for ($i=0; $i < $mcount; $i++) {
            if ($matches[1][$i] == 'WEBROOT') {
                $config = preg_replace('#' . preg_quote($matches[0][$i]) . '#', $this->config['install']['dir'] . '/current', $config);
            } else {
                $key = strtolower($matches[1][$i]);
                if (!isset($this->config['webserver'][$key])) {
                    throw new \Exception("No config data for nginx config placeholder " . $matches[0][$i]);
                }

                $config = preg_replace('#' . preg_quote($matches[0][$i]) . '#', $this->config['webserver'][$key], $config);
            }
        }

        $write = file_put_contents('/etc/nginx/sites-available/' . $this->site . '-' . $this->env . '.conf', $config);

        if ($write === false) {
            throw new \Exception("Unable to write nginx conf to: /etc/nginx/sites-available/" . $this->site . "/" . $this->env . "/" . $this->site . "-" . $this->env . ".conf");
        }
    }
}
?>

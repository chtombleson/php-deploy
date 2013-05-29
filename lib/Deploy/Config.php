<?php
namespace Deploy;

class Config {
    protected $site;
    protected $env;
    protected $conf_file = null;
    private $config;

    public function __construct($site, $env, $config = null) {
        if (!file_exists('sites/' . $site)) {
            throw new \Exception("No site directory for: " . $site . " was found in sites/");
        }

        if (!file_exists('sites/' . $site . '/' . $env)) {
            throw new \Exception("No environment directory for: " . $env . " was found in sites/" . $site . "/");
        }

        if (!empty($config)) {
            if (!file_exists('sites/' . $site . '/' . $env . '/' . $config)) {
                throw new \Exception("Config file: " . $config . " was not in sites/" . $site . "/" . $env . "/");
            }

            $this->conf_file = 'sites/' . $site . '/' . $env . '/' . $config;
        } else {
            if (!file_exists('sites/' . $site . '/' . $env . '/config.yml')) {
                throw new \Exception("Config file: config.yml was not in sites/" . $site . "/" . $env . "/");
            }
        }

        $this->site = $site;
        $this->env = $env;
        $this->conf_file = 'sites/' . $site . '/' . $env . '/config.yml';
        $this->loadConf();
    }

    public function get() {
        return $this->config;
    }

    private function loadConf() {
        $yaml = \Spyc::YAMLLoad($this->conf_file);

        if (!isset($yaml['version_control'])) {
            throw new \Exception("Config requires version control information");
        }

        if (!isset($yaml['install'])) {
            throw new \Exception("Config requires install information");
        }

        if (!isset($yaml['webserver'])) {
            throw new \Exception("Config requires webserver information");
        }

        $this->config = $yaml;
    }
}
?>

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

        if (!isset($yaml['setup'])) {
            throw new \Exception("Config requires setup tasks");
        }

        if (!isset($yaml['update'])) {
            throw new \Exception("Config requires update tasks");
        }

        if (!isset($yaml['global'])) {
            $yaml['global'] = array(
                'install_dir' => '/var/www/' . $this->site . '-' . $this->env,
            );
        } else if (!isset($yaml['global']['install_dir'])) {
            $yaml['global']['install_dir'] = '/var/www/' . $this->site . '-' . $this->env;
        } else if (isset($yaml['global']['install_dir'])) {
            $yaml['global']['install_dir'] = realpath($yaml['global']['install_dir']);
        }

        $this->config = $yaml;
    }
}
?>

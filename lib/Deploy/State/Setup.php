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
namespace Deploy\State;

class Setup {
    protected $site;
    protected $env;
    protected $config;

    public function __construct($site, $env, $config = null) {
        $conf = new Config($site, $env, $config);
        $this->config = $conf->get();
        $this->site = $site;
        $this->env = $env;
    }

    public function run() {
        if (!isset($this->config['setup']['tasks'])) {
            throw new \Exception("No setup task were defined in your config.yml file");
        } else {
            if (isset($this->config['global']['ssh'])) {
                $this->buildInstallDir(true);
                $base = 'Deploy\Task\Ssh\\\\';

                foreach ($this->config['setup']['tasks'] as $task => $conf) {
                    $class = $base . ucfirst($task);
                    $task = new $class($this->config);
                    $task->setup();
                }
            } else {
                $this->buildInstallDir();
                $base = 'Deploy\Task\Local\\\\';

                foreach ($this->config['setup']['tasks'] as $task => $conf) {
                    $class = $base . ucfirst($task);
                    $task = new $class($this->config);
                    $task->setup();
                }
            }
        }
    }

    private function buildInstallDir($ssh = null) {
        $structure = array('releases');

        if (!empty($ssh)) {
            $cmd = new Deploy\Task\Ssh\Command();
        } else {
            $cmd = new Deploy\Task\Local\Command();
        }

        $command = sprintf('mkdir -p %s/%s', $webroot, implode('/', $structure));
        $cmd->run($command);
    }
}
?>

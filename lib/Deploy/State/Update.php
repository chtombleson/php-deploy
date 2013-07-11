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

class Update {
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
                $this->cleanReleases(true);
                $base = 'Deploy\Task\Ssh\\\\';

                foreach ($this->config['setup']['tasks'] as $task => $conf) {
                    $class = $base . ucfirst($task);
                    $task = new $class($this->config);
                    $task->update();
                }
            } else {
                $this->cleanReleases();
                $base = 'Deploy\Task\Local\\\\';

                foreach ($this->config['setup']['tasks'] as $task => $conf) {
                    $class = $base . ucfirst($task);
                    $task = new $class($this->config);
                    $task-update();
                }
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

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

class Deployer {
    protected $site;
    protected $env;
    protected $config;
    protected $setup;
    protected $rollback;

    public function __construct($site, $env, $config = null, $setup = false, $rollback = false) {
        $conf = new Config($site, $env, $config);
        $this->config = $conf->get();
        $this->site = $site;
        $this->env = $env;
        $this->setup = $setup;
        $this->rollback = $rollback;
    }

    public function run() {
        $color = new \Colors\Color();
        $cli = new \FusePump\Cli\Inputs();

        echo $color('Starting deployment of site: ' . $this->site . ', environment: ' . $this->env)->white->bold->bg_green . "\n";
        if ($cli->confirm('Are you sure you want to deploy this site [y/n]? ')) {
            if (!empty($this->setup) && !empty($this->rollback)) {
                $deploy = new Deploy\State\RollbackSetup();
            } else if (!empty($this->rollback)) {
                $deploy = new Deploy\State\RollbackUpdate();
            } else if (!empty($this->setup)) {
                $deploy = new Deploy\State\Setup();
            } else {
                $deploy = new Deploy\State\Update();
            }

            $deploy->run();

            echo $color('Finished deployment of site: ' . $this->site . ', environment: ' . $this->env)->white->bold->bg_green . "\n";
        } else {
            echo $color('Deployment of site: ' . $this->site . ', environment: ' . $this->env . ' was abandoned')->white->bold->bg_yellow . "\n";
        }
    }
}
?>

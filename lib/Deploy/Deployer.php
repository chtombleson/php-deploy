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
    protected $ssh = false;

    public function __construct($site, $env, $config = null, $setup = false) {
        $conf = new Config($site, $env, $config);
        $this->config = $conf->get();
        $this->site = $site;
        $this->env = $env;
        $this->setup = $setup;

        if (isset($this->config['ssh'])) {
            $this->ssh = true;
        }
    }

    public function run() {
        if ($this->ssh) {
            $deploy = new Deploy\Ssh($this->site, $this->env, $this->config, $this->setup);
            $deploy->run();
        } else {
            $deploy = new Deploy\Local($this->site, $this->env, $this->config, $this->setup);
            $deploy->run();
        }
    }
}
?>

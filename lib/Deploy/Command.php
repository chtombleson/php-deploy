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

class Command {
    protected $workingdir = './';
    protected $output;

    public function __construct($workingdir = null) {
        if (!empty($workingdir)) {
            $this->setWorkingDir($workingdir);
        }
    }

    public function setWorkingDir($workingdir) {
        if (!file_exists($workingdir)) {
            throw new \Exception("Directory: " . $workingdir . " does not exist");
        }

        $this->workingdir = $workingdir;
    }

    public function getWorkingDir() {
        return $this->workingdir;
    }

    public function run($cmd, $workingdir = null, $output = true, $escape = false) {
        if (!empty($workingdir)) {
            $this->setWorkingDir($workingdir);
        }

        if ($escape) {
            $cmd = $this->escapeCmd($cmd);
        }

        $spec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'r'),
        );

        $process = proc_open($cmd, $spec, $pipes, $this->getWorkingDir());

        if (is_resource($process)) {
            fclose($pipes[0]);

            $this->output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            if ($output) {
                echo $this->getOutput();
            }

            $exitstatus = proc_close($process);
            return $exitstatus;
        }

        return false;
    }

    public function getOutput() {
        return $this->output;
    }

    private function escapeCmd($cmd) {
        return escapeshellcmd($cmd);
    }
}
?>

<?php
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

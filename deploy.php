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
require_once('vendor/autoload.php');
spl_autoload_register(function($class) {
    if (strpos($class, 'Deploy') === false) {
        return;
    }

    require_once('lib/' . str_replace('\\', '/', $class) . '.php');
});

if ($argc < 2) {
    echo "Usage: " . $argv[0] . " --site [site name] --environment [test|prod]\n";
    echo "Options:\n";
    echo "\t--help, -h: Show this message\n";
    echo "\t--site, -site: Name of site to deploy\n";
    echo "\t--environment, -env: Environment to deploy to (testing (test), production (prod))\n";
    echo "\t--setup, -setup: Setup new site (Default is update)\n";
    echo "\t--rollback, -rollback: Rollback a release\n";
    echo "\t--config, -config: Config file to use\n";
    exit;
}

$cli = new FusePump\Cli\Inputs($argv);
$cli->option('-h, --help', 'Help');
$cli->option('-site, --site [type]', 'Name of site to deploy', true);
$cli->option('-env, --environment [type]', 'Environment to deploy to (testing, production)', true);
$cli->option('-setup, --setup', 'Setup new site (Default is update)');
$cli->option('-rollback, --rollback', 'Rollback a release');
$cli->option('-config, --config [type]', 'Config file to use');

$color = new Colors\Color();

if (!$cli->parse()) {
    exit(1);
}

$allowed_envs = array('prod', 'testing');

if (!in_array($cli->get('-env'), $allowed_envs)) {
    echo $color('Error: Invalid environment given, valid environments are prod & testing.')->white->bold->bg_red . "\n";
    exit;
}

try {
    $deployer = new Deploy\Deployer($cli->get('-site'), $cli->get('-env'), $cli->get('-config'), $cli->get('-setup'), $cli->get('-rollback'));
    $deployer->run();
} catch (Exception $e) {
    echo $color("Error: " . $e->getMessage())->white->bold->bg_red . "\n";
    echo $color("Deployment Failed!!!")->white->bold->bg_red . "\n";
    exit(1);
}
?>

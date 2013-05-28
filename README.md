# PHP Deploy
A simple php deployment system.

This can be used to deploy websites for version control to a server in an automated way.

## Configuration
To deploy a site using this tool you will need to create a directory for the site you want to deploy
in the sites/ directory you will also have to create two evironment directories (prod & testing) or
just one of them.

For an example configuration look at the example in the sites/ directory.

Each environment has it's own config.yml file and a template of the web server configuration.

## What does this tool support
Currently there is support for Git, Composer, Nginx, Apache and PostgreSQL with more to come.

## Installation
    $git clone https://github.com/chtombleson/php-deploy.git
    $cd php-deploy
    $composer install

## Useage
    $cd php-deploy
    $php deploy.php -site [sitename] -env [environment]
    $php deploy.php --help

## License
See LICENSE

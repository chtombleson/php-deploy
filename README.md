# PHP Deploy
A simple php deployment system.

This can be used to deploy websites from version control to a server in an automated way and also the ability of rolling back releases.

## Configuration
To deploy a site using this tool you will need to create a directory for the site you want to deploy
in the sites/ directory you will also have to create two evironment directories (prod & testing) or
just one of them.

For an example configuration look at the example in the sites/ directory.

Each environment has it's own config.yml file and a template of the web server configuration.

## What does this tool support
Currently there is support for Git, Composer, Nginx, Apache, PostgreSQL and MySQL with more to come.

## Installation
See INSTALL.md

## Usage
Setup a site:

    $/home/deploy/ php deploy.php -site [sitename] -env [testing | prod] -setup

Updating a site:

    $/home/deploy/ php deploy.php -site [sitname] -env [testing | prod]

Rolling back an Update:

    $/home/deploy/ php deploy.php -site [sitname] -env [testing | prod] -rollback

Rolling back a Setup:

    $/home/deply/ php deploy.php -site [sitename] -env [testing | prod] -setup -rollback

## License
See LICENSE

# Installing PHP-Deploy

1. Requirements
2. Create a deploy user (This user will need relvant permissions to run command)
3. Get PHP-Deploy from github
4. Configuring a site for deployment
5. How to deploy a site

## Requirements
 * PHP 5.3+
 * Git
 * Composer installed system wide (Optional)
 * Web server (Nginx, Apache are support atm)
 * Database server (Optional: PostgreSQL, MySQL are supported atm)

## Create a deploy user
This is the easiest way to manage the permissions that are needed to deploy sites.

The following permission are needed:
 * Ability to read / write to web directory and /tmp
 * Abiltiy to reload the webserver Nginx(nginx -s reload), Apache(apache2ctl graceful)
 * Ability to create database users and database via PostgreSQL(psql), MySQL(mysql)
 * Ability to run composer
 * Ability to run shell commands for hooks

## Get PHP-Deploy from github
After you have created your deploy user in that users home directory clone the git repo from github

Run the following command to get the code for php deploy:

    $/home/deploy/ git clone git://github.com/chtombleson/php-deploy.git
    $/home/deploy/ cd php-deploy
    $/home/deploy/ composer install

## Configuring a site for deployment
Setting up a site for deployment is quite simple each site has a directory which is named after the site and in that directory there should be a testing / prod directory depending on which environment you will be deploy to.

Inside the testing / prod directory we need to create a config.yml file which holds all the information about how to deploy the site and also a webserver config template.

Here is an example config.yml for a site that is running on nginx, php-fpm, PostgreSQL and uses git for version control.

   version_control: # Required
    type: "git" # Version control type (Git is only supported atm)
    url: "git://github.com/user/repo.git" # Git repo address

    install: # Required
        dir: "/var/www/test" # Where the site should live

    webserver: # Required
        type: "nginx" # Webserver it's running on (Nginx & Apache are only supported atm)
        servername: "test.example.com" # Domain name where site can be accessed
        errorlog: "/var/log/sitelogs/test/nginx.error.log" # Optional placeholder value for nginx conf
        accesslog: "/var/log/sitelogs/test/nginx.acccess.log" # Optional placeholder value for nginx conf

    dependency_manager: # Optional
        type: "composer" # Dependency manager to use (Composer is only supported atm)

    database: # Optional
        type: "postgresql" # Database to use (PostgreSQL and Mysql are only supported atm)
        name: "test" # Database name
        username: "test" # Database username
        password: "test" # Database password

    hooks: # Optional
        after_git: "shell cmd" # Run a shell command(s) after git has finished
        after_nginx: "mkdir /var/log/sitelogs/test/" # Run a command(s) after nginx has parsed the config
        after_apache: "shell cmd" # Run a command(s) after apache has parsed the config
        after_deploy: ["/etc/init.d/php5-fpm reload"] # Run a shell command(s) after deploy is finished
        after_composer: ["shell cmd"] # Run a shell command(s) after composer has run

Here is an example nginx.conf template:

    server {
        listen 80;
        servername [[SERVERNAME]]; # [[SERVERNAME]] is a placeholder that will be replace with the servername from the config file
        root [[WEBROOT]]; # [[WEBROOT]] is a placeholder that will be replaced by the path to the current release
        error_log [[ERRORLOG]]; # [[ERRORLOG]] is a placeholder that will be replaced with the errorlog value for the webserver config section in config.yml
        access_log [[ACCESSLOG]]; # [[ACCESSLOG]] is a placeholder that will be replaced with the accesslog value for the webserver config section in config.yml
        index index.html;

        location / {
            try_files $uri index.html;
        }
    }

If you are using PHP-FPM it is a good idea to reload PHP-FPM as it can cache information.

Save both of these files into the testing or prod directory depending on what environment you want to deploy to.

## How to deploy a site
Deploying a site is straight forward run the following command to setup a new site:

    $/home/deploy/ php deploy -site [sitename] -env [environment (testing | prod)] -setup

Updating a site is also just as simple

    $/home/deploy/ php deploy.php -site [sitename] -env [environment (testing | prod)]

The only difference between the two command is the -setup option which is needed to setup a site for the first time.

The -setup option run everything creating the web directory structure, getting the first release, setting up the webserver conf, database and install any dependencies. On an update it only creates a new release, updates any dependencies.

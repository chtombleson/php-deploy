# Setting up deloyment group

On the server you will be deploying to create the following group called deploy.

    $ sudo addgroup deploy

Depending on what setup you have in the way of web server and database setup passwordless
sudo for the following commands.

    $ sudo visudo

Add the following lines to the end of the file.

    # Apache
    %deploy ALL= NOPASSWD: /usr/sbin/apache2ctl graceful
    
    # Nginx
    %deploy ALL= NOPASSWD: /usr/sbin/nginx -s reload
    
    # Postgres
    %deploy ALL= NOPASSWD: /usr/bin/psql


It will also be a good idea to setup passwordless sudo for any other command that run as hooks.

    # PHP-FPM
    %deploy ALL= NOPASSWD: /etc/init.d/php5-fpm


You will also need to give write permissions to some directories to the deploy group.

    # Apache
    $ sudo chgrp deploy /etc/apache2/sites-available
    $ sudo chgrp deploy /etc/apache2/sites-enabled
    $ sudo chmod g+w /etc/apache2/sites-available
    $ sudo chmod g+w /etc/apache2/sites-enabled
    
    # Nginx
    $ sudo chgrp deploy /etc/nginx/sites-available
    $ sudo chgrp deploy /etc/nginx/sites-enabled
    $ sudo chmod g+w /etc/nginx/sites-available
    $ sudo chmod g+w /etc/nginx/sites-enabled
    
    # Web Root
    $ sudo chgrp deploy /var/www
    $ sudo chmod g+w /var/www


If you wish you can create a deploy user and add them to the deploy group.

    $ sudo adduser deployments


Add the deployments user to the depoy group

    $ sudo adduser deployments deploy


Add your ssh key to the users ~/.ssh/authorized_keys file

    # You may need to create the .ssh directory for the new user
    $ sudo mkdir /home/deployments/.ssh/
    $ sudo chown deployments:deployments /home/deployments/.ssh/
    
    # Now add your public ssh key to the authorized_keys file
    $ cat ~/.ssh/id-rsa.pub > /home/deployments/.ssh/authorized_keys


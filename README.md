# CJPath #

CJPath is a browser-based visualization tool of customer journey behavior. It processes data with "essentia" that works with in-memory database on server.

## Demo Packages ##

CJPath needs a dedicated environment since it processes data with in-memory database. For demo, we provide some packages.

- Docker Version :

    [Setup instruction](http://auriq.org/download/)

- AWS AMI Image : 

    - AMI name of latest version : "CJPath GUI 05.31.2017"

    - Steps to start : 

        1. Launch instance more than 2Gb as t2.small based on the AMI image.

        1. After launch, access http://your.ip via browser.

    - This AMI already have cjpath docker image and a container is already started. (ready-to-use version of docker version)

    - This is the easiest way, but cost a little. It's good if you do not want to install docker into your machine.


## Customization ##

### Requirement ###

* essentia v3.2

* LAMP environment

    - PHP 5.6
    
    - MySQL  Ver 14


### Structure of This Repository ###

Directory|Language| What it does
-------- | --- | ---
ess|essentia|Core engine of this application. It loads customer journey data into in-memory database and calculate/query based on the request from "api".
api|php,mysql|Server-side scripts. It translates requests from client side into essentia commands, run it, format it, and pass it to client side back.
app|javascript|Client-side scripts. Visualize results from essentia. Build (uglified) of src/app.
src/app|javascript|Source code of "app" directory.



### Steps To Start Customization ###

Since it requires "essentia", you need to start from either of

- [recommended] pull/launch "demo packages" (Docker/AMI) above.

- download free version of essentia from [here](http://www.auriq.com/try-free/) and deploy everything from scratch.



#### Start With Docker Version ####

  1. Pull demo package from dockerhub

  1. Run container 

  1. Enter container and go to /var/www/html/cjpath directory.


#### Start With AWS AMI Version ####

  1. Launch instance from demo AMI image (CJPath GUI 05.31.2017) 

  1. Enter the already running container as `docker exec -ti cjpath /bin/bash`

  1. Go to /var/www/html/cjpath directory.


#### Start From Scratch ####

  1. Get free version of essentia from [here](http://www.auriq.com/try-free/)

  1. Pull this repository and put it under apache document root.

  1. Change owner of `ess` directory to `apache`.

  1. Run `sudo yum install -y bc` to install `bc` command.

  1. Load `CVPathSettings` database into mysql as `mysql -u root -p$PASSWD < api/mysql-CVPathSettings-default.sql`
  
  

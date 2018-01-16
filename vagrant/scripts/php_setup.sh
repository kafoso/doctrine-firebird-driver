#!/bin/bash

add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y php7.1-fpm php7.1-xml php7.1-mbstring php7.1-interbase

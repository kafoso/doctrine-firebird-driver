#!/bin/bash

add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y php7.4-fpm php7.4-xml php7.4-mbstring php7.4-interbase

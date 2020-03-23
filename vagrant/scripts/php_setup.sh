#!/bin/bash

add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y php7.2-fpm php7.2-xml php7.2-mbstring php7.2-interbase

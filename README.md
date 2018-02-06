Doctrine Firebird driver
---------------------------

Firebird driver for the Doctrine DBAL (https://github.com/doctrine/dbal) version 2.5+.

# Requirements

- PHP >= 5.6

# Installation

...

# Tests

## Installation to run tests

1. `vagrant up` (install the VM)
2. `vagrant ssh`
3. `sudo su`
4. `apt-get -y install firebird2.5-superclassic`
 - Password: `88fb9f307125cc397f70e59c749715e1`
5. `dpkg-reconfigure firebird2.5-superclassic` (starts the server)
 - Same password.

Reference: https://firebirdsql.org/manual/ubusetup.html

## Running tests

Due to the database being created by the PHP bootstrap script on the fly, `root` is needed to run the tests **on the VM**.

1. `vagrant ssh`
2. `sudo su`
3. `cd /var/www/tests`
4. `../vendor/phpunit/phpunit/phpunit tests`

# Credits

- https://github.com/helicon-os/doctrine-dbal (not maintained)
- https://github.com/ISTDK/doctrine-dbal (fork)

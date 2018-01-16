# Doctrine Firebird driver
Firebird driver for the Doctrine DBAL (https://github.com/doctrine/dbal).

## Credits

- https://github.com/helicon-os/doctrine-dbal (not maintained)
- https://github.com/ISTDK/doctrine-dbal (fork)

## Running tests

Due to the database being created by the PHP bootstrap script on the fly, `root` is needed to run the tests **on the VM**.

1. `vagrant ssh`
2. `sudo -i`
3. `cd /var/www/tests`
4. `../vendor/phpunit/phpunit/phpunit tests`

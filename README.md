Doctrine Firebird driver
---------------------------

Firebird driver for the [Doctrine DBAL](https://github.com/doctrine/dbal) ([v2.7.*](https://packagist.org/packages/doctrine/orm#v2.7.0)).

This library is completely separate (i.e. abstracted away) from the core Doctrine DBAL library. I.e. it is fully a **plug-in**.

# Requirements

To utilize this library in your application code, the following is required:

- [Firebird](https://firebirdsql.org/) version 2.5.*
  - Version 3.* is not supported. You are very welcome to provide a pull request for this.
- PHP >= 7.2
- PHP extensions:
  - [pdo](http://php.net/manual/en/book.pdo.php)
  - [pdo-firebird](https://www.php.net/manual/en/ref.pdo-firebird.php)
  - [mbstring](http://php.net/manual/en/book.mbstring.php)
  - xml
- [doctrine/dbal: ^2.9.3](https://packagist.org/packages/doctrine/dbal#v2.9.3)

## On `pdo-firebird` vs `interbase`/`ibase`

PDO was chosen because support for the `interbase` extension was dropped in PHP 7.4 and it was moved into PECL. However, as of March 2020, the [project on PECL](http://pecl.php.net/package/interbase) has had no releases yet (see issue #9).

If this is not a concern for you and you prefer the driver to use the `interbase` PHP extension and `ibase_*` functions instead of PDO, have a look at the 2.7 branch of this repository. Admittedly, that branch has a couple extra features which aren't available in this branch (yet?). These are:
- Support for last insert id. The `pdo-firebird` extension doesn't support this natively. In 2.7 it is implemented by using a query, but it requires the name of a sequence as an argument. Not sure if implementing it in PDO this way would be much of a win.
- Support for different transaction isolation levels. Firebird does them a bit differently from other RDBMS implementations, and PDO doesn't seem to expect that, thus workarounds are needed. It appears we'd have to handle transactions on our side without relying on PDO.
- Transaction lock timeouts and waiting. Again, to do this, we would have to handle transactions ourselves.
- Autocommitting changes from last queries during object destruction, if they were not made within an explicit transaction. Again, the test just kept failing and both my patience and time were running out.
- Quoting queries without calling the constructor first. That's just not how PDO operates, and likely for a good reason.
- Different Firebird SQL dialects are only supported with PHP 7.4 and up (this was only implemented in ext-pdo-firebird [in 7.4](http://docs.php.net/manual/en/ref.pdo-firebird.connection.php)).

# License & Disclaimer

See [LICENSE](LICENSE) file. Basically: Use this library at your own risk.

## Limitations of Schema Manager

This library does **_not_ fully support generation through the Schema Manager**, i.e.:

1. Generation of database tables, views, etc. from entities.
2. Generation of entities from database tables, views, etc.

Reasons for not investing time in schema generation include that Firebird does not allow renaming of tables, which in turn makes automated schema updates annoying and over-complicated. Better results are probably achieved by writing manual migrations.

# Installation

Via Composer ([`kafoso/doctrine-firebird-driver`](https://packagist.org/packages/kafoso/doctrine-firebird-driver)):

    composer install kafoso/doctrine-firebird-driver

Via Github:

    git clone git@github.com:kafoso/doctrine-firebird-driver.git

## Configuration

### Manual configuration

For example of configuration in PHP, see [`tests/tests/Test/Integration/AbstractIntegrationTest.php`](tests/tests/Test/Integration/AbstractIntegrationTest.php) (in the method `setUp`).

Additional help may be found at: https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/index.html

### Symfony configuration (YAML)

This driver may be used like any other Doctrine DBAL driver in [Symfony](https://symfony.com/), e.g. with [doctrine/doctrine-bundle](https://packagist.org/packages/doctrine/doctrine-bundle). However, the `driver_class` option must be specified instead of simply `driver`. This is due to the driver not being part of the [core Doctrine DBAL library](https://github.com/doctrine/dbal).

Sample YAML configuration:

```
doctrine:
    dbal:
        default_connection: default
        connections:
            default:
                driver_class:   Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Driver
                host:           "%database_host%"
                port:           "%database_port%"
                dbname:         "%database_name%"
                user:           "%database_user%"
                password:       "%database_password%"
                charset:        "UTF-8"
```

# Tests

## Test/development requirements

To run tests, fix bugs, provide features, etc. the following is required:

- A system capable of running a virtual machine with [ubuntu/xenial64](https://app.vagrantup.com/ubuntu/boxes/xenial64).
- [Virtualbox](https://www.virtualbox.org/) >= 5.1.0
- [Vagrant](https://www.vagrantup.com/) >= 2.0.0

You may of course install everything manually using your own VM setup. For help and a stack list (required apt-get packages), see the [Vagrantfile](Vagrantfile).

## Installation to run tests

A few steps are required to run all tests. Unit tests ([tests/tests/Test/Unit](tests/tests/Test/Unit)) will run on all environments. However, integration tests ([tests/tests/Test/Integration](tests/tests/Test/Integration)) require the following because they test against a running Firebird database in the VM:

1. `vagrant up`<br>
Install/provision the VM.
2. `vagrant ssh`
3. `sudo su`
4. `apt-get -y install firebird2.5-superclassic`<br>
 You will be prompted to install the database using a password. Please provide this password: `88fb9f307125cc397f70e59c749715e1`. It is re-used when connecting through the DBAL later on.
5. `dpkg-reconfigure firebird2.5-superclassic`<br>
Starts the Firebird database server. Provide same password as above.

Reference: https://firebirdsql.org/manual/ubusetup.html

## Running tests

Due to the database being created by the PHP bootstrap script on the fly, `root` is needed to run the tests **on the VM**.

1. `vagrant ssh`
2. `sudo su`
3. `apt-get install zip -y` (for when installing composer packages)
4. Install composer (on guest OS). Follow these instructions: https://getcomposer.org/download/
5. `mv composer.phar /usr/bin/composer`
6. `cd /var/git/kafoso/doctrine-firebird-driver`
7. `composer install`<sup>1</sup>
8. `cd /var/git/kafoso/doctrine-firebird-driver/tests`
9. `../bin/phpunit tests`

<sup>1</sup> Composer will say you shouldn't run it as root/super user. This is techically true, but it's fine in the VM.

# Credits

## Authors

- **Kasper Søfren**<br>
https://github.com/kafoso<br>
E-mail: soefritz@gmail.com
- **Uffe Pedersen**<br>
https://github.com/upmedia
- **Rimas Kudelis**<br>
https://github.com/rimas-kudelis

## Acknowledgements

### https://github.com/doctrine/dbal

Fundamental Doctrine DBAL implementation. The driver and platform logic in this library is based on other implementations in the core library, largely [`\Doctrine\DBAL\Driver\PDOOracle\Driver`](https://github.com/doctrine/dbal/blob/v2.9.3/lib/Doctrine/DBAL/Driver/PDOOracle/Driver.php) and [`\Doctrine\DBAL\Platforms\OraclePlatform`](https://github.com/doctrine/dbal/blob/v2.9.3/lib/Doctrine/DBAL/Platforms/OraclePlatform.php), and their respective parent classes.

### https://github.com/helicon-os/doctrine-dbal

Whilst a great inspiration for this library - and we very much appreciate the work done by the authors - the library has a few flaws and limitations regarding the Interbase Firebird driver logic:

- It contains bugs. E.g. incorrect/insufficient handling of nested transactions and save points.
- It is lacking with respect to test coverage.
- It appears to no longer be maintained. Possibly entirely discontinued.
- It is intermingled with the core Doctrine DBAL code, making version management and code adaptation unnecessarily complicated; a nightmare, really. It is forked from https://github.com/doctrine/dbal, although, this is not specifically stated.
- It is not a Composer package (not on [https://packagist.org](https://packagist.org)).

### https://github.com/ISTDK/doctrine-dbal

A fork of https://github.com/helicon-os/doctrine-dbal with a few improvements and fixes.

### https://firebirdsql.org/

The main resource for Firebird documentation, syntax, downloads, etc.

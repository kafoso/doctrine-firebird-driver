Doctrine Firebird driver
---------------------------

Firebird driver for the [Doctrine DBAL](https://github.com/doctrine/dbal).

This library is completely separate (i.e. abstracted away) from the core Doctrine DBAL library. I.e. it is fully a **plug-in**, however, it requires **configuration in the PHP code**, contra e.g. YAML configuration in [Symfony](https://github.com/symfony/symfony).

# Requirements

To utilize this library in your application code, the following is required:

- [Firebird](https://firebirdsql.org/) version 2.5.*
  - Version 3.* is not supported. You are very welcome to provide a pull request for this.
- PHP >= 5.6.0
- [doctrine/dbal: 2.5.\*](https://packagist.org/packages/doctrine/dbal#v2.5.0)

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

For example of configuration in PHP, see [`tests/tests/Test/Integration/AbstractIntegrationTest.php`](tests/tests/Test/Integration/AbstractIntegrationTest.php) (in the method `setUp`).

A YAML configuration example is not provided, nor is YAML supported at the current stage.

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
3. `cd /var/git/kafoso/doctrine-firebird-driver/tests`
4. `../vendor/phpunit/phpunit/phpunit tests`

# Credits

## Authors

- **Kasper Søfren**<br>
https://github.com/kafoso<br>
E-mail: soefritz@gmail.com
- **Uffe Pedersen**<br>
https://github.com/upmedia

## Acknowledgements

### https://github.com/doctrine/dbal

Fundamental Doctrine DBAL implementation. The driver and platform logic in this library is based on other implementations in the core library, largely [`\Doctrine\DBAL\Driver\PDOOracle\Driver`](https://github.com/doctrine/dbal/blob/v2.5.0/lib/Doctrine/DBAL/Driver/PDOOracle/Driver.php) and [`\Doctrine\DBAL\Platforms\OraclePlatform`](https://github.com/doctrine/dbal/blob/v2.5.0/lib/Doctrine/DBAL/Platforms/OraclePlatform.php), and their respective parent classes.

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

<?php
namespace Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\DBALException;
use Kafoso\DoctrineFirebirdDriver\Driver\AbstractFirebirdInterbaseDriver;
use PDOException;

class Driver extends AbstractFirebirdInterbaseDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        try {
            return new Connection(
                $this->constructPdoDsn($params),
                $username,
                $password,
                $driverOptions
            );
        } catch (PDOException $e) {
            throw DBALException::driverException($this, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'FirebirdInterbase';
    }

    /**
     * Constructs the Firebird PDO DSN.
     *
     * @param mixed[] $params
     *
     * @return string The DSN.
     */
    protected function constructPdoDsn(array $params)
    {
        $dsn = 'firebird:dbname=';
        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= $params['host'];

            if (isset($params['port']) && $params['port'] !== '') {
                $dsn .= '/' . $params['port'];
            }

            $dsn .= ':';
        }
        if (isset($params['dbname'])) {
            $dsn .= $params['dbname'];
        }
        if (isset($params['charset'])) {
            $dsn .= ';charset=' . $params['charset'];
        }
        if (isset($params['role'])) {
            $dsn .= ';role=' . $params['role'];
        }
        if (isset($params['dialect'])) {
            $dsn .= ';dialect=' . $params['dialect'];
        }

        return $dsn;
    }
}

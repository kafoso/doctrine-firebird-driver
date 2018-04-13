<?php
namespace Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\DBALException;
use Kafoso\DoctrineFirebirdDriver\Driver\AbstractFirebirdInterbaseDriver;

class Driver extends AbstractFirebirdInterbaseDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        $this->setDriverOptions($driverOptions);
        try {
            return new Connection(
                $params,
                $username,
                $password,
                $this->getDriverOptions() // Sanitized
            );
        } catch (Exception $e) {
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
}

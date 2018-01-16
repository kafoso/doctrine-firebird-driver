<?php
namespace IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\DBALException;
use IST\DoctrineFirebirdDriver\Driver\AbstractFirebirdInterbaseDriver;

class Driver extends AbstractFirebirdInterbaseDriver
{
    /**
     * @param array $params                     N/A.
     * @param ?string $username                 N/A.
     * @param ?string $password                 N/A.
     * @param array $driverOptions              N/A.
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        try {
            return new Connection($this->configuration);
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

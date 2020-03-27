<?php
namespace Kafoso\DoctrineFirebirdDriver\Driver;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\ExceptionConverterDriver;
use Doctrine\DBAL\Exception;
use Kafoso\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;
use Kafoso\DoctrineFirebirdDriver\Schema\FirebirdInterbaseSchemaManager;

abstract class AbstractFirebirdInterbaseDriver implements Driver, ExceptionConverterDriver
{
    /**
     * {@inheritdoc}
     */
    public function convertException($message, \Doctrine\DBAL\Driver\DriverException $exception)
    {
        $message = 'Error ' . $exception->getErrorCode() . ': ' . $message;
        switch ($exception->getErrorCode()) {
            case -104:
                return new \Doctrine\DBAL\Exception\SyntaxErrorException($message, $exception);
            case -204:
                if (preg_match('/.*(dynamic sql error).*(table unknown).*/i', $message)) {
                    return new \Doctrine\DBAL\Exception\TableNotFoundException($message, $exception);
                }
                if (preg_match('/.*(dynamic sql error).*(ambiguous field name).*/i', $message)) {
                    return new \Doctrine\DBAL\Exception\NonUniqueFieldNameException($message, $exception);
                }
                break;
            case -206:
                if (preg_match('/.*(dynamic sql error).*(table unknown).*/i', $message)) {
                    return new \Doctrine\DBAL\Exception\InvalidFieldNameException($message, $exception);
                }
                if (preg_match('/.*(dynamic sql error).*(column unknown).*/i', $message)) {
                    return new \Doctrine\DBAL\Exception\InvalidFieldNameException($message, $exception);
                }
                break;
            case -803:
                return new \Doctrine\DBAL\Exception\UniqueConstraintViolationException($message, $exception);
            case -530:
                return new \Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException($message, $exception);
            case -607:
                if (preg_match('/.*(unsuccessful metadata update Table).*(already exists).*/i', $message)) {
                    return new \Doctrine\DBAL\Exception\TableExistsException($message, $exception);
                }
                break;
            case -902:
                return new \Doctrine\DBAL\Exception\ConnectionException($message, $exception);
        }
        return new Exception\DriverException($message, $exception);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        $params = $conn->getParams();
        return $params['dbname'];
    }

    /**
     * {@inheritdoc}
     * @return FirebirdInterbasePlatform
     */
    public function getDatabasePlatform()
    {
        return new FirebirdInterbasePlatform();
    }

    /**
     * {@inheritdoc}
     * @return FirebirdInterbaseSchemaManager
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new FirebirdInterbaseSchemaManager($conn);
    }
}

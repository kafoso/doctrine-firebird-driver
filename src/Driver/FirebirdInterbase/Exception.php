<?php
namespace IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\Driver\AbstractDriverException;

class Exception extends AbstractDriverException
{
    /**
     * @param array $error
     *
     * @return Exception
     */
    public static function fromErrorInfo($error)
    {
        return new self($error['message'], null, $error['code']);
    }
}

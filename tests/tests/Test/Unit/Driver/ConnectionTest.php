<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Unit\Driver;

use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testQuote()
    {
        $connection = $this->_createConnectionThroughReflection();
        $this->assertSame("'key'", $connection->quote("key"));
        $this->assertSame("'''key'", $connection->quote("'key"));
    }

    /**
     * @return Connection
     */
    private function _createConnectionThroughReflection()
    {
        $reflectionClass = new \ReflectionClass(Connection::class);
        $connection = $reflectionClass->newInstanceWithoutConstructor();
        return $connection;
    }
}

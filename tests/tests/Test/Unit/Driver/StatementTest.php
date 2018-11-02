<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Unit\Driver;

use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Connection;
use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement;

class StatementTest extends \PHPUnit_Framework_TestCase
{
    public function testBasics()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "foo");
        $this->assertSame(['code' => 0, 'message' => null], $statement->errorInfo());
        $this->assertSame(0, $statement->columnCount());
        $this->assertSame(0, $statement->rowCount());
    }

    /**
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Cannot use \PDO::FETCH_INTO; fetching multiple rows into single object is impossible. Provided class was: \stdClass
     */
    public function testFetchAllThrowsExceptionWhenModeIsPdoFetchInto()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $object = new \stdClass;
        $statement->fetchAll(\PDO::FETCH_INTO, $object);
    }

    /**
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Cannot use \PDO::FETCH_OBJ; fetching multiple rows into single object is impossible. Provided class was: \stdClass
     */
    public function testFetchAllThrowsExceptionWhenModeIsPdoFetchObject()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $object = new \stdClass;
        $statement->fetchAll(\PDO::FETCH_OBJ, $object);
    }

    /**
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Fetch mode -1 not supported by this driver in Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement::fetchAll
     */
    public function testFetchAllThrowsExceptionWhenModeIsUnsupported()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $object = new \stdClass;
        $statement->fetchAll(-1, $object);
    }

    protected function _mockConnection(): Connection
    {
        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $connection;
    }
}

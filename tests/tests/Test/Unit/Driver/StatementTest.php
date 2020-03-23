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

    public function testFetchReturnsFalseWhenNoConnectionResourceExists()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $row = $statement->fetch();
        $this->assertSame(false, $row);
    }

    /**
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Fetch mode -1 not supported by this driver. Called in method Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement::fetch
     */
    public function testFetchThrowsExceptionWhenFetchModeIsUnsupported()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $statement->fetch(-1);
    }

    public function testFetchAllReturnsEmptyArrayWhenNoConnectionResourceExists()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $rows = $statement->fetchAll();
        $this->assertSame([], $rows);
    }

    /**
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Cannot use \PDO::FETCH_INTO; fetching multiple rows into single object is impossible. Fetch object is: \stdClass
     */
    public function testFetchAllThrowsExceptionWhenModeIsPdoFetchInto()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $object = new \stdClass;
        $statement->setFetchMode(\PDO::FETCH_INTO, $object);
        $statement->fetchAll();
    }

    /**
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Argument $fetchArgument must - when fetch mode is \PDO::FETCH_OBJ - be null or a string. Found: (integer) 1
     */
    public function testFetchAllThrowsExceptionWhenModeIsPdoFetchObjAndFetchObjectIsNotAString()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $object = new \stdClass;
        $statement->fetchAll(\PDO::FETCH_OBJ, 1);
    }

    /**
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Argument $fetchArgument must - when fetch mode is \PDO::FETCH_COLUMN - be an integer. Found: (string) "1"
     */
    public function testFetchAllThrowsExceptionWhenModeIsPdoFetchColumnAndFetchArgumentIsNotAnInteger()
    {
        $connection = $this->_mockConnection();
        $statement = new Statement($connection, "SELECT * FROM dummy");
        $object = new \stdClass;
        $statement->fetchAll(\PDO::FETCH_COLUMN, "1");
    }

    /**
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Fetch mode -1 not supported by this driver. Called through method Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement::fetchAll
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

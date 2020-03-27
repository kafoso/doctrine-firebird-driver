<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Driver\PDOStatement;
use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;

/**
 * @runTestsInSeparateProcesses
 */
class ConnectionTest extends AbstractIntegrationTest
{
    public function testBasics()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $this->assertInstanceOf(PDOConnection::class, $connection);
        $this->assertFalse($connection->requiresQueryForServerVersion());
        $this->assertInstanceOf(PDOStatement::class, $connection->prepare("SELECT 1 FROM RDB\$DATABASE"));
        $this->assertSame("'''foo'''", $connection->quote("'foo'"));
    }

    /**
     * @expectedException \PDOException
     * @expectedExceptionMessage SQLSTATE[IM001]: Driver does not support this function: driver does not support that attribute
     */
    public function testGetAttributeThrowsExceptionForUnknownAttribute()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $connection->getAttribute(-1);
    }

    /**
     * @expectedException \Doctrine\DBAL\Driver\PDOException
     * @expectedExceptionMessage SQLSTATE[IM001]: Driver does not support this function: driver does not support lastInsertId()
     */
    public function testLastInsertIdThrowsAnException()
    {
        $this->_entityManager->getConnection()->lastInsertId('ALBUM_ID_SEQ');
    }

    public function testTransactionFlow()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();

        $this->assertFalse($connection->inTransaction());

        $connection->beginTransaction();
        $this->assertTrue($connection->inTransaction());

        $connection->commit();
        $this->assertFalse($connection->inTransaction());
    }
}

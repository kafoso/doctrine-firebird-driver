<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Kafoso\DoctrineFirebirdDriver\Driver\AbstractFirebirdInterbaseDriver;
use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Connection;
use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement;
use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

class ConnectionTest extends AbstractIntegrationTest
{
    /**
     * @runInSeparateProcess
     */
    public function testBasics()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $this->assertInternalType("object", $connection);
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertNull($connection->getAttribute(-1));
        $this->assertSame(
            \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED,
            $connection->getAttribute(AbstractFirebirdInterbaseDriver::ATTR_DOCTRINE_DEFAULT_TRANS_ISOLATION_LEVEL)
        );
        $this->assertInternalType("resource", $connection->getInterbaseConnectionResource());
        $this->assertFalse($connection->requiresQueryForServerVersion());
        $this->assertInstanceOf(Statement::class, $connection->prepare("foo"));
        $this->assertSame("'''foo'''", $connection->quote("'foo'"));
        $this->assertInternalType(
            "string",
            $connection->getStartTransactionSql(\Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED)
        );
        $this->assertSame(
            "foo/3333:bar",
            Connection::generateConnectString([
                "host" => "foo",
                "dbname" => "bar",
                "port" => 3333,
            ])
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testLastInsertIdWorks()
    {
        $id = $this->_entityManager->getConnection()->lastInsertId('ALBUM_ID_SEQ');
        $this->assertSame(2, $id); // 2x ALBUM are inserted in database_setup.sql
        $albumA = new Entity\Album("Foo");
        $this->_entityManager->persist($albumA);
        $this->_entityManager->flush($albumA);
        $idA = $this->_entityManager->getConnection()->lastInsertId('ALBUM_ID_SEQ');
        $this->assertSame(3, $idA);
        $albumB = new Entity\Album("Foo");
        $this->_entityManager->persist($albumB);
        $this->_entityManager->flush($albumB);
        $idB = $this->_entityManager->getConnection()->lastInsertId('ALBUM_ID_SEQ');
        $this->assertSame(4, $idB);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument $name must be null or a string. Found: (integer) 42
     */
    public function testLastInsertIdThrowsExceptionWhenArgumentNameIsInvalid()
    {
        $this->_entityManager->getConnection()->lastInsertId(42);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessageRegExp /^Expects argument \$name to match regular expression '.+?'\. Found\: \(string\) "FOO_Ø"$/
     */
    public function testLastInsertIdThrowsExceptionWhenArgumentNameContainsInvalidCharacters()
    {
        $this->_entityManager->getConnection()->lastInsertId("FOO_Ø");
    }

    /**
     * @runInSeparateProcess
     * @dataProvider dataProvider_testGetStartTransactionSqlWorks
     */
    public function testGetStartTransactionSqlWorks($expected, $isolationLevel, $timeout)
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        if (is_int($timeout)) {
            $connection->setAttribute(AbstractFirebirdInterbaseDriver::ATTR_DOCTRINE_DEFAULT_TRANS_WAIT, $timeout);
        }
        $found = $connection->getStartTransactionSql($isolationLevel);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetStartTransactionSqlWorks()
    {
        return [
            [
                "SET TRANSACTION READ WRITE ISOLATION LEVEL READ COMMITTED RECORD_VERSION WAIT LOCK TIMEOUT 5",
                \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED,
                null
            ],
            [
                "SET TRANSACTION READ WRITE ISOLATION LEVEL READ COMMITTED RECORD_VERSION WAIT LOCK TIMEOUT 5",
                \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED,
                null
            ],
            [
                "SET TRANSACTION READ WRITE ISOLATION LEVEL SNAPSHOT WAIT LOCK TIMEOUT 5",
                \Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ,
                null
            ],
            [
                "SET TRANSACTION READ WRITE ISOLATION LEVEL SNAPSHOT TABLE STABILITY WAIT LOCK TIMEOUT 5",
                \Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE,
                null
            ],
            [
                "SET TRANSACTION READ WRITE ISOLATION LEVEL READ COMMITTED RECORD_VERSION WAIT LOCK TIMEOUT 1",
                \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED,
                1
            ],
            [
                "SET TRANSACTION READ WRITE ISOLATION LEVEL READ COMMITTED RECORD_VERSION WAIT",
                \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED,
                -1
            ],
            [
                "SET TRANSACTION READ WRITE ISOLATION LEVEL READ COMMITTED RECORD_VERSION NO WAIT",
                \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED,
                0
            ],
        ];
    }

    /**
     * @runInSeparateProcess
     * @expectedException Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception
     * @expectedExceptionMessage Isolation level -1 is not supported
     */
    public function testGetStartTransactionSqlThrowsExceptionWhenIsolationLevelIsNotSupported()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $connection->getStartTransactionSql(-1);
    }

    /**
     * @runInSeparateProcess
     */
    public function testBeginTransaction()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();

        $reflectionObject = new \ReflectionObject($connection);

        $reflectionPropertyIbaseTransactionLevel = $reflectionObject->getProperty("_ibaseTransactionLevel");
        $reflectionPropertyIbaseTransactionLevel->setAccessible(true);
        $level = $reflectionPropertyIbaseTransactionLevel->getValue($connection);

        $reflectionPropertyIbaseTransactionLevel = $reflectionObject->getProperty("_ibaseTransactionLevel");
        $reflectionPropertyIbaseTransactionLevel->setAccessible(true);
        $level = $reflectionPropertyIbaseTransactionLevel->getValue($connection);
        $reflectionPropertyIbaseActiveTransaction = $reflectionObject->getProperty("_ibaseActiveTransaction");
        $reflectionPropertyIbaseActiveTransaction->setAccessible(true);
        $transactionA = $reflectionPropertyIbaseActiveTransaction->getValue($connection);
        $this->assertSame(0, $level);
        $this->assertInternalType("resource", $transactionA);

        $connection->beginTransaction();
        $level = $reflectionPropertyIbaseTransactionLevel->getValue($connection);
        $transactionB = $reflectionPropertyIbaseActiveTransaction->getValue($connection);
        $this->assertSame(1, $level);
        $this->assertInternalType("resource", $transactionB);
        $this->assertNotSame($transactionA, $transactionB);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Argument $params must contain non-empty "host" and "dbname"
     */
    public function testGenerateConnectStringThrowsExceptionWhenArrayIsMalformed()
    {
        Connection::generateConnectString([]);
    }
}

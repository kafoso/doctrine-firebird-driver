<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\DBAL\Database;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;

class TransactionTest extends AbstractIntegrationTest
{
    public function testCanSuccessfullyCommitASingleTransaction()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $connection->exec("CREATE TABLE {$tableName} (id INTEGER DEFAULT 0 NOT NULL)");
        $connection->beginTransaction();
        $connection->exec("INSERT INTO {$tableName} (id) VALUES (42)");
        $connection->commit();
        $result = $connection->query("SELECT id FROM {$tableName} WHERE id = 42");
        $value = $result->fetchColumn();
        $this->assertSame(42, $value);
    }

    public function testNestedTransactions()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $connection->exec("CREATE TABLE {$tableName} (id INTEGER DEFAULT 0 NOT NULL)");
        $expectedTransactionLevel = 0;
        foreach ([42, 43, 44] as $id) {
            $connection->beginTransaction();
            $expectedTransactionLevel++;
            $this->assertSame($expectedTransactionLevel, $connection->getTransactionNestingLevel(), "Expected transaction level");
            $connection->exec("INSERT INTO {$tableName} (id) VALUES ($id)");
            $result = $connection->query("SELECT COUNT(id) FROM {$tableName}");
            $count = $result->fetchColumn();
            $this->assertSame($count, $connection->getTransactionNestingLevel(), "Count vs expected transaction level");
        }
        $connection->rollback();
        $result = $connection->query("SELECT COUNT(id) FROM {$tableName}");
        $count = $result->fetchColumn();
        $this->assertSame(2, $connection->getTransactionNestingLevel(), "Transaction level, 3rd");
        $this->assertSame(2, $count, "Count, 3rd");
        $connection->rollback();
        $result = $connection->query("SELECT COUNT(id) FROM {$tableName}");
        $count = $result->fetchColumn();
        $this->assertSame(1, $connection->getTransactionNestingLevel(), "Transaction level, 2nd");
        $this->assertSame(1, $count, "Count, 2nd");
        $connection->commit();
        $result = $connection->query("SELECT COUNT(id) FROM {$tableName}");
        $count = $result->fetchColumn();
        $this->assertSame(0, $connection->getTransactionNestingLevel(), "Transaction level, 1st");
        $this->assertSame(1, $count, "Count, 1st");
    }

    public function testCanSuccessfullyRollbackASingleTransaction()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $connection->exec("CREATE TABLE {$tableName} (id INTEGER DEFAULT 0 NOT NULL)");
        $connection->beginTransaction();
        $connection->exec("INSERT INTO {$tableName} (id) VALUES (42)");
        $connection->rollback();
        $result = $connection->query("SELECT id FROM {$tableName} WHERE id = 42");
        $value = $result->fetchColumn();
        $this->assertFalse($value);
    }
}

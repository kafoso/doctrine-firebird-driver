<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\DBAL\Database\Table;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement;

class CreateTest extends AbstractIntegrationTest
{
    public function testCreateTable()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $table->addColumn('foo', 'string', ['notnull' => false, 'length' => 255]);
        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(1, $statements);
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }
        $sql = "SELECT 1 FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = '{$tableName}'";
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $this->assertSame(1, $result->fetchColumn(), "Table creation failure. SQL: " . self::statementArrayToText($statements));
    }

    public function testCreateTableWithPrimaryKey()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(3, $statements);
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }

        $sql = (
            "SELECT 1
            FROM RDB\$INDICES IX
            LEFT JOIN RDB\$INDEX_SEGMENTS SG ON IX.RDB\$INDEX_NAME = SG.RDB\$INDEX_NAME
            LEFT JOIN RDB\$RELATION_CONSTRAINTS RC ON RC.RDB\$INDEX_NAME = IX.RDB\$INDEX_NAME
            WHERE RC.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
            AND RC.RDB\$RELATION_NAME = '{$tableName}'"
        );
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $this->assertSame(1, $result->fetchColumn(), "Primary key \"id\" not found");
    }

    public function testCreateTableWithPrimaryKeyAndAutoIncrement()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(3, $statements);
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }

        $triggerName = "{$tableName}_D2IT";
        $sql = "SELECT 1 FROM RDB\$TRIGGERS WHERE RDB\$TRIGGER_NAME = '{$triggerName}'";
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $this->assertSame(1, $result->fetchColumn(), "Trigger creation failure. SQL: " . self::statementArrayToText($statements));

        $sequenceName = "{$tableName}_D2IS";
        foreach ([1, 2] as $id) {
            $sql = "SELECT NEXT VALUE FOR {$sequenceName} FROM RDB\$DATABASE;";
            $result = $connection->query($sql);
            $this->assertInstanceOf(Statement::class, $result);
            $this->assertSame($id, $result->fetchColumn(), "Incorrect autoincrement value");
        }
    }

    public function testCreateTableWithIndex()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $table->addColumn('foo', 'integer');
        $table->addIndex(["foo"]);
        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(2, $statements);
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }
        preg_match('/^CREATE INDEX (IDX_.+?) /', $statements[1], $match);
        $this->assertNotEmpty($match, "Invalid match against \$statements[1]: {$statements[1]}");
        $indexName = $match[1];

        $sql = (
            "SELECT 1
            FROM RDB\$INDICES IX
            LEFT JOIN RDB\$INDEX_SEGMENTS SG ON IX.RDB\$INDEX_NAME = SG.RDB\$INDEX_NAME
            LEFT JOIN RDB\$RELATION_CONSTRAINTS RC ON RC.RDB\$INDEX_NAME = IX.RDB\$INDEX_NAME
            WHERE IX.RDB\$UNIQUE_FLAG IS NULL
            AND IX.RDB\$INDEX_NAME = '{$indexName}'
            AND IX.RDB\$RELATION_NAME STARTING WITH '{$tableName}'
            AND SG.RDB\$FIELD_NAME = 'FOO'"
        );
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $this->assertSame(1, $result->fetchColumn(), "Index creation failure. SQL: " . self::statementArrayToText($statements));
    }

    public function testCreateTableWithUniqueIndex()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $table->addColumn('foo', 'integer');
        $table->addUniqueIndex(["foo"]);
        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(2, $statements);
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }
        preg_match('/^CREATE UNIQUE INDEX (UNIQ_.+?) /', $statements[1], $match);
        $this->assertNotEmpty($match, "Invalid match against \$statements[1]: {$statements[1]}");
        $indexName = $match[1];

        $sql = (
            "SELECT 1
            FROM RDB\$INDICES IX
            LEFT JOIN RDB\$INDEX_SEGMENTS SG ON IX.RDB\$INDEX_NAME = SG.RDB\$INDEX_NAME
            LEFT JOIN RDB\$RELATION_CONSTRAINTS RC ON RC.RDB\$INDEX_NAME = IX.RDB\$INDEX_NAME
            WHERE IX.RDB\$UNIQUE_FLAG = 1
            AND IX.RDB\$INDEX_NAME = '{$indexName}'
            AND IX.RDB\$RELATION_NAME STARTING WITH '{$tableName}'
            AND SG.RDB\$FIELD_NAME = 'FOO'"
        );
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $this->assertSame(1, $result->fetchColumn(), "Unique index creation failure. SQL: " . self::statementArrayToText($statements));
    }

    public function testCreateTableWithCommentedColumn()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $comment = 'Lorem ipsum';
        $table->addColumn('foo', 'integer', ['comment' => $comment]);
        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(2, $statements);
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }

        $sql = (
            "SELECT 1
            FROM RDB\$FIELDS F
            JOIN RDB\$RELATION_FIELDS RF ON RF.RDB\$FIELD_SOURCE = F.RDB\$FIELD_NAME
            WHERE RF.RDB\$RELATION_NAME = '{$tableName}'
            AND RF.RDB\$FIELD_NAME = 'FOO'
            AND RF.RDB\$DESCRIPTION = '{$comment}'"
        );
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $this->assertSame(1, $result->fetchColumn(), "Comment creation failure. SQL: " . self::statementArrayToText($statements));
    }
}

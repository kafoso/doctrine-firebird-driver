<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\DBAL\Database\Table;

use IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement;
use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Schema\FirebirdInterbaseSchemaManager;

class AlterTest extends AbstractIntegrationTest
{
    public function testAlterTable()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $sql = "CREATE TABLE {$tableName} (foo INTEGER DEFAULT 0 NOT NULL)";
        $connection->exec($sql);

        $tableDiff = new \Doctrine\DBAL\Schema\TableDiff($tableName);
        $tableDiff->changedColumns['foo'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'foo',
            new \Doctrine\DBAL\Schema\Column(
                'bar',
                \Doctrine\DBAL\Types\Type::getType('string')
            ),
            ['type']
        );
        $statements = $this->_platform->getAlterTableSQL($tableDiff);
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
            AND F.RDB\$FIELD_TYPE = " . FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR
        );
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $this->assertSame(1, $result->fetchColumn(), "Column change failed. SQL: " . self::statementArrayToText($statements));
    }
}

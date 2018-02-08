<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\DBAL\Database\Table\Column;

use Doctrine\DBAL\Schema\Comparator;
use IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement;
use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Schema\FirebirdInterbaseSchemaManager;

class CreateWithColumnsTest extends AbstractIntegrationTest
{
    /**
     * @dataProvider dataProvider_testCreateTableWithVariousColumnOptionCombinations
     */
    public function testCreateTableWithVariousColumnOptionCombinations(
        $inputFieldType,
        $expectedFieldType,
        array $options
    )
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__ . json_encode(func_get_args())), 0, 12));
        $columnTypeName = FirebirdInterbaseSchemaManager::getFieldTypeIdToColumnTypeMap()[$inputFieldType];

        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $table->addColumn('foo', $columnTypeName, $options);

        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(1, $statements);
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }

        $sql = (
            "SELECT *
            FROM RDB\$FIELDS F
            JOIN RDB\$RELATION_FIELDS RF ON RF.RDB\$FIELD_SOURCE = F.RDB\$FIELD_NAME
            WHERE RF.RDB\$RELATION_NAME = '{$tableName}'
            AND RF.RDB\$FIELD_NAME = 'FOO'"
        );
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $row = $result->fetch();
        $this->assertInternalType('array', $row);
        $this->assertArrayHasKey('RDB$FIELD_TYPE', $row);
        $this->assertSame($expectedFieldType, $row['RDB$FIELD_TYPE'], "Invalid field type.");

        if (isset($options['notnull'])) {
            $this->assertSame($options['notnull'], boolval(intval($row['RDB$NULL_FLAG_01'])), "Invalid notnull.");
        }
        if (isset($options['length'])) {
            $this->assertSame($options['length'], intval($row['RDB$CHARACTER_LENGTH']), "Invalid length.");
        }
        if (isset($options['default'])) {
            /**
             * Use RF.RDB$DEFAULT_SOURCE instead of RF.RDB$DEFAULT_VALUE becuase the latter is binary.
             */
            $default = $options['default'];
            switch ($expectedFieldType) {
                case FirebirdInterbaseSchemaManager::META_FIELD_TYPE_DOUBLE:
                case FirebirdInterbaseSchemaManager::META_FIELD_TYPE_FLOAT:
                    $default = strval($default);
                    break;
            }
            if (is_string($default)) {
                $default = "'{$default}'";
            }
            $expected = "DEFAULT {$default}";
            $this->assertSame($expected, $row['RDB$DEFAULT_SOURCE_01'], "Invalid default.");
        }
    }

    public function dataProvider_testCreateTableWithVariousColumnOptionCombinations()
    {
        return [
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_CHAR,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_CHAR,
                ['length' => 11, 'fixed' => true],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                [],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                ['notnull' => false],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                ['notnull' => true],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                ['fixed' => false],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                ['default' => 'Lorem'],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR,
                ['notnull' => true, 'length' => 300, 'default' => "Lorem ''opsum''"],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_DATE,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_DATE,
                ['notnull' => true, 'default' => '2018-01-01'],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_TIME,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_TIME,
                ['notnull' => true, 'default' => '13:37:00'],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_FLOAT,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_DOUBLE,
                ['notnull' => true, 'default' => 3.14],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_SHORT,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_SHORT,
                ['notnull' => true, 'default' => 3],
            ],
            [
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_LONG,
                FirebirdInterbaseSchemaManager::META_FIELD_TYPE_INT64,
                ['notnull' => true, 'default' => 3],
            ],
        ];
    }

    public function testCreateTableWithManyDifferentColumns()
    {
        $connection = $this->_entityManager->getConnection();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));

        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $columns = [];
        $columns[] = $table->addColumn('char_a', 'string', ['length' => 11, 'fixed' => true]);
        $columns[] = $table->addColumn('smallint_a', 'smallint');
        $columns[] = $table->addColumn('smallint_b', 'smallint', ['notnull' => true]);
        $columns[] = $table->addColumn('smallint_c', 'smallint', ['default' => 3]);
        $columns[] = $table->addColumn('smallint_d', 'smallint', ['autoincrement' => true]);
        $columns[] = $table->addColumn('varchar_a', 'string');
        $columns[] = $table->addColumn('varchar_b', 'string', ['notnull' => true,]);
        $columns[] = $table->addColumn('varchar_c', 'string', ['length' => 300]);
        $columns[] = $table->addColumn('varchar_d', 'string', ['default' => 'Lorem']);

        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(3, $statements);
        foreach ($statements as $statement) {
            $connection->exec($statement);
        }

        $sql = (
            "SELECT *
            FROM RDB\$FIELDS F
            JOIN RDB\$RELATION_FIELDS RF ON RF.RDB\$FIELD_SOURCE = F.RDB\$FIELD_NAME
            WHERE RF.RDB\$RELATION_NAME = '{$tableName}'"
        );
        $result = $connection->query($sql);
        $this->assertInstanceOf(Statement::class, $result);
        $rows = $result->fetchAll();
        $this->assertInternalType('array', $rows);
        $this->assertCount(count($columns), $rows, 'Row count does not match column count');

        $columnsIndexed = [];
        foreach ($columns as $column) {
            $columnsIndexed[strtoupper($column->getName())] = $column;
        }

        foreach ($rows as $row) {
            $fieldName = trim($row['RDB$FIELD_NAME_01']);
            $this->assertArrayHasKey($fieldName, $columnsIndexed);
            $column = $columnsIndexed[$fieldName];
            $this->assertArrayHasKey('RDB$FIELD_TYPE', $row);
            $expectedType = null;
            $expectedLength = $column->getLength();
            $expectedPrecision = $column->getPrecision();
            $expectedFixed = $column->getFixed();
            $expectedDefault = $column->getDefault();
            switch (get_class($column->getType())) {
                case 'Doctrine\DBAL\Types\SmallIntType':
                    $expectedType = FirebirdInterbaseSchemaManager::META_FIELD_TYPE_SHORT;
                    if (10 === $expectedPrecision) {
                        $expectedPrecision = 0;
                    }
                    if (null !== $expectedDefault) {
                        $expectedDefault = strval($expectedDefault);
                    }
                    break;
                case 'Doctrine\DBAL\Types\StringType':
                    $expectedType = FirebirdInterbaseSchemaManager::META_FIELD_TYPE_VARCHAR;
                    if ($column->getFixed()) {
                        $expectedType = FirebirdInterbaseSchemaManager::META_FIELD_TYPE_CHAR;
                    }
                    if (null === $expectedLength) {
                        $expectedLength = 255;
                    }
                    if (10 === $expectedPrecision) {
                        $expectedPrecision = null;
                    }
                    break;
            }
            $this->assertSame($expectedType, $row['RDB$FIELD_TYPE'], "Invalid field type.");
            $this->assertSame($expectedLength, $row['RDB$CHARACTER_LENGTH'], 'Invalid length');
            $this->assertSame($expectedPrecision, $row['RDB$FIELD_PRECISION'], 'Invalid precision');
            $this->assertSame($column->getScale(), $row['RDB$FIELD_SCALE'], 'Invalid scale');
            $this->assertSame($expectedFixed, ($expectedType == FirebirdInterbaseSchemaManager::META_FIELD_TYPE_CHAR), 'Invalid fixed');
            $this->assertSame($column->getNotnull(), boolval($row['RDB$NULL_FLAG_01']), 'Invalid notnull');

            $expectedDefaultSource = $expectedDefault;
            if (null !== $expectedDefaultSource) {
                switch ($expectedType) {
                    case FirebirdInterbaseSchemaManager::META_FIELD_TYPE_DOUBLE:
                    case FirebirdInterbaseSchemaManager::META_FIELD_TYPE_FLOAT:
                        $expectedDefaultSource = strval($expectedDefaultSource);
                        break;
                    case FirebirdInterbaseSchemaManager::META_FIELD_TYPE_INT64:
                    case FirebirdInterbaseSchemaManager::META_FIELD_TYPE_LONG:
                    case FirebirdInterbaseSchemaManager::META_FIELD_TYPE_SHORT:
                        $expectedDefaultSource = intval($expectedDefaultSource);
                        break;
                }
            }
            if (is_null($expectedDefaultSource)) {
                // Do nothing
            } elseif (is_string($expectedDefaultSource)) {
                $expectedDefaultSource = "DEFAULT '{$expectedDefaultSource}'";
            } else {
                $expectedDefaultSource = "DEFAULT {$expectedDefaultSource}";
            }
            // Use RF.RDB$DEFAULT_SOURCE instead of RF.RDB$DEFAULT_VALUE becuase the latter is binary.
            $this->assertSame($expectedDefaultSource, $row['RDB$DEFAULT_SOURCE_01'], 'Invalid default');
        }
    }
}

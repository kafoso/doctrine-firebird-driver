<?php
namespace IST\DoctrineFirebirdDriver\Test\Unit\Platforms;

use IST\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;

/**
 * Tests SQL generation. For functional tests, see FirebirdInterbasePlatformTest.
 * Inspired by:
 * @link https://github.com/ISTDK/doctrine-dbal/blob/master/tests/Doctrine/Tests/DBAL/Platforms/OraclePlatformTest.php
 */
class FirebirdInterbasePlatformSQLTest extends AbstractFirebirdInterbasePlatformTest
{
    public function testGetBitAndComparisonExpression()
    {
        $found = $this->_platform->getBitAndComparisonExpression(0, 1);
        $this->assertInternalType("string", $found);
        $this->assertSame("BIN_AND (0, 1)", $found);
    }

    public function testGetBitOrComparisonExpression()
    {
        $found = $this->_platform->getBitOrComparisonExpression(0, 1);
        $this->assertInternalType("string", $found);
        $this->assertSame("BIN_OR (0, 1)", $found);
    }

    public function testGetDateAddDaysExpression()
    {
        $found = $this->_platform->getDateAddDaysExpression('2018-01-01', 1);
        $this->assertInternalType("string", $found);
        $this->assertSame("DATEADD(1 DAY TO 2018-01-01)", $found);
    }

    public function testGetDateAddMonthExpression()
    {
        $found = $this->_platform->getDateAddMonthExpression('2018-01-01', 1);
        $this->assertInternalType("string", $found);
        $this->assertSame("DATEADD(1 MONTH TO 2018-01-01)", $found);
    }

    /**
     * @dataProvider dataProvider_testGetDateArithmeticIntervalExpression
     */
    public function testGetDateArithmeticIntervalExpression($expected, $operator, $interval, $unit)
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getDateArithmeticIntervalExpression');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, '2018-01-01', $operator, $interval, $unit);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetDateArithmeticIntervalExpression()
    {
        return [
            ["DATEADD(DAY, 1, 2018-01-01)", "", 1, FirebirdInterbasePlatform::DATE_INTERVAL_UNIT_DAY],
            ["DATEADD(DAY, -1, 2018-01-01)", "-", 1, FirebirdInterbasePlatform::DATE_INTERVAL_UNIT_DAY],
            ["DATEADD(MONTH, 1, 2018-01-01)", "", 1, FirebirdInterbasePlatform::DATE_INTERVAL_UNIT_MONTH],
            ["DATEADD(MONTH, 3, 2018-01-01)", "", 1, FirebirdInterbasePlatform::DATE_INTERVAL_UNIT_QUARTER],
            ["DATEADD(MONTH, -3, 2018-01-01)", "-", 1, FirebirdInterbasePlatform::DATE_INTERVAL_UNIT_QUARTER],
        ];
    }

    public function testGetDateDiffExpression()
    {
        $found = $this->_platform->getDateDiffExpression('2018-01-01', '2017-01-01');
        $this->assertInternalType("string", $found);
        $this->assertSame("DATEDIFF(day, 2017-01-01,2018-01-01)", $found);
    }

    public function testGetDateSubDaysExpression()
    {
        $found = $this->_platform->getDateSubDaysExpression('2018-01-01', 1);
        $this->assertInternalType("string", $found);
        $this->assertSame("DATEADD(-1 DAY TO 2018-01-01)", $found);
    }

    public function testGetDateSubMonthExpression()
    {
        $found = $this->_platform->getDateSubMonthExpression('2018-01-01', 1);
        $this->assertInternalType("string", $found);
        $this->assertSame("DATEADD(-1 MONTH TO 2018-01-01)", $found);
    }

    /**
     * @dataProvider dataProvider_testGetLocateExpression
     */
    public function testGetLocateExpression($expected, $startPos)
    {
        $found = $this->_platform->getLocateExpression("foo", "o", $startPos);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetLocateExpression()
    {
        return [
            ["POSITION (o in foo)", false],
            ["POSITION (o, foo, 1)", 1],
        ];
    }

    public function testGetRegexpExpression()
    {
        $this->assertInternalType("string", $this->_platform->getRegexpExpression());
        $this->assertSame("SIMILAR TO", $this->_platform->getRegexpExpression());
    }

    public function testGetCreateViewSQL()
    {
        $found = $this->_platform->getCreateViewSQL('foo', 'bar');
        $this->assertInternalType("string", $found);
        $this->assertSame("CREATE VIEW foo AS bar", $found);
    }

    public function testGetDropViewSQL()
    {
        $found = $this->_platform->getDropViewSQL('foo');
        $this->assertInternalType("string", $found);
        $this->assertSame("DROP VIEW foo", $found);
    }

    public function testGeneratesSqlSnippets()
    {
        $this->assertEquals('"', $this->_platform->getIdentifierQuoteCharacter());
        $this->assertEquals('column1 || column2 || column3', $this->_platform->getConcatExpression('column1', 'column2', 'column3'));
    }

    public function testGetDropTableSQL()
    {
        $found = $this->_platform->getDropTableSQL('foo');
        $this->assertInternalType("string", $found);
        $this->assertStringStartsWith('EXECUTE BLOCK AS', $found);
        $this->assertContains('DROP TRIGGER foo_D2IT', $found);
        $this->assertContains('DROP TABLE foo', $found);
    }

    public function testGeneratesTypeDeclarationForIntegers()
    {
        $this->assertEquals(
            'INTEGER',
            $this->_platform->getIntegerTypeDeclarationSQL([])
        );
        $this->assertEquals(
            'INTEGER',
            $this->_platform->getIntegerTypeDeclarationSQL([
                'autoincrement' => true,
            ])
        );
        $this->assertEquals(
            'INTEGER',
            $this->_platform->getIntegerTypeDeclarationSQL(
                [
                    'autoincrement' => true,
                    'primary' => true,
                ]
            )
        );
    }

    public function testGeneratesTypeDeclarationsForStrings()
    {
        $this->assertEquals(
            'CHAR(10)',
            $this->_platform->getVarcharTypeDeclarationSQL([
                'length' => 10,
                'fixed' => true,
            ])
        );
        $this->assertEquals(
            'VARCHAR(50)',
            $this->_platform->getVarcharTypeDeclarationSQL(['length' => 50])
        );
        $this->assertEquals(
            'VARCHAR(255)',
            $this->_platform->getVarcharTypeDeclarationSQL([])
        );
    }

    /**
     * @group DBAL-1097
     *
     * @dataProvider dataProvider_testGeneratesAdvancedForeignKeyOptionsSQL
     */
    public function testGeneratesAdvancedForeignKeyOptionsSQL($expected, array $options)
    {
        $foreignKey = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(
            ['foo'],
            'foreign_table',
            ['bar'],
            null,
            $options
        );
        $this->assertSame($expected, $this->_platform->getAdvancedForeignKeyOptionsSQL($foreignKey));
    }

    /**
     * @return array
     */
    public function dataProvider_testGeneratesAdvancedForeignKeyOptionsSQL()
    {
        // XXX Ensure this is correct cf. actual SQL
        return [
            ['', []],
            [' ON UPDATE CASCADE', ['onUpdate' => 'CASCADE']],
            [' ON DELETE CASCADE', ['onDelete' => 'CASCADE']],
            [' ON DELETE NO ACTION', ['onDelete' => 'NO ACTION']],
            [' ON DELETE RESTRICT', ['onDelete' => 'RESTRICT']],
            [' ON UPDATE SET NULL ON DELETE SET NULL', ['onUpdate' => 'SET NULL', 'onDelete' => 'SET NULL']],
        ];
    }

    public function testModifyLimitQuery()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10, 0);
        $this->assertEquals('SELECT * FROM user ROWS 1 TO 10', $sql);
    }

    public function testModifyLimitQueryWithEmptyOffset()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', 10);
        $this->assertEquals('SELECT * FROM user ROWS 10', $sql);
    }

    public function testModifyLimitQueryWithEmptyLimit()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user', null, 10);
        $this->assertEquals('SELECT * FROM user ROWS 11 TO 9000000000000000000', $sql);
    }

    public function testModifyLimitQueryWithAscOrderBy()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user ORDER BY username ASC', 10);
        $this->assertEquals('SELECT * FROM user ORDER BY username ASC ROWS 10', $sql);
    }

    public function testModifyLimitQueryWithDescOrderBy()
    {
        $sql = $this->_platform->modifyLimitQuery('SELECT * FROM user ORDER BY username DESC', 10);
        $this->assertEquals('SELECT * FROM user ORDER BY username DESC ROWS 10', $sql);
    }

    public function testGenerateTableWithAutoincrement()
    {
        $columnName = strtoupper('id' . uniqid());
        $tableName = strtoupper('table' . uniqid());
        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $column = $table->addColumn($columnName, 'integer');
        $column->setAutoincrement(true);
        $statements = $this->_platform->getCreateTableSQL($table);
        //strip all the whitespace from the statements
        array_walk($statements, function(&$value){
            $value = preg_replace('/\s+/', ' ',$value);
        });
        $this->assertCount(3, $statements);
        $this->assertArrayHasKey(0, $statements);
        $this->assertSame("CREATE TABLE {$tableName} ({$columnName} INTEGER NOT NULL)", $statements[0]);
        $this->assertArrayHasKey(1, $statements);
        $this->assertRegExp('/^CREATE SEQUENCE TABLE[0-9A-Z]+_D2IS$/', $statements[1]);
        $this->assertArrayHasKey(2, $statements);
        $regex = '/^';
        $regex .= 'CREATE TRIGGER TABLE([0-9A-Z]+)_D2IT FOR TABLE\1';
        $regex .= ' BEFORE INSERT AS BEGIN IF \(\(NEW.ID([0-9A-Z]+) IS NULL\) OR \(NEW.ID\2 = 0\)\) THEN';
        $regex .= ' BEGIN NEW.ID\2 = NEXT VALUE FOR TABLE\1_D2IS; END END;';
        $regex .= '$/';
        $this->assertRegExp($regex, $statements[2]);
    }

    public function testGenerateTableWithMultiColumnUniqueIndex()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $table->addColumn('foo', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('bar', 'string', ['notnull' => false, 'length' => 255]);
        $table->addUniqueIndex(["foo", "bar"]);
        $statements = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(2, $statements);
        $this->assertArrayHasKey(0, $statements);
        $this->assertSame("CREATE TABLE test (foo VARCHAR(255) DEFAULT NULL, bar VARCHAR(255) DEFAULT NULL)", $statements[0]);
        $this->assertArrayHasKey(1, $statements);
        $this->assertRegExp('/^CREATE UNIQUE INDEX UNIQ_[0-9A-Z]+ ON test \(foo, bar\)$/', $statements[1]);
    }

    public function testGeneratesIndexCreationSql()
    {
        $indexDef = new \Doctrine\DBAL\Schema\Index('my_idx', array('user_name', 'last_login'));
        $found = $this->_platform->getCreateIndexSQL($indexDef, 'mytable');
        $expected = 'CREATE INDEX my_idx ON mytable (user_name, last_login)';
        $this->assertSame($expected, $found);
    }

    public function testGeneratesUniqueIndexCreationSql()
    {
        $indexDef = new \Doctrine\DBAL\Schema\Index('index_name', array('test', 'test2'), true);
        $found = $this->_platform->getCreateIndexSQL($indexDef, 'test');
        $expected = 'CREATE UNIQUE INDEX index_name ON test (test, test2)';
        $this->assertEquals($expected, $found);
    }

    /**
     * @group DBAL-472
     * @group DBAL-1001
     */
    public function testAlterTableNotNULL()
    {
        $tableDiff = new \Doctrine\DBAL\Schema\TableDiff('mytable');
        $tableDiff->changedColumns['foo'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'foo', new \Doctrine\DBAL\Schema\Column(
                'foo',
                \Doctrine\DBAL\Types\Type::getType('string'),
                [
                    'default' => 'bla',
                    'notnull' => true,
                ]
            ),
            ['type']
        );
        $tableDiff->changedColumns['bar'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'bar', new \Doctrine\DBAL\Schema\Column(
                'baz',
                \Doctrine\DBAL\Types\Type::getType('string'),
                [
                    'default' => 'bla',
                    'notnull' => true,
                ]
            ),
            ['type', 'notnull']
        );
        $tableDiff->changedColumns['metar'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'metar', new \Doctrine\DBAL\Schema\Column(
                'metar',
                \Doctrine\DBAL\Types\Type::getType('string'),
                [
                    'length' => 2000,
                    'notnull' => false,
                ]
            ),
            ['notnull']
        );
        $found = $this->_platform->getAlterTableSQL($tableDiff);
        $this->assertCount(6, $found);
        $this->assertArrayHasKey(0, $found);
        $this->assertEquals("ALTER TABLE mytable ALTER COLUMN foo TYPE VARCHAR(255)", $found[0]);
        $this->assertArrayHasKey(1, $found);
        $this->assertEquals("ALTER TABLE mytable ALTER foo SET DEFAULT 'bla'", $found[1]);
        $this->assertArrayHasKey(2, $found);
        $this->assertEquals("ALTER TABLE mytable ALTER COLUMN bar TYPE VARCHAR(255)", $found[2]);
        $this->assertArrayHasKey(3, $found);
        $this->assertEquals("ALTER TABLE mytable ALTER bar SET DEFAULT 'bla'", $found[3]);
        $this->assertArrayHasKey(4, $found);
        $this->assertEquals("UPDATE RDB\$RELATION_FIELDS SET RDB\$NULL_FLAG = 1 WHERE UPPER(RDB\$FIELD_NAME) = UPPER('bar') AND UPPER(RDB\$RELATION_NAME) = UPPER('mytable')", $found[4]);
        $this->assertArrayHasKey(5, $found);
        $this->assertEquals("UPDATE RDB\$RELATION_FIELDS SET RDB\$NULL_FLAG = NULL WHERE UPPER(RDB\$FIELD_NAME) = UPPER('metar') AND UPPER(RDB\$RELATION_NAME) = UPPER('mytable')", $found[5]);
    }

    public function testReturnsBinaryTypeDeclarationSQL()
    {
        $this->assertSame('VARCHAR(255)', $this->_platform->getBinaryTypeDeclarationSQL([]));
        $this->assertSame('VARCHAR(255)', $this->_platform->getBinaryTypeDeclarationSQL(['length' => 0]));
        $this->assertSame('VARCHAR(8190)', $this->_platform->getBinaryTypeDeclarationSQL(['length' => 8190]));
        $this->assertSame('BLOB', $this->_platform->getBinaryTypeDeclarationSQL(['length' => 8191]));
        $this->assertSame('CHAR(255)', $this->_platform->getBinaryTypeDeclarationSQL(['fixed' => true]));
        $this->assertSame('CHAR(255)', $this->_platform->getBinaryTypeDeclarationSQL(['fixed' => true, 'length' => 0]));
        $this->assertSame('CHAR(8190)', $this->_platform->getBinaryTypeDeclarationSQL(['fixed' => true, 'length' => 8190]));
        $this->assertSame('BLOB', $this->_platform->getBinaryTypeDeclarationSQL(['fixed' => true, 'length' => 8191]));
    }

    /** XXX Fix me https://github.com/ISTDK/doctrine-dbal/blob/master/tests/Doctrine/Tests/DBAL/Platforms/OraclePlatformTest.php#L422
    public function testDoesNotPropagateUnnecessaryTableAlterationOnBinaryType()
    {
        $table1 = new \Doctrine\DBAL\Schema\Table('mytable');
        $table1->addColumn('column_varbinary', 'binary');
        $table1->addColumn('column_binary', 'binary', array('fixed' => true));
        $table2 = new \Doctrine\DBAL\Schema\Table('mytable');
        $table2->addColumn('column_varbinary', 'binary', array('fixed' => true));
        $table2->addColumn('column_binary', 'binary');
        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $found = $this->_platform->getAlterTableSQL($comparator->diffTable($table1, $table2));
        echo "<pre>";var_dump("kafoso [] ".__FILE__."::".__LINE__, $found);die("</pre>");
        $this->assertEmpty($found);
    }
    */

    public function testGetCreateAutoincrementSql()
    {
        $found = $this->_platform->getCreateAutoincrementSql("bar", "foo");
        $this->assertInternalType("array", $found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame("CREATE SEQUENCE foo_D2IS", $found[0]);
        $this->assertArrayHasKey(1, $found);
        $this->assertStringStartsWith("CREATE TRIGGER foo_D2IT FOR foo", $found[1]);
        $this->assertContains("NEW.bar = NEXT VALUE FOR foo_D2IS;", $found[1]);
    }

    public function testGetDropAutoincrementSql()
    {
        $found = $this->_platform->getDropAutoincrementSql("foo");
        $this->assertInternalType("array", $found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame("DROP TRIGGER FOO_AI_PK", $found[0]);
        $this->assertArrayHasKey(1, $found);
        $this->assertStringStartsWith("EXECUTE BLOCK", $found[1]);
        $this->assertContains("DROP TRIGGER FOO_D2IT", $found[1]);
        $this->assertContains("DROP SEQUENCE FOO_D2IS", $found[1]);
        $this->assertSame("ALTER TABLE FOO DROP CONSTRAINT FOO_AI_PK", $found[2]);
    }

    /**
     * @group DBAL-1004
     */
    public function testAltersTableColumnCommentWithExplicitlyQuotedIdentifiers()
    {
        $table1 = new \Doctrine\DBAL\Schema\Table(
            '"foo"',
            [
                new \Doctrine\DBAL\Schema\Column(
                    '"bar"',
                    \Doctrine\DBAL\Types\Type::getType('integer')
                )
            ]
        );
        $table2 = new \Doctrine\DBAL\Schema\Table(
            '"foo"',
            [
                new \Doctrine\DBAL\Schema\Column(
                    '"bar"',
                    \Doctrine\DBAL\Types\Type::getType('integer'),
                    [
                        'comment' => 'baz',
                    ]
                )
            ]
        );
        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $tableDiff = $comparator->diffTable($table1, $table2);
        $this->assertInstanceOf('Doctrine\DBAL\Schema\TableDiff', $tableDiff);
        $this->assertSame(
            [
                'COMMENT ON COLUMN "foo"."bar" IS \'baz\'',
            ],
            $this->_platform->getAlterTableSQL($tableDiff)
        );
    }

    public function testQuotedTableNames()
    {
        $table = new \Doctrine\DBAL\Schema\Table('"test"');
        $table->addColumn('"id"', 'integer', ['autoincrement' => true]);
        $this->assertTrue($table->isQuoted());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals('"test"', $table->getQuotedName($this->_platform));
        $sql = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(3, $sql);
        $this->assertArrayHasKey(0, $sql);
        $this->assertEquals('CREATE TABLE "test" ("id" INTEGER NOT NULL)', $sql[0]);
        $this->assertArrayHasKey(1, $sql);
        $this->assertEquals('CREATE SEQUENCE "test_D2IS"', $sql[1]);
        $this->assertArrayHasKey(2, $sql);
        $expectedCreateTrigger = preg_replace('/\s+/', ' ', trim('
            CREATE TRIGGER "test_D2IT" FOR "test"
                BEFORE INSERT
                AS
                BEGIN
                    IF ((NEW."id" IS NULL) OR
                        (NEW."id" = 0)) THEN
                    BEGIN
                        NEW."id" = NEXT VALUE FOR "test_D2IS";
                    END
                END;
        '));
        $this->assertEquals($expectedCreateTrigger, preg_replace('/\s+/', ' ', trim($sql[2])));
    }

    public function testGeneratesPartialIndexesSqlOnlyWhenSupportingPartialIndexes()
    {
        $where = 'test IS NULL AND test2 IS NOT NULL';
        $indexDef = new \Doctrine\DBAL\Schema\Index('name', ['test', 'test2'], false, false, [], ['where' => $where]);
        $uniqueIndex = new \Doctrine\DBAL\Schema\Index('name', ['test', 'test2'], true, false, [], ['where' => $where]);
        $expected = ' WHERE ' . $where;
        $actuals = [];
        $actuals[] = $this->_platform->getIndexDeclarationSQL('name', $indexDef);
        $actuals[] = $this->_platform->getUniqueConstraintDeclarationSQL('name', $uniqueIndex);
        $actuals[] = $this->_platform->getCreateIndexSQL($indexDef, 'table');
        foreach ($actuals as $actual) {
            if ($this->_platform->supportsPartialIndexes()) {
                $this->assertStringEndsWith($expected, $actual, 'WHERE clause should be present');
            } else {
                $this->assertStringEndsNotWith($expected, $actual, 'WHERE clause should NOT be present');
            }
        }
    }

    public function testGeneratesForeignKeyCreationSql()
    {
        $fk = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(['fk_name_id'], 'other_table', ['id'], '');
        $found = $this->_platform->getCreateForeignKeySQL($fk, 'test');
        $expected = 'ALTER TABLE test ADD FOREIGN KEY (fk_name_id) REFERENCES other_table (id)';
        $this->assertEquals($expected, $found);
    }

    public function testGeneratesConstraintCreationSql()
    {
        $idx = new \Doctrine\DBAL\Schema\Index('constraint_name', array('test'), true, false);
        $found = $this->_platform->getCreateConstraintSQL($idx, 'test');
        $expected = 'ALTER TABLE test ADD CONSTRAINT constraint_name UNIQUE (test)';
        $this->assertEquals($expected, $found);
        $pk = new \Doctrine\DBAL\Schema\Index('constraint_name', array('test'), true, true);
        $found = $this->_platform->getCreateConstraintSQL($pk, 'test');
        $expected = 'ALTER TABLE test ADD CONSTRAINT constraint_name PRIMARY KEY (test)';
        $this->assertEquals($expected, $found);
        $fk = new \Doctrine\DBAL\Schema\ForeignKeyConstraint(array('fk_name'), 'foreign', array('id'), 'constraint_fk');
        $found = $this->_platform->getCreateConstraintSQL($fk, 'test');
        $quotedForeignTable = $fk->getQuotedForeignTableName($this->_platform);
        $expected = "ALTER TABLE test ADD CONSTRAINT constraint_fk FOREIGN KEY (fk_name) REFERENCES {$quotedForeignTable} (id)";
        $this->assertEquals($expected, $found);
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     * @expectedExceptionMessage Operation 'IST\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform::getAlterTableSQL Cannot rename tables because firebird does not support it
     */
    public function testGeneratesTableAlterationSqlThrowsException()
    {
        $table = new \Doctrine\DBAL\Schema\Table('mytable');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foo', 'integer');
        $table->addColumn('bar', 'string');
        $table->addColumn('bloo', 'boolean');
        $table->setPrimaryKey(['id']);
        $tableDiff = new \Doctrine\DBAL\Schema\TableDiff('mytable');
        $tableDiff->fromTable = $table;
        $tableDiff->newName = 'userlist';
        $tableDiff->addedColumns['quota'] = new \Doctrine\DBAL\Schema\Column(
            'quota',
            \Doctrine\DBAL\Types\Type::getType('integer'),
            [
                'notnull' => false,
            ]
        );
        $tableDiff->removedColumns['foo'] = new \Doctrine\DBAL\Schema\Column(
            'foo',
            \Doctrine\DBAL\Types\Type::getType('integer')
        );
        $tableDiff->changedColumns['bar'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'bar', new \Doctrine\DBAL\Schema\Column(
                'baz',
                \Doctrine\DBAL\Types\Type::getType('string'),
                [
                    'default' => 'def',
                ]
            ),
            ['type', 'notnull', 'default']
        );
        $tableDiff->changedColumns['bloo'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'bloo', new \Doctrine\DBAL\Schema\Column(
                'bloo',
                \Doctrine\DBAL\Types\Type::getType('boolean'),
                [
                    'default' => false,
                ]
            ),
            ['type', 'notnull', 'default']
        );
        $this->_platform->getAlterTableSQL($tableDiff);
    }

    public function testGetCustomColumnDeclarationSql()
    {
        $field = ['columnDefinition' => 'bar'];
        $this->assertEquals('foo bar', $this->_platform->getColumnDeclarationSQL('foo', $field));
    }

    public function testGetCreateTableSqlDispatchEvent()
    {
        $listenerMock = $this
            ->getMockBuilder('GetCreateTableSqlDispatchEvenListener')
            ->disableOriginalConstructor()
            ->setMethods([
                'onSchemaCreateTable',
                'onSchemaCreateTableColumn'
            ])
            ->getMock();
        $listenerMock
            ->expects($this->once())
            ->method('onSchemaCreateTable');
        $listenerMock
            ->expects($this->exactly(2))
            ->method('onSchemaCreateTableColumn');
        $eventManager = new \Doctrine\Common\EventManager();
        $eventManager->addEventListener(
            [
                \Doctrine\DBAL\Events::onSchemaCreateTable,
                \Doctrine\DBAL\Events::onSchemaCreateTableColumn
            ],
            $listenerMock
        );
        $this->_platform->setEventManager($eventManager);
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $table->addColumn('foo', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('bar', 'string', ['notnull' => false, 'length' => 255]);
        $this->_platform->getCreateTableSQL($table);
    }

    public function testGetDropTableSqlDispatchEvent()
    {
        $listenerMock = $this
            ->getMockBuilder('GetDropTableSqlDispatchEventListener')
            ->disableOriginalConstructor()
            ->setMethods(['onSchemaDropTable'])
            ->getMock();
        $listenerMock
            ->expects($this->once())
            ->method('onSchemaDropTable');
        $eventManager = new \Doctrine\Common\EventManager();
        $eventManager->addEventListener([\Doctrine\DBAL\Events::onSchemaDropTable], $listenerMock);
        $this->_platform->setEventManager($eventManager);
        $this->_platform->getDropTableSQL('TABLE');
    }

    public function testGetAlterTableSqlDispatchEvent()
    {
        $listenerMock = $this
            ->getMockBuilder('GetAlterTableSqlDispatchEvenListener')
            ->disableOriginalConstructor()
            ->setMethods([
                'onSchemaAlterTable',
                'onSchemaAlterTableAddColumn',
                'onSchemaAlterTableRemoveColumn',
                'onSchemaAlterTableChangeColumn',
                'onSchemaAlterTableRenameColumn',
            ])
            ->getMock();
        $listenerMock
            ->expects($this->once())
            ->method('onSchemaAlterTable');
        $listenerMock
            ->expects($this->once())
            ->method('onSchemaAlterTableAddColumn');
        $listenerMock
            ->expects($this->once())
            ->method('onSchemaAlterTableRemoveColumn');
        $listenerMock
            ->expects($this->once())
            ->method('onSchemaAlterTableChangeColumn');
        $listenerMock
            ->expects($this->once())
            ->method('onSchemaAlterTableRenameColumn');
        $eventManager = new \Doctrine\Common\EventManager();
        $events = [
            \Doctrine\DBAL\Events::onSchemaAlterTable,
            \Doctrine\DBAL\Events::onSchemaAlterTableAddColumn,
            \Doctrine\DBAL\Events::onSchemaAlterTableRemoveColumn,
            \Doctrine\DBAL\Events::onSchemaAlterTableChangeColumn,
            \Doctrine\DBAL\Events::onSchemaAlterTableRenameColumn
        ];
        $eventManager->addEventListener($events, $listenerMock);
        $this->_platform->setEventManager($eventManager);
        $table = new \Doctrine\DBAL\Schema\Table('mytable');
        $table->addColumn('removed', 'integer');
        $table->addColumn('changed', 'integer');
        $table->addColumn('renamed', 'integer');
        $tableDiff = new \Doctrine\DBAL\Schema\TableDiff('mytable');
        $tableDiff->fromTable = $table;
        $tableDiff->addedColumns['added'] = new \Doctrine\DBAL\Schema\Column(
            'added',
            \Doctrine\DBAL\Types\Type::getType('integer'),
            []
        );
        $tableDiff->removedColumns['removed'] = new \Doctrine\DBAL\Schema\Column(
            'removed',
            \Doctrine\DBAL\Types\Type::getType('integer'),
            []
        );
        $tableDiff->changedColumns['changed'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'changed', new \Doctrine\DBAL\Schema\Column(
                'changed2',
                \Doctrine\DBAL\Types\Type::getType('string'),
                []
            ),
            []
        );
        $tableDiff->renamedColumns['renamed'] = new \Doctrine\DBAL\Schema\Column(
            'renamed2',
            \Doctrine\DBAL\Types\Type::getType('integer'),
            []
        );
        $this->_platform->getAlterTableSQL($tableDiff);
    }

    /**
     * @group DBAL-42
     */
    public function testCreateTableColumnComments()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $table->addColumn('id', 'integer', ['comment' => 'This is a comment']);
        $table->setPrimaryKey(['id']);
        $found = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(2, $found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame("CREATE TABLE test (id INTEGER NOT NULL, CONSTRAINT test_PK PRIMARY KEY (id))", $found[0]);
        $this->assertArrayHasKey(1, $found);
        $this->assertSame("COMMENT ON COLUMN test.id IS 'This is a comment'", $found[1]);
    }

    /**
     * @group DBAL-42
     */
    public function testAlterTableColumnComments()
    {
        $tableDiff = new \Doctrine\DBAL\Schema\TableDiff('mytable');
        $tableDiff->addedColumns['quota'] = new \Doctrine\DBAL\Schema\Column(
            'quota',
            \Doctrine\DBAL\Types\Type::getType('integer'),
            ['comment' => 'A comment']
        );
        $tableDiff->changedColumns['foo'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'foo', new \Doctrine\DBAL\Schema\Column(
                'foo',
                \Doctrine\DBAL\Types\Type::getType('string')
            ),
            ['comment']
        );
        $tableDiff->changedColumns['bar'] = new \Doctrine\DBAL\Schema\ColumnDiff(
            'bar', new \Doctrine\DBAL\Schema\Column(
                'baz',
                \Doctrine\DBAL\Types\Type::getType('string'),
                ['comment' => 'B comment']
            ),
            ['comment']
        );
        $found = $this->_platform->getAlterTableSQL($tableDiff);
        $this->assertCount(4, $found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame("ALTER TABLE mytable ADD quota INTEGER NOT NULL", $found[0]);
        $this->assertArrayHasKey(1, $found);
        $this->assertSame("COMMENT ON COLUMN mytable.quota IS 'A comment'", $found[1]);
        $this->assertArrayHasKey(2, $found);
        $this->assertSame("COMMENT ON COLUMN mytable.foo IS ''", $found[2]);
        $this->assertArrayHasKey(3, $found);
        $this->assertSame("COMMENT ON COLUMN mytable.baz IS 'B comment'", $found[3]);
    }

    public function testCreateTableColumnTypeComments()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $table->addColumn('id', 'integer');
        $table->addColumn('data', 'array');
        $table->setPrimaryKey(['id']);
        $found = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(2, $found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame("CREATE TABLE test (id INTEGER NOT NULL, data BLOB SUB_TYPE TEXT NOT NULL, CONSTRAINT test_PK PRIMARY KEY (id))", $found[0]);
        $this->assertArrayHasKey(1, $found);
        $this->assertSame("COMMENT ON COLUMN test.data IS '(DC2Type:array)'", $found[1]);
    }

    public function testGetDefaultValueDeclarationSQL()
    {
        // non-timestamp value will get single quotes
        $field = [
            'type' => 'string',
            'default' => 'non_timestamp'
        ];
        $this->assertEquals(" DEFAULT 'non_timestamp'", $this->_platform->getDefaultValueDeclarationSQL($field));
    }

    public function testGetDefaultValueDeclarationSQLDateTime()
    {
        // timestamps on datetime types should not be quoted
        foreach (['datetime', 'datetimetz'] as $type) {
            $field = [
                'type' => \Doctrine\DBAL\Types\Type::getType($type),
                'default' => $this->_platform->getCurrentTimestampSQL()
            ];
            $this->assertEquals(' DEFAULT ' . $this->_platform->getCurrentTimestampSQL(), $this->_platform->getDefaultValueDeclarationSQL($field));
        }
    }

    public function testGetDefaultValueDeclarationSQLForIntegerTypes()
    {
        foreach(array('bigint', 'integer', 'smallint') as $type) {
            $field = [
                'type'    => \Doctrine\DBAL\Types\Type::getType($type),
                'default' => 1
            ];
            $this->assertEquals(
                ' DEFAULT 1',
                $this->_platform->getDefaultValueDeclarationSQL($field)
            );
        }
    }

    /**
     * @group DBAL-374
     */
    public function testQuotedColumnInPrimaryKeyPropagation()
    {
        $table = new \Doctrine\DBAL\Schema\Table('`quoted`');
        $table->addColumn('create', 'string');
        $table->setPrimaryKey(['create']);
        $found = $this->_platform->getCreateTableSQL($table);
        $this->assertInternalType('array', $found);
        $this->assertCount(1, $found);
        $this->assertArrayHasKey(0, $found);
        $expected = 'CREATE TABLE "quoted" ("create" VARCHAR(255) NOT NULL, CONSTRAINT "quoted_PK" PRIMARY KEY ("create"))';
        $this->assertSame($expected, $found[0]);
    }


    /**
     * @group DBAL-374
     */
    public function testQuotedColumnInIndexPropagation()
    {
        $table = new \Doctrine\DBAL\Schema\Table('`quoted`');
        $table->addColumn('create', 'string');
        $table->addIndex(['create']);
        $found = $this->_platform->getCreateTableSQL($table);
        $this->assertCount(2, $found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame('CREATE TABLE "quoted" ("create" VARCHAR(255) NOT NULL)', $found[0]);
        $this->assertArrayHasKey(1, $found);
        $this->assertRegExp('/^CREATE INDEX IDX_[0-9A-F]+ ON "quoted" \("create"\)$/', $found[1]);
    }

    public function testQuotedNameInIndexSQL()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $table->addColumn('column1', 'string');
        $table->addIndex(['column1'], '`key`');
        $found = $this->_platform->getCreateTableSQL($table);
        $expected = [
            'CREATE TABLE test (column1 VARCHAR(255) NOT NULL)',
            'CREATE INDEX "key" ON test (column1)',
        ];
        $this->assertSame($expected, $found);
    }

    /**
     * @group DBAL-374
     */
    public function testQuotedColumnInForeignKeyPropagation()
    {
        $table = new \Doctrine\DBAL\Schema\Table('`quoted`');
        $table->addColumn('create', 'string');
        $table->addColumn('foo', 'string');
        $table->addColumn('`bar`', 'string');
        // Foreign table with reserved keyword as name (needs quotation).
        $foreignTable = new \Doctrine\DBAL\Schema\Table('foreign');
        $foreignTable->addColumn('create', 'string');    // Foreign column with reserved keyword as name (needs quotation).
        $foreignTable->addColumn('bar', 'string');       // Foreign column with non-reserved keyword as name (does not need quotation).
        $foreignTable->addColumn('`foo-bar`', 'string'); // Foreign table with special character in name (needs quotation on some platforms, e.g. Sqlite).
        $table->addForeignKeyConstraint(
            $foreignTable,
            ['create', 'foo', '`bar`'],
            ['create', 'bar', '`foo-bar`'],
            [],
            'FK_WITH_RESERVED_KEYWORD'
        );
        // Foreign table with non-reserved keyword as name (does not need quotation).
        $foreignTable = new \Doctrine\DBAL\Schema\Table('foo');
        $foreignTable->addColumn('create', 'string');    // Foreign column with reserved keyword as name (needs quotation).
        $foreignTable->addColumn('bar', 'string');       // Foreign column with non-reserved keyword as name (does not need quotation).
        $foreignTable->addColumn('`foo-bar`', 'string'); // Foreign table with special character in name (needs quotation on some platforms, e.g. Sqlite).
        $table->addForeignKeyConstraint(
            $foreignTable,
            ['create', 'foo', '`bar`'],
            ['create', 'bar', '`foo-bar`'],
            [],
            'FK_WITH_NON_RESERVED_KEYWORD'
        );
        // Foreign table with special character in name (needs quotation on some platforms, e.g. Sqlite).
        $foreignTable = new \Doctrine\DBAL\Schema\Table('`foo-bar`');
        $foreignTable->addColumn('create', 'string');    // Foreign column with reserved keyword as name (needs quotation).
        $foreignTable->addColumn('bar', 'string');       // Foreign column with non-reserved keyword as name (does not need quotation).
        $foreignTable->addColumn('`foo-bar`', 'string'); // Foreign table with special character in name (needs quotation on some platforms, e.g. Sqlite).
        $table->addForeignKeyConstraint(
            $foreignTable,
            ['create', 'foo', '`bar`'],
            ['create', 'bar', '`foo-bar`'],
            [],
            'FK_WITH_INTENDED_QUOTATION'
        );
        $found = $this->_platform->getCreateTableSQL($table, \Doctrine\DBAL\Platforms\AbstractPlatform::CREATE_FOREIGNKEYS);
        $this->assertCount(4, $found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame('CREATE TABLE "quoted" ("create" VARCHAR(255) NOT NULL, foo VARCHAR(255) NOT NULL, "bar" VARCHAR(255) NOT NULL)', $found[0]);
        $this->assertArrayHasKey(1, $found);
        $this->assertSame('ALTER TABLE "quoted" ADD CONSTRAINT FK_WITH_RESERVED_KEYWORD FOREIGN KEY ("create", foo, "bar") REFERENCES "foreign" ("create", bar, "foo-bar")', $found[1]);
        $this->assertArrayHasKey(2, $found);
        $this->assertSame('ALTER TABLE "quoted" ADD CONSTRAINT FK_WITH_NON_RESERVED_KEYWORD FOREIGN KEY ("create", foo, "bar") REFERENCES foo ("create", bar, "foo-bar")', $found[2]);
        $this->assertArrayHasKey(3, $found);
        $this->assertSame('ALTER TABLE "quoted" ADD CONSTRAINT FK_WITH_INTENDED_QUOTATION FOREIGN KEY ("create", foo, "bar") REFERENCES "foo-bar" ("create", bar, "foo-bar")', $found[3]);
    }

    /**
     * @group DBAL-1051
     */
    public function testQuotesReservedKeywordInUniqueConstraintDeclarationSQL()
    {
        $index = new \Doctrine\DBAL\Schema\Index('select', ['foo'], true);
        $found = $this->_platform->getUniqueConstraintDeclarationSQL('select', $index);
        $this->assertSame('CONSTRAINT "select" UNIQUE (foo)', $found);
    }

    /**
     * @group DBAL-1051
     */
    public function testQuotesReservedKeywordInIndexDeclarationSQL()
    {
        $index = new \Doctrine\DBAL\Schema\Index('select', ['foo']);
        $found = $this->_platform->getIndexDeclarationSQL('select', $index);
        $this->assertSame('INDEX "select" (foo)', $found);
    }
}

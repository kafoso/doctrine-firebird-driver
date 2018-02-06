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
        // XXX Ensure this is correct
        return [
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
}

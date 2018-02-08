<?php
namespace IST\DoctrineFirebirdDriver\Test\Unit\Platforms;

use IST\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;
use IST\DoctrineFirebirdDriver\Platforms\Keywords\FirebirdInterbaseKeywords;

/**
 * Tests primarily functional aspects of the platform class. For SQL tests, see FirebirdInterbasePlatformSQLTest.
 */
class FirebirdInterbasePlatformTest extends AbstractFirebirdInterbasePlatformTest
{
    public function testGetName()
    {
        $this->assertInternalType("string", $this->_platform->getName());
        $this->assertSame("FirebirdInterbase", $this->_platform->getName());
    }

    /**
     * FROM: @link https://github.com/ISTDK/doctrine-dbal/blob/master/tests/Doctrine/Tests/DBAL/Platforms/AbstractPlatformTestCase.php
     */

    public function testGetMaxIdentifierLength()
    {
        $this->assertInternalType("int", $this->_platform->getMaxIdentifierLength());
        $this->assertSame(31, $this->_platform->getMaxIdentifierLength());
    }

    public function testGetMaxConstraintIdentifierLength()
    {
        $this->assertInternalType("int", $this->_platform->getMaxConstraintIdentifierLength());
        $this->assertSame(27, $this->_platform->getMaxConstraintIdentifierLength());
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     * @expectedExceptionMessage Operation 'Identifier kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk is too long for firebird platform. Maximum identifier length is 31' is not supported by platform
     */
    public function testCheckIdentifierLengthThrowsExceptionWhenArgumentNameIsTooLong()
    {
        $this->_platform->checkIdentifierLength(str_repeat("k", 32), null);
    }

    public function testQuoteSql()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('quoteSql');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, 'foo');
        $this->assertInternalType("string", $found);
        $this->assertSame("'foo'", $found);
    }

    public function testQuoteIdentifier()
    {
        $c = $this->_platform->getIdentifierQuoteCharacter();
        $this->assertEquals($c."test".$c, $this->_platform->quoteIdentifier("test"));
        $this->assertEquals($c."test".$c.".".$c."test".$c, $this->_platform->quoteIdentifier("test.test"));
        $this->assertEquals(str_repeat($c, 4), $this->_platform->quoteIdentifier($c));
    }

    /**
     * @group DDC-1360
     */
    public function testQuoteSingleIdentifier()
    {
        $c = $this->_platform->getIdentifierQuoteCharacter();
        $this->assertEquals($c."test".$c, $this->_platform->quoteSingleIdentifier("test"));
        $this->assertEquals($c."test.test".$c, $this->_platform->quoteSingleIdentifier("test.test"));
        $this->assertEquals(str_repeat($c, 4), $this->_platform->quoteSingleIdentifier($c));
    }

    public function testGetInvalidForeignKeyReferentialActionSQLThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_platform->getForeignKeyReferentialActionSQL('unknown');
    }

    public function testGetUnknownDoctrineMappingTypeThrowsException()
    {
        $this->setExpectedException('Doctrine\DBAL\DBALException');
        $this->_platform->getDoctrineTypeMapping('foobar');
    }

    public function testRegisterDoctrineMappingType()
    {
        $this->_platform->registerDoctrineTypeMapping('foo', 'integer');
        $this->assertEquals('integer', $this->_platform->getDoctrineTypeMapping('foo'));
    }

    public function testRegisterUnknownDoctrineMappingTypeThrowsException()
    {
        $this->setExpectedException('Doctrine\DBAL\DBALException');
        $this->_platform->registerDoctrineTypeMapping('foo', 'bar');
    }

    public function testCreateWithNoColumnsThrowsException()
    {
        $table = new \Doctrine\DBAL\Schema\Table('test');
        $this->setExpectedException('Doctrine\DBAL\DBALException');
        $this->_platform->getCreateTableSQL($table);
    }

    /**
     * @group DBAL-45
     */
    public function testKeywordList()
    {
        $keywordList = $this->_platform->getReservedKeywordsList();
        $this->assertInstanceOf(FirebirdInterbaseKeywords::class, $keywordList);
        $this->assertInstanceOf('Doctrine\DBAL\Platforms\Keywords\KeywordList', $keywordList);
        $this->assertTrue($keywordList->isKeyword('table'));
    }

    /**
     * CUSTOM
     */

    public function testGeneratePrimaryKeyConstraintName()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('generatePrimaryKeyConstraintName');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, 'id');
        $this->assertInternalType("string", $found);
        $this->assertSame("id_PK", $found);
    }

    public function testSupportsForeignKeyConstraints()
    {
        $found = $this->_platform->supportsForeignKeyConstraints();
        $this->assertInternalType("bool", $found);
        $this->assertTrue($found);
    }

    public function testSupportsSequences()
    {
        $found = $this->_platform->supportsSequences();
        $this->assertInternalType("bool", $found);
        $this->assertTrue($found);
    }

    public function testUsesSequenceEmulatedIdentityColumns()
    {
        $found = $this->_platform->usesSequenceEmulatedIdentityColumns();
        $this->assertInternalType("bool", $found);
        $this->assertTrue($found);
    }

    public function testGetIdentitySequenceName()
    {
        $found = $this->_platform->getIdentitySequenceName('foo', 'bar');
        $this->assertInternalType("string", $found);
        $this->assertSame("foo_D2IS", $found);
    }

    public function testGetIdentitySequenceTriggerName()
    {
        $found = $this->_platform->getIdentitySequenceTriggerName('foo', 'bar');
        $this->assertInternalType("string", $found);
        $this->assertSame("foo_D2IT", $found);
    }

    public function testSupportsViews()
    {
        $found = $this->_platform->supportsViews();
        $this->assertInternalType("bool", $found);
        $this->assertTrue($found);
    }

    public function testSupportsSchemas()
    {
        $found = $this->_platform->supportsSchemas();
        $this->assertInternalType("bool", $found);
        $this->assertFalse($found);
    }

    public function testSupportsIdentityColumns()
    {
        $found = $this->_platform->supportsIdentityColumns();
        $this->assertInternalType("bool", $found);
        $this->assertFalse($found);
    }

    public function testSupportsInlineColumnComments()
    {
        $found = $this->_platform->supportsInlineColumnComments();
        $this->assertInternalType("bool", $found);
        $this->assertFalse($found);
    }

    public function testSupportsCommentOnStatement()
    {
        $found = $this->_platform->supportsCommentOnStatement();
        $this->assertInternalType("bool", $found);
        $this->assertTrue($found);
    }

    public function testSupportsCreateDropDatabase()
    {
        $found = $this->_platform->supportsCreateDropDatabase();
        $this->assertInternalType("bool", $found);
        $this->assertFalse($found);
    }

    public function testSupportsSavepoints()
    {
        $found = $this->_platform->supportsSavepoints();
        $this->assertInternalType("bool", $found);
        $this->assertTrue($found);
    }

    public function testSupportsLimitOffset()
    {
        $found = $this->_platform->supportsLimitOffset();
        $this->assertInternalType("bool", $found);
        $this->assertTrue($found);
    }

    public function testPrefersSequences()
    {
        $found = $this->_platform->prefersSequences();
        $this->assertInternalType("bool", $found);
        $this->assertTrue($found);
    }

    public function testPrefersIdentityColumns()
    {
        $found = $this->_platform->prefersIdentityColumns();
        $this->assertInternalType("bool", $found);
        $this->assertFalse($found);
    }

    /**
     * @dataProvider dataProvider_testDoModifyLimitQuery
     */
    public function testDoModifyLimitQuery($expected, $query, $limit, $offset)
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('doModifyLimitQuery');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, $query, $limit, $offset);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testDoModifyLimitQuery()
    {
        return [
            ["foo", "foo", null, null],
            ["foo ROWS 3", "foo", 3, null],
            ["foo ROWS 4 TO 9000000000000000000", "foo", null, 3],
            ["foo ROWS 4 TO 6", "foo", 3, 3],
        ];
    }

    public function testGetListTablesSQL()
    {
        $found = $this->_platform->getListTablesSQL();
        $this->assertInternalType("string", $found);
    }

    public function testGetListViewsSQL()
    {
        $found = $this->_platform->getListViewsSQL('foo');
        $this->assertInternalType("string", $found);
    }

    /**
     * @dataProvider dataProvider_testMakeSimpleMetadataSelectExpression
     */
    public function testMakeSimpleMetadataSelectExpression($expected, $expressions)
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('makeSimpleMetadataSelectExpression');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, $expressions);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testMakeSimpleMetadataSelectExpression()
    {
        return [
            ["(UPPER(foo) = UPPER('bar'))", ["foo" => "bar"]],
            ["(foo IS NULL)", ["foo" => null]],
            ["(foo = 42)", ["foo" => 42]],
        ];
    }

    public function testGetDummySelectSQL()
    {
        $found = $this->_platform->getDummySelectSQL('foo');
        $this->assertInternalType("string", $found);
    }

    /**
     * @dataProvider dataProvider_testGetExecuteBlockSql
     */
    public function testGetExecuteBlockSql($expected, $params)
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getExecuteBlockSql');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, $params);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetExecuteBlockSql()
    {
        return [
            ["EXECUTE BLOCK AS\nBEGIN\nEND\n", []],
            ["EXECUTE BLOCK AS BEGIN END ", ['formatLineBreak' => false]],
            ["EXECUTE BLOCK (foo bar) \nAS\nBEGIN\nEND\n", ['blockParams' => ['foo' => 'bar']]],
            ["EXECUTE BLOCK AS\n  DECLARE foo bar; \nBEGIN\nEND\n", ['blockVars' => ['foo' => 'bar']]],
            ["EXECUTE BLOCK AS\nBEGIN\n  foo\n  bar\nEND\n", ['statements' => ['foo', 'bar']]],
        ];
    }

    /**
     * @dataProvider dataProvider_testGetExecuteBlockWithExecuteStatementsSql
     */
    public function testGetExecuteBlockWithExecuteStatementsSql($expected, $params)
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getExecuteBlockWithExecuteStatementsSql');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, $params);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetExecuteBlockWithExecuteStatementsSql()
    {
        return [
            ["EXECUTE BLOCK AS\nBEGIN\nEND\n", []],
            ["EXECUTE BLOCK AS BEGIN END ", ['formatLineBreak' => false]],
            ["EXECUTE BLOCK (foo bar) \nAS\nBEGIN\nEND\n", ['blockParams' => ['foo' => 'bar']]],
            ["EXECUTE BLOCK AS\n  DECLARE foo bar; \nBEGIN\nEND\n", ['blockVars' => ['foo' => 'bar']]],
            ["EXECUTE BLOCK AS\nBEGIN\n  EXECUTE STATEMENT 'foo';\nEND\n", ['statements' => ['foo']]],
        ];
    }

    public function testGetDropAllViewsOfTablePSqlSnippet()
    {
        $found = $this->_platform->getDropAllViewsOfTablePSqlSnippet('foo');
        $this->assertInternalType("string", $found);
    }

    public function testGetCreateSequenceSQL()
    {
        $sequence = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Sequence')
            ->disableOriginalConstructor()
            ->getMock();
        $sequence
            ->expects($this->once())
            ->method('getQuotedName')
            ->with($this->_platform)
            ->willReturn('foo');
        $found = $this->_platform->getCreateSequenceSQL($sequence);
        $this->assertInternalType("string", $found);
        $this->assertSame("CREATE SEQUENCE foo", $found);
    }

    public function testGetAlterSequenceSQL()
    {
        $sequence = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Sequence')
            ->disableOriginalConstructor()
            ->getMock();
        $sequence
            ->expects($this->once())
            ->method('getInitialValue')
            ->willReturn(3);
        $sequence
            ->expects($this->once())
            ->method('getQuotedName')
            ->with($this->_platform)
            ->willReturn('foo');
        $found = $this->_platform->getAlterSequenceSQL($sequence);
        $this->assertInternalType("string", $found);
        $this->assertSame("ALTER SEQUENCE foo RESTART WITH 2", $found);
    }

    public function testGetExecuteStatementPSql()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getExecuteStatementPSql');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, 'foo');
        $this->assertInternalType("string", $found);
        $this->assertSame("EXECUTE STATEMENT 'foo'", $found);
    }

    public function testGetPlainDropSequenceSQL()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getPlainDropSequenceSQL');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, 'foo');
        $this->assertInternalType("string", $found);
        $this->assertSame("DROP SEQUENCE foo", $found);
    }

    public function testGetDropTriggerSql()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getDropTriggerSql');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, 'foo');
        $this->assertInternalType("string", $found);
        $this->assertSame("DROP TRIGGER foo", $found);
    }

    /**
     * @dataProvider dataProvider_testGetDropTriggerIfExistsPSql
     */
    public function testGetDropTriggerIfExistsPSql(
        $expectedStartsWith,
        $expectedEndsWith,
        $aTrigger,
        $inBlock
    )
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getDropTriggerIfExistsPSql');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, $aTrigger, $inBlock);
        $this->assertInternalType("string", $found);
        $this->assertStringStartsWith($expectedStartsWith, $found);
        $this->assertContains("IF (EXISTS (SELECT 1 FROM RDB\$TRIGGERS WHERE", $found);
        $this->assertStringEndsWith($expectedEndsWith, $found);
    }

    public function dataProvider_testGetDropTriggerIfExistsPSql()
    {
        return [
            [
                "IF (EXISTS (SELECT 1 FROM RDB\$TRIGGERS WHERE",
                "EXECUTE STATEMENT 'DROP TRIGGER foo'; END",
                'foo',
                false
            ],
            [
                "EXECUTE BLOCK AS BEGIN IF (EXISTS (SELECT 1 FROM RDB\$TRIGGERS WHERE",
                "EXECUTE STATEMENT 'DROP TRIGGER bar'; END END ",
                'bar',
                true
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_testGetCombinedSqlStatements
     */
    public function testGetCombinedSqlStatements($expected, $sql, $aSeparator)
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getCombinedSqlStatements');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, $sql, $aSeparator);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetCombinedSqlStatements()
    {
        return [
            ["foo;", 'foo', ';'],
            ["bar;baz;", ['bar', 'baz'], ';'],
        ];
    }

    public function testGetDropSequenceSQLWithNormalString()
    {
        $found = $this->_platform->getDropSequenceSQL('foo');
        $this->assertInternalType("string", $found);
        $this->assertSame('DROP SEQUENCE foo', $found);
    }

    public function testGetDropSequenceSQLWithD2IS()
    {
        $found = $this->_platform->getDropSequenceSQL('bar_D2IS');
        $this->assertInternalType("string", $found);
        $this->assertStringStartsWith('EXECUTE BLOCK AS', $found);
        $this->assertContains('RDB$TRIGGERS', $found);
        $this->assertContains('RDB$TRIGGER_NAME', $found);
        $this->assertContains('DROP TRIGGER bar_D2IT', $found);
        $this->assertContains('DROP SEQUENCE bar_D2IS', $found);
    }

    public function testGetDropSequenceSQLWithSequence()
    {
        $sequence = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Sequence')
            ->disableOriginalConstructor()
            ->getMock();
        $sequence
            ->expects($this->once())
            ->method('getQuotedName')
            ->willReturn('foo');
        $found = $this->_platform->getDropSequenceSQL($sequence);
        $this->assertInternalType("string", $found);
        $this->assertSame('DROP SEQUENCE foo', $found);
    }

    public function testGetDropForeignKeySQL()
    {
        $found = $this->_platform->getDropForeignKeySQL('foo', 'bar');
        $this->assertInternalType("string", $found);
        $this->assertSame('ALTER TABLE bar DROP CONSTRAINT foo', $found);
    }

    public function testGetSequenceNextValFunctionSQL()
    {
        $found = $this->_platform->getSequenceNextValFunctionSQL('foo');
        $this->assertInternalType("string", $found);
        $this->assertSame('NEXT VALUE FOR foo', $found);
    }

    public function testGetSequenceNextValSQL()
    {
        $found = $this->_platform->getSequenceNextValSQL('foo');
        $this->assertInternalType("string", $found);
        $this->assertSame('SELECT NEXT VALUE FOR foo FROM RDB$DATABASE', $found);
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testGetSetTransactionIsolationSQLThrowsException()
    {
        $this->_platform->getSetTransactionIsolationSQL(null);
    }

    public function testGetBooleanTypeDeclarationSQL()
    {
        $found = $this->_platform->getBooleanTypeDeclarationSQL([]);
        $this->assertInternalType("string", $found);
        $this->assertSame('BOOLEAN', $found);
    }

    public function testGetIntegerTypeDeclarationSQL()
    {
        $found = $this->_platform->getIntegerTypeDeclarationSQL([]);
        $this->assertInternalType("string", $found);
        $this->assertSame('INTEGER', $found);
    }

    public function testGetBigIntTypeDeclarationSQL()
    {
        $found = $this->_platform->getBigIntTypeDeclarationSQL([]);
        $this->assertInternalType("string", $found);
        $this->assertSame('BIGINT', $found);
    }

    /**
     * @dataProvider dataProvider_testGetTruncateTableSQL
     */
    public function testGetTruncateTableSQL($cascade)
    {
        $found = $this->_platform->getTruncateTableSQL('foo', $cascade);
        $this->assertInternalType("string", $found);
        $this->assertSame('DELETE FROM foo', $found);
    }

    public function dataProvider_testGetTruncateTableSQL()
    {
        return [
            [false],
            [true],
        ];
    }

    public function testGetDateTimeFormatString()
    {
        $found = $this->_platform->getDateTimeFormatString();
        $this->assertInternalType("string", $found);
    }

    public function testGetAlterTableSQLWorksWithNoChanges()
    {
        $diff = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\TableDiff')
            ->disableOriginalConstructor()
            ->getMock();
        $name = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Identifier')
            ->disableOriginalConstructor()
            ->getMock();
        $name
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'foo'");
        $diff
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $diff->addedColumns = [];
        $diff->removedColumns = [];
        $diff->changedColumns = [];
        $diff->renamedColumns = [];
        $found = $this->_platform->getAlterTableSQL($diff);
        $this->assertInternalType("array", $found);
        $this->assertSame([], $found);
    }

    public function testGetAlterTableSQLWorksWithAddedColumn()
    {
        $diff = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\TableDiff')
            ->disableOriginalConstructor()
            ->getMock();
        $name = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Identifier')
            ->disableOriginalConstructor()
            ->getMock();
        $name
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'foo'");
        $diff
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $column = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Column')
            ->disableOriginalConstructor()
            ->getMock();
        $column
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'bar'");
        $type = $this
            ->getMockBuilder('Doctrine\DBAL\Types\BigIntType')
            ->disableOriginalConstructor()
            ->getMock();
        $type
            ->expects($this->any())
            ->method('getSQLDeclaration')
            ->willReturn("baz");
        $column
            ->expects($this->any())
            ->method('getType')
            ->willReturn($type);
        $column
            ->expects($this->any())
            ->method('toArray')
            ->willReturn(['type' => $type]);
        $diff->addedColumns = [$column];
        $diff->removedColumns = [];
        $diff->changedColumns = [];
        $diff->renamedColumns = [];
        $found = $this->_platform->getAlterTableSQL($diff);
        $this->assertInternalType("array", $found);
        $this->assertCount(1, $found);
        $this->assertSame([0 => "ALTER TABLE 'foo' ADD 'bar' baz DEFAULT NULL"], $found);
    }

    public function testGetAlterTableSQLWorksWithRemovedColumn()
    {
        $diff = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\TableDiff')
            ->disableOriginalConstructor()
            ->getMock();
        $name = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Identifier')
            ->disableOriginalConstructor()
            ->getMock();
        $name
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'foo'");
        $diff
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $column = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Column')
            ->disableOriginalConstructor()
            ->getMock();
        $column
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'bar'");
        $type = $this
            ->getMockBuilder('Doctrine\DBAL\Types\BigIntType')
            ->disableOriginalConstructor()
            ->getMock();
        $type
            ->expects($this->any())
            ->method('getSQLDeclaration')
            ->willReturn("baz");
        $column
            ->expects($this->any())
            ->method('getType')
            ->willReturn($type);
        $column
            ->expects($this->any())
            ->method('toArray')
            ->willReturn(['type' => $type]);
        $diff->addedColumns = [];
        $diff->removedColumns = [$column];
        $diff->changedColumns = [];
        $diff->renamedColumns = [];
        $found = $this->_platform->getAlterTableSQL($diff);
        $this->assertInternalType("array", $found);
        $this->assertCount(1, $found);
        $this->assertSame([0 => "ALTER TABLE 'foo' DROP 'bar'"], $found);
    }

    public function testGetAlterTableSQLWorksWithChangedColumn()
    {
        $diff = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\TableDiff')
            ->disableOriginalConstructor()
            ->getMock();
        $name = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Identifier')
            ->disableOriginalConstructor()
            ->getMock();
        $name
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'foo'");
        $diff
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $column = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Column')
            ->disableOriginalConstructor()
            ->getMock();
        $column
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'bar'");
        $type = $this
            ->getMockBuilder('Doctrine\DBAL\Types\BigIntType')
            ->disableOriginalConstructor()
            ->getMock();
        $type
            ->expects($this->any())
            ->method('getSqlDeclaration')
            ->willReturn('baz');
        $column
            ->expects($this->any())
            ->method('getType')
            ->willReturn($type);
        $column
            ->expects($this->any())
            ->method('toArray')
            ->willReturn(['type' => $type]);
        $columnDiff = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\ColumnDiff')
            ->disableOriginalConstructor()
            ->getMock();
        $columnDiff->column = $column;
        $identifierOld = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Identifier')
            ->disableOriginalConstructor()
            ->getMock();
        $identifierOld
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'bar'");
        $columnDiff
            ->expects($this->any())
            ->method('getOldColumnName')
            ->willReturn($identifierOld);
        $columnDiff
            ->expects($this->any())
            ->method('hasChanged')
            ->willReturn(true);
        $diff->addedColumns = [];
        $diff->removedColumns = [];
        $diff->changedColumns = [$columnDiff];
        $diff->renamedColumns = [];
        $found = $this->_platform->getAlterTableSQL($diff);
        $this->assertInternalType("array", $found);
        $this->assertCount(6, $found);
        $expected = [
            0 => "ALTER TABLE 'foo' ALTER COLUMN 'bar' TYPE baz",
            1 => "ALTER TABLE 'foo' ALTER 'bar' DROP DEFAULT",
            2 => "UPDATE RDB\$RELATION_FIELDS SET RDB\$NULL_FLAG = NULL WHERE UPPER(RDB\$FIELD_NAME) = UPPER('') AND UPPER(RDB\$RELATION_NAME) = UPPER('')",
            3 => "ALTER TABLE 'foo' ALTER 'bar' DROP DEFAULT",
            4 => "ALTER TABLE 'foo' ALTER COLUMN 'bar' TYPE baz",
            5 => "COMMENT ON COLUMN 'foo'.'bar' IS ''",
        ];
        $this->assertSame($expected, $found);
    }

    public function testGetAlterTableSQLWorksWithRenamedColumn()
    {
        $diff = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\TableDiff')
            ->disableOriginalConstructor()
            ->getMock();
        $name = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Identifier')
            ->disableOriginalConstructor()
            ->getMock();
        $name
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'foo'");
        $diff
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $column = $this
            ->getMockBuilder('Doctrine\DBAL\Schema\Column')
            ->disableOriginalConstructor()
            ->getMock();
        $column
            ->expects($this->any())
            ->method('getQuotedName')
            ->willReturn("'bar'");
        $type = $this
            ->getMockBuilder('Doctrine\DBAL\Types\BigIntType')
            ->disableOriginalConstructor()
            ->getMock();
        $type
            ->expects($this->any())
            ->method('getSQLDeclaration')
            ->willReturn("baz");
        $column
            ->expects($this->any())
            ->method('getType')
            ->willReturn($type);
        $column
            ->expects($this->any())
            ->method('toArray')
            ->willReturn(['type' => $type]);
        $diff->addedColumns = [];
        $diff->removedColumns = [];
        $diff->changedColumns = [];
        $diff->renamedColumns = [$column];
        $found = $this->_platform->getAlterTableSQL($diff);
        $this->assertInternalType("array", $found);
        $this->assertCount(1, $found);
        $this->assertSame([0 => "ALTER TABLE 'foo' ALTER COLUMN 0 TO 'bar'"], $found);
    }

    public function testGetVarcharMaxLength()
    {
        $found = $this->_platform->getVarcharMaxLength();
        $this->assertInternalType("int", $found);
        $this->assertGreaterThan(0, $found);
    }

    public function testGetBinaryMaxLength()
    {
        $found = $this->_platform->getBinaryMaxLength();
        $this->assertInternalType("int", $found);
        $this->assertGreaterThan(0, $found);
    }

    public function testGetReservedKeywordsClass()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getReservedKeywordsClass');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform);
        $this->assertInternalType("string", $found);
        $this->assertTrue(class_exists($found));
        $this->assertTrue(is_subclass_of($found, 'Doctrine\DBAL\Platforms\Keywords\KeywordList'));
    }

    public function testGetSmallIntTypeDeclarationSQL()
    {
        $found = $this->_platform->getSmallIntTypeDeclarationSQL([]);
        $this->assertInternalType("string", $found);
        $this->assertSame("SMALLINT", $found);
    }

    public function testGetCommonIntegerTypeDeclarationSQL()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('_getCommonIntegerTypeDeclarationSQL');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, []);
        $this->assertInternalType("string", $found);
        $this->assertSame("", $found);
    }

    /**
     * @dataProvider dataProvider_testGetClobTypeDeclarationSQL
     */
    public function testGetClobTypeDeclarationSQL($expected, $field)
    {
        $found = $this->_platform->getClobTypeDeclarationSQL($field);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetClobTypeDeclarationSQL()
    {
        return [
            ["BLOB SUB_TYPE TEXT", []],
            ["VARCHAR(255)", ['length' => 255]],
        ];
    }

    public function testGetBlobTypeDeclarationSQL()
    {
        $found = $this->_platform->getBlobTypeDeclarationSQL([]);
        $this->assertInternalType("string", $found);
        $this->assertSame("BLOB", $found);
    }

    public function testGetDateTimeTypeDeclarationSQL()
    {
        $found = $this->_platform->getDateTimeTypeDeclarationSQL([]);
        $this->assertInternalType("string", $found);
        $this->assertSame("TIMESTAMP", $found);
    }

    public function testGetTimeTypeDeclarationSQL()
    {
        $found = $this->_platform->getTimeTypeDeclarationSQL([]);
        $this->assertInternalType("string", $found);
        $this->assertSame("TIME", $found);
    }

    public function testGetDateTypeDeclarationSQL()
    {
        $found = $this->_platform->getDateTypeDeclarationSQL([]);
        $this->assertInternalType("string", $found);
        $this->assertSame("DATE", $found);
    }

    /**
     * @dataProvider dataProvider_testGetVarcharTypeDeclarationSQLSnippet
     */
    public function testGetVarcharTypeDeclarationSQLSnippet($expected, $length, $fixed)
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getVarcharTypeDeclarationSQLSnippet');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, $length, $fixed);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetVarcharTypeDeclarationSQLSnippet()
    {
        return [
            ["CHAR(32)", 32, true],
            ["CHAR(255)", 0, true],
            ["VARCHAR(32)", 32, false],
            ["VARCHAR(255)", 0, false],
        ];
    }

    /**
     * @dataProvider dataProvider_testGetColumnCharsetDeclarationSQL
     */
    public function testGetColumnCharsetDeclarationSQL($expected, $charset)
    {
        $found = $this->_platform->getColumnCharsetDeclarationSQL($charset);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetColumnCharsetDeclarationSQL()
    {
        return [
            ["", ""],
            [" CHARACTER SET foo", "foo"],
        ];
    }

    public function testReturnsBinaryTypeDeclarationSQL()
    {
        $found = $this->_platform->getBinaryTypeDeclarationSQL([]);
        $this->assertSame("VARCHAR(255)", $found);
    }

    /**
     * @dataProvider dataProvider_testGetBinaryTypeDeclarationSQLSnippet
     */
    public function testGetBinaryTypeDeclarationSQLSnippet($expected, $length, $fixed)
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getBinaryTypeDeclarationSQLSnippet');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, $length, $fixed);
        $this->assertInternalType("string", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetBinaryTypeDeclarationSQLSnippet()
    {
        return [
            ["CHAR(32)", 32, true],
            ["CHAR(255)", 0, true],
            ["VARCHAR(32)", 32, false],
            ["VARCHAR(255)", 0, false],
            ["VARCHAR(8190)", 8190, false],
            ["BLOB", 8191, false],
        ];
    }

    public function testGetColumnDeclarationSQL()
    {
        $type = $this
            ->getMockBuilder('Doctrine\DBAL\Types\StringType')
            ->disableOriginalConstructor()
            ->getMock();
        $type
            ->expects($this->any())
            ->method('getSQLDeclaration')
            ->willReturn("baz");
        $type
            ->expects($this->any())
            ->method('__toString')
            ->willReturn("binary");
        $found = $this->_platform->getColumnDeclarationSQL("foo", ['type' => $type]);
        $this->assertInternalType("string", $found);
        $this->assertSame("foo baz  CHARACTER SET binary DEFAULT NULL ", $found);
    }

    public function testGetCreateTemporaryTableSnippetSQL()
    {
        $found = $this->_platform->getCreateTemporaryTableSnippetSQL();
        $this->assertInternalType("string", $found);
        $this->assertSame("CREATE GLOBAL TEMPORARY TABLE", $found);
    }

    public function testGetTemporaryTableSQLgetTemporaryTableSQL()
    {
        $found = $this->_platform->getTemporaryTableSQL();
        $this->assertInternalType("string", $found);
        $this->assertSame("GLOBAL TEMPORARY", $found);
    }

    /**
     * @dataProvider dataProvider_testGetCreateTableSQL
     */
    public function testGetCreateTableSQL($expected, $options)
    {
        $type = $this
            ->getMockBuilder('Doctrine\DBAL\Types\StringType')
            ->disableOriginalConstructor()
            ->getMock();
        $type
            ->expects($this->any())
            ->method('getSQLDeclaration')
            ->willReturn("baz");
        $columns = [
            0 => ['type' => $type],
        ];
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('_getCreateTableSQL');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, 'foo', $columns, $options);
        $this->assertInternalType("array", $found);
        $this->assertSame($expected, $found);
    }

    public function dataProvider_testGetCreateTableSQL()
    {
        return [
            [
                [
                    0 => "CREATE TABLE foo (0 baz DEFAULT NULL)",
                ],
                []
            ],
            [
                [
                    0 => "CREATE GLOBAL TEMPORARY TABLE foo (0 baz DEFAULT NULL) ON COMMIT PRESERVE ROWS",
                ],
                ['temporary' => true]
            ],
        ];
    }

    public function testGetListSequencesSQL()
    {
        $found = $this->_platform->getListSequencesSQL('');
        $this->assertInternalType("string", $found);
        $this->assertStringStartsWith("select trim(rdb\$generator_name)", $found);
    }

    public function testGetListTableColumnsSQL()
    {
        $found = $this->_platform->getListTableColumnsSQL('foo');
        $this->assertInternalType("string", $found);
        $this->assertStringStartsWith("SELECT TRIM(r.RDB\$FIELD_NAME) AS \"FIELD_NAME\",\r", ltrim($found));
        $this->assertContains(" FROM RDB\$RELATION_FIELDS r\r", $found);
    }

    public function testGetListTableForeignKeysSQL()
    {
        $found = $this->_platform->getListTableForeignKeysSQL('foo');
        $this->assertInternalType("string", $found);
        $this->assertStringStartsWith("SELECT TRIM(rc.RDB\$CONSTRAINT_NAME) AS constraint_name,\r", ltrim($found));
        $this->assertContains(" FROM RDB\$INDEX_SEGMENTS s\r", $found);
    }

    public function testGetListTableIndexesSQL()
    {
        $found = $this->_platform->getListTableIndexesSQL('foo');
        $this->assertInternalType("string", $found);
        $this->assertStringStartsWith("SELECT\r", ltrim($found));
        $this->assertContains("FROM RDB\$INDEX_SEGMENTS\r", $found);
    }

    public function testGetSQLResultCasing()
    {
        $found = $this->_platform->getSQLResultCasing('foo');
        $this->assertInternalType("string", $found);
        $this->assertStringStartsWith("FOO", $found);
    }

    public function testUnquotedIdentifierName()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('unquotedIdentifierName');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, 'foo');
        $this->assertInternalType("string", $found);
        $this->assertSame("foo", $found);
    }

    public function testGetQuotedNameOf()
    {
        $reflection = new \ReflectionObject($this->_platform);
        $method = $reflection->getMethod('getQuotedNameOf');
        $method->setAccessible(true);
        $found = $method->invoke($this->_platform, 'foo');
        $this->assertInternalType("string", $found);
        $this->assertSame("foo", $found);
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testGetCreateDatabaseSQLThrowsException()
    {
        $this->_platform->getCreateDatabaseSQL('foo');
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testGetDropDatabaseSQLThrowsException()
    {
        $this->_platform->getDropDatabaseSQL('foobar');
    }

    /**
     * @group DBAL-553
     */
    public function testHasNativeJsonType()
    {
        $this->assertFalse($this->_platform->hasNativeJsonType());
    }

    /**
     * @group DBAL-553
     */
    public function testReturnsJsonTypeDeclarationSQL()
    {
        $column = array(
            'length'  => 666,
            'notnull' => true,
            'type'    => \Doctrine\DBAL\Types\Type::getType('json_array'),
        );
        $this->assertSame(
            $this->_platform->getClobTypeDeclarationSQL($column),
            $this->_platform->getJsonTypeDeclarationSQL($column)
        );
    }

    public function testGetStringLiteralQuoteCharacter()
    {
        $this->assertSame("'", $this->_platform->getStringLiteralQuoteCharacter());
    }
}

<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\DBAL\SchemaManager\Table;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement;

class CreateTest extends AbstractIntegrationTest
{
    public function testCreateTable()
    {
        $connection = $this->_entityManager->getConnection();
        $sm = $connection->getSchemaManager();
        $tableName = strtoupper("TABLE_" . substr(md5(__CLASS__ . ':' . __FUNCTION__), 0, 12));
        $table = new \Doctrine\DBAL\Schema\Table($tableName);
        $table->addColumn('foo', 'string', ['notnull' => false, 'length' => 255]);
        $sm->createTable($table);
        $this->assertTrue($sm->tablesExist([$tableName]));
        $foundColumns = $sm->listTableColumns($tableName);
        $this->assertCount(1, $foundColumns);
        $this->assertArrayHasKey("foo", $foundColumns);
        $foundColumn = $foundColumns["foo"];
        $this->assertSame("foo", $foundColumn->getName(), 'Invalid name');
        $this->assertInstanceOf('Doctrine\DBAL\Types\StringType', $foundColumn->getType(), 'Invalid type');
        $this->assertSame(255, $foundColumn->getLength(), 'Invalid length');
        $this->assertSame(10, $foundColumn->getPrecision(), 'Invalid precision');
        $this->assertSame(0, $foundColumn->getScale(), 'Invalid scale');
        $this->assertFalse($foundColumn->getUnsigned(), 'Invalid unsigned');
        $this->assertFalse($foundColumn->getFixed(), 'Invalid fixed');
        $this->assertFalse($foundColumn->getNotnull(), 'Invalid notnull');
        $this->assertNull($foundColumn->getDefault(), 'Invalid default');
        $this->assertFalse($foundColumn->getAutoincrement(), 'Invalid autoincrement');
    }
}

<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Statement;
use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;

/**
 * @runTestsInSeparateProcesses
 */
class StatementTest extends AbstractIntegrationTest
{
    public function testFetchWorks()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $sql = "SELECT * FROM Album";
        $statement = new Statement($connection, $sql);

        $statement->setFetchMode(\PDO::FETCH_CLASS, '\stdClass');
        $statement->execute();
        $object = $statement->fetch();
        $this->assertInternalType('object', $object);
        $this->assertInstanceOf('stdClass', $object);
        $this->assertSame(1, $object->ID);
        $this->assertSame('2017-01-01 15:00:00', $object->TIMECREATED);
        $this->assertSame('...Baby One More Time', $object->NAME);
        $this->assertSame(2, $object->ARTIST_ID);

        $statement->setFetchMode(\PDO::FETCH_OBJ, '\stdClass');
        $statement->execute();
        $object = $statement->fetch();
        $this->assertInternalType('object', $object);
        $this->assertInstanceOf('stdClass', $object);
        $this->assertSame(1, $object->ID);
        $this->assertSame('2017-01-01 15:00:00', $object->TIMECREATED);
        $this->assertSame('...Baby One More Time', $object->NAME);
        $this->assertSame(2, $object->ARTIST_ID);

        $object = new class {
            public $dummy = 42;
        };
        $statement->setFetchMode(\PDO::FETCH_INTO, $object);
        $statement->execute();
        $object2 = $statement->fetch();
        $this->assertSame($object, $object2);
        $this->assertSame(42, $object->dummy);
        $this->assertSame(1, $object->ID);
        $this->assertSame('2017-01-01 15:00:00', $object->TIMECREATED);
        $this->assertSame('...Baby One More Time', $object->NAME);
        $this->assertSame(2, $object->ARTIST_ID);

        $statement->setFetchMode(\PDO::FETCH_ASSOC, $object);
        $statement->execute();
        $row = $statement->fetch();
        $this->assertSame(1, $row['ID']);
        $this->assertSame('2017-01-01 15:00:00', $row['TIMECREATED']);
        $this->assertSame('...Baby One More Time', $row['NAME']);
        $this->assertSame(2, $row['ARTIST_ID']);

        $statement->setFetchMode(\PDO::FETCH_NUM, $object);
        $statement->execute();
        $row = $statement->fetch();
        $this->assertSame(1, $row[0]);
        $this->assertSame('2017-01-01 15:00:00', $row[1]);
        $this->assertSame('...Baby One More Time', $row[2]);
        $this->assertSame(2, $row[3]);

        $statement->setFetchMode(\PDO::FETCH_BOTH, $object);
        $statement->execute();
        $row = $statement->fetch();
        $this->assertSame(1, $row[0]);
        $this->assertSame('2017-01-01 15:00:00', $row[1]);
        $this->assertSame('...Baby One More Time', $row[2]);
        $this->assertSame(2, $row[3]);
        $this->assertSame(1, $row['ID']);
        $this->assertSame('2017-01-01 15:00:00', $row['TIMECREATED']);
        $this->assertSame('...Baby One More Time', $row['NAME']);
        $this->assertSame(2, $row['ARTIST_ID']);
    }

    public function testFetchAllWorks()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $sql = "SELECT * FROM Album";
        $statement = new Statement($connection, $sql);

        $statement->setFetchMode(\PDO::FETCH_CLASS, 'stdClass');
        $statement->execute();
        $array = $statement->fetchAll();
        $this->assertInternalType("array", $array);
        $this->assertCount(2, $array);
        $this->assertInternalType('object', $array[0]);
        $this->assertInstanceOf('stdClass', $array[0]);
        $this->assertInternalType('object', $array[1]);
        $this->assertInstanceOf('stdClass', $array[1]);
        $this->assertSame(1, $array[0]->ID);
        $this->assertSame('2017-01-01 15:00:00', $array[0]->TIMECREATED);
        $this->assertSame('...Baby One More Time', $array[0]->NAME);
        $this->assertSame(2, $array[0]->ARTIST_ID);
        $this->assertSame(2, $array[1]->ID);
        $this->assertSame('2017-01-01 15:00:00', $array[1]->TIMECREATED);
        $this->assertSame('Dark Horse', $array[1]->NAME);
        $this->assertSame(3, $array[1]->ARTIST_ID);

        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        $statement->execute();
        $rows = $statement->fetchAll();
        $this->assertInternalType("array", $rows);
        $this->assertCount(2, $rows);
        $this->assertInternalType("array", $rows[0]);
        $this->assertInternalType("array", $rows[1]);
        $this->assertSame(1, $rows[0]['ID'] ?? false);
        $this->assertSame('2017-01-01 15:00:00', $rows[0]['TIMECREATED'] ?? false);
        $this->assertSame('...Baby One More Time', $rows[0]['NAME'] ?? false);
        $this->assertSame(2, $rows[0]['ARTIST_ID'] ?? false);
        $this->assertSame(2, $rows[1]['ID'] ?? false);
        $this->assertSame('2017-01-01 15:00:00', $rows[1]['TIMECREATED'] ?? false);
        $this->assertSame('Dark Horse', $rows[1]['NAME'] ?? false);
        $this->assertSame(3, $rows[1]['ARTIST_ID'] ?? false);

        $statement->setFetchMode(\PDO::FETCH_NUM);
        $statement->execute();
        $rows = $statement->fetchAll();
        $this->assertInternalType("array", $rows);
        $this->assertCount(2, $rows);
        $this->assertInternalType("array", $rows[0]);
        $this->assertInternalType("array", $rows[1]);
        $this->assertSame(1, $rows[0][0] ?? false);
        $this->assertSame('2017-01-01 15:00:00', $rows[0][1] ?? false);
        $this->assertSame('...Baby One More Time', $rows[0][2] ?? false);
        $this->assertSame(2, $rows[0][3] ?? false);
        $this->assertSame(2, $rows[1][0] ?? false);
        $this->assertSame('2017-01-01 15:00:00', $rows[1][1] ?? false);
        $this->assertSame('Dark Horse', $rows[1][2] ?? false);
        $this->assertSame(3, $rows[1][3] ?? false);

        $statement->setFetchMode(\PDO::FETCH_BOTH);
        $statement->execute();
        $rows = $statement->fetchAll();
        $this->assertInternalType("array", $rows);
        $this->assertCount(2, $rows);
        $this->assertInternalType("array", $rows[0]);
        $this->assertInternalType("array", $rows[1]);
        $this->assertSame(1, $rows[0][0] ?? false);
        $this->assertSame('2017-01-01 15:00:00', $rows[0][1] ?? false);
        $this->assertSame('...Baby One More Time', $rows[0][2] ?? false);
        $this->assertSame(2, $rows[0][3] ?? false);
        $this->assertSame(1, $rows[0]['ID'] ?? false);
        $this->assertSame('2017-01-01 15:00:00', $rows[0]['TIMECREATED'] ?? false);
        $this->assertSame('...Baby One More Time', $rows[0]['NAME'] ?? false);
        $this->assertSame(2, $rows[0]['ARTIST_ID'] ?? false);
        $this->assertSame(2, $rows[1][0] ?? false);
        $this->assertSame('2017-01-01 15:00:00', $rows[1][1] ?? false);
        $this->assertSame('Dark Horse', $rows[1][2] ?? false);
        $this->assertSame(3, $rows[1][3] ?? false);
        $this->assertSame(2, $rows[1]['ID'] ?? false);
        $this->assertSame('2017-01-01 15:00:00', $rows[1]['TIMECREATED'] ?? false);
        $this->assertSame('Dark Horse', $rows[1]['NAME'] ?? false);
        $this->assertSame(3, $rows[1]['ARTIST_ID'] ?? false);
    }

    public function testFetchColumnWorks()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $sql = "SELECT * FROM Album";
        $statement = new Statement($connection, $sql);

        $statement->execute();
        $column = $statement->fetchColumn();
        $this->assertSame(1, $column);

        $statement->execute();
        $column = $statement->fetchColumn(1);
        $this->assertSame('2017-01-01 15:00:00', $column);

        $statement->execute();
        $column = $statement->fetchColumn(2);
        $this->assertSame('...Baby One More Time', $column);

        $statement->execute();
        $column = $statement->fetchColumn(3);
        $this->assertSame(2, $column);
    }

    public function testGetIteratorWorks()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $sql = "SELECT * FROM Album";
        $statement = new Statement($connection, $sql);
        $statement->execute();
        $array = [];
        foreach ($statement as $row) {
            $array[] = $row;
        }
        $this->assertCount(2, $array);
        $this->assertInternalType("array", $array[0]);
        $this->assertInternalType("array", $array[1]);
        $this->assertSame(1, $array[0]['ID'] ?? false);
        $this->assertSame(2, $array[1]['ID'] ?? false);
    }

    public function testExecuteWorks()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $sql = "SELECT * FROM Album";
        $statement = new Statement($connection, $sql);
        $this->assertTrue($statement->execute());
    }

    public function testExecuteWorksWithParameters()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $sql = "SELECT * FROM Album WHERE ID = ?";
        $statement = new Statement($connection, $sql);
        $this->assertTrue($statement->execute([1]));
    }

    public function testBindValueWorks()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $sql = "SELECT ID FROM Album WHERE ID = ?";
        $statement = new Statement($connection, $sql);
        $statement->bindValue(0, 2);
        $statement->execute();
        $value = $statement->fetchColumn();
        $this->assertSame(2, $value);
    }

    public function testBindParamWorks()
    {
        $connection = $this->_entityManager->getConnection()->getWrappedConnection();
        $sql = "SELECT ID FROM Album WHERE ID = :ID";
        $statement = new Statement($connection, $sql);
        $id = 2;
        $statement->bindParam(':ID', $id);
        $statement->execute();
        $value = $statement->fetchColumn();
        $this->assertSame(2, $value);
    }
}

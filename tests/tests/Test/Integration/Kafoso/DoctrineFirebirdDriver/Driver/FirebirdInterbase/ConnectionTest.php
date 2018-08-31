<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Connection;
use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

class ConnectionTest extends AbstractIntegrationTest
{
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
}

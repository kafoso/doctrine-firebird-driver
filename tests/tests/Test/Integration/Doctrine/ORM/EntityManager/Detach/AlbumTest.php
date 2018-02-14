<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\EntityManager\Detach;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class AlbumTest extends AbstractIntegrationTest
{
    public function testCanPersist()
    {
        $album = new Entity\Album("Foo");
        $this->assertNull($album->getId());
        $this->_entityManager->persist($album);
        $this->_entityManager->flush($album);
        $id = $album->getId();
        $this->assertSame("Foo", $album->getName());
        $this->_entityManager->detach($album);
        $album->setName("Bar");
        $this->assertSame("Bar", $album->getName());
        $this->_entityManager->flush();
        $albumFound = $this->_entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertSame("Foo", $albumFound->getName());
    }
}

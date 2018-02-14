<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\EntityManager\Persist;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class AlbumTest extends AbstractIntegrationTest
{
    public function testCanPersist()
    {
        $album = new Entity\Album("Communion");
        $this->assertNull($album->getId());
        $this->_entityManager->persist($album);
        $this->_entityManager->flush($album);
        $this->assertInternalType('int', $album->getId());
        $this->assertSame("Communion", $album->getName());
    }

    public function testCascadingPersistWorks()
    {
        $artistType = $this->_entityManager->getRepository(Entity\Artist\Type::class)->find(2);
        $album = new Entity\Album("Life thru a Lens");
        $artist = new Entity\Artist("Robbie Williams", $artistType);
        $album->setArtist($artist);
        $this->assertNull($album->getId());
        $this->assertNull($artist->getId());
        $this->assertSame($artist, $album->getArtist());
        $this->_entityManager->persist($album);
        $this->_entityManager->flush($album);
        $id = $album->getId();
        $albumFound = $this->_entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertSame($album, $albumFound);
        $this->assertInstanceOf(Entity\Artist::class, $album->getArtist());
        $this->assertSame($artist, $albumFound->getArtist());
        $this->assertInternalType('int', $albumFound->getArtist()->getId());
    }
}

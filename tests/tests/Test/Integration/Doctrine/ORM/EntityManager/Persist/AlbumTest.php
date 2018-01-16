<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\EntityManager\Persist;

use IST\DoctrineFirebirdDriver\Test\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Entity;

class AlbumTest extends AbstractIntegrationTest
{
    public function testCanPersist()
    {
        $album = new Entity\Album("Communion");
        $this->assertNull($album->getId());
        static::$entityManager->persist($album);
        static::$entityManager->flush($album);
        $this->assertInternalType('int', $album->getId());
        $this->assertSame("Communion", $album->getName());
    }

    public function testCascadingPersistWorks()
    {
        $artistType = static::$entityManager->getRepository(Entity\Artist\Type::class)->find(2);
        $album = new Entity\Album("Life thru a Lens");
        $artist = new Entity\Artist("Robbie Williams", $artistType);
        $album->setArtist($artist);
        $this->assertNull($album->getId());
        $this->assertNull($artist->getId());
        $this->assertSame($artist, $album->getArtist());
        static::$entityManager->persist($album);
        static::$entityManager->flush($album);
        $id = $album->getId();
        $album = static::$entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertInstanceOf(Entity\Artist::class, $album->getArtist());
        $this->assertInternalType('int', $album->getArtist()->getId());
    }
}

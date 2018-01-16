<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\EntityManager\Update;

use IST\DoctrineFirebirdDriver\Test\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Entity;

class AlbumTest extends AbstractIntegrationTest
{
    public function testCanUpdate()
    {
        $album = new Entity\Album("Highway to Hell");
        $this->assertNull($album->getId());
        static::$entityManager->persist($album);
        static::$entityManager->flush($album);
        $this->assertInternalType('int', $album->getId());
        $this->assertNull($album->getArtist());
        $id = $album->getId();
        $album = static::$entityManager->getRepository(Entity\Album::class)->find($id);
        $artist = static::$entityManager->getRepository(Entity\Artist::class)->find(2);
        $album->setArtist($artist);
        static::$entityManager->flush($album);
        $album = static::$entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertInstanceOf(Entity\Artist::class, $album->getArtist());
        $this->assertSame(2, $album->getArtist()->getId());
    }
}

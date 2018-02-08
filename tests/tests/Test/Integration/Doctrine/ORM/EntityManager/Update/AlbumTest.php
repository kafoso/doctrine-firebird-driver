<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\EntityManager\Update;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class AlbumTest extends AbstractIntegrationTest
{
    public function testCanUpdate()
    {
        $album = new Entity\Album("Highway to Hell");
        $this->assertNull($album->getId());
        $this->_entityManager->persist($album);
        $this->_entityManager->flush($album);
        $this->assertInternalType('int', $album->getId());
        $this->assertNull($album->getArtist());
        $albumId = $album->getId();
        $foundAlbumA = $this->_entityManager->getRepository(Entity\Album::class)->find($albumId);
        $this->assertInstanceOf(Entity\Album::class, $foundAlbumA);
        $this->assertSame($album, $foundAlbumA); // Object is already loaded
        $artist = $this->_entityManager->getRepository(Entity\Artist::class)->find(2);
        $foundAlbumA->setArtist($artist);
        $this->_entityManager->flush($foundAlbumA);
        $foundAlbumB = $this->_entityManager->getRepository(Entity\Album::class)->find($albumId);
        $this->assertSame($foundAlbumA, $foundAlbumB); // Object is already loaded
        $this->assertInstanceOf(Entity\Artist::class, $foundAlbumB->getArtist());
        $this->assertSame($artist, $foundAlbumB->getArtist()); // Object is already loaded
        $this->assertSame(2, $foundAlbumB->getArtist()->getId());
    }
}

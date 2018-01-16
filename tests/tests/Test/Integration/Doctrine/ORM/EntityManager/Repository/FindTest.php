<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\EntityManager\Repository;

use IST\DoctrineFirebirdDriver\Test\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Entity;

class FindTest extends AbstractIntegrationTest
{
    public function testFindAlbum()
    {
        $album = static::$entityManager->getRepository(Entity\Album::class)->find(1);
        $this->assertInstanceOf(Entity\Album::class, $album);
        $this->assertSame(1, $album->getId());
        $this->assertSame("...Baby One More Time", $album->getName());
        $this->assertSame("2017-01-01 15:00:00", $album->getTimeCreated()->format('Y-m-d H:i:s'));
        $this->assertSame(2, $album->getSongs()->count());
        $this->assertSame(1, $album->getSongs()->get(0)->getId());
        $this->assertSame(2, $album->getSongs()->get(1)->getId());
        $this->assertSame(2, $album->getArtist()->getId());
    }

    public function testFindAlbumReturnsNullOnMismatch()
    {
        $album = static::$entityManager->getRepository(Entity\Album::class)->find(0);
        $this->assertNull($album);
    }

    public function testFindArtist()
    {
        $artist = static::$entityManager->getRepository(Entity\Artist::class)->find(2);
        $this->assertInstanceOf(Entity\Artist::class, $artist);
        $this->assertSame(2, $artist->getId());
        $this->assertSame(1, $artist->getAlbums()->count());
        $this->assertSame("Britney Spears", $artist->getName());
        $this->assertSame(2, $artist->getType()->getId());
    }

    public function testFindArtistType()
    {
        $type = static::$entityManager->getRepository(Entity\Artist\Type::class)->find(2);
        $this->assertInstanceOf(Entity\Artist\Type::class, $type);
        $this->assertSame(2, $type->getId());
        $this->assertSame("Solo", $type->getName());
        $this->assertGreaterThan(0, $type->getArtists()->count());
    }

    public function testFindGenre()
    {
        $genre = static::$entityManager->getRepository(Entity\Genre::class)->find(3);
        $this->assertInstanceOf(Entity\Genre::class, $genre);
        $this->assertSame(3, $genre->getId());
        $this->assertSame("Pop", $genre->getName());
        $this->assertSame(2, $genre->getSongs()->count());
    }

    public function testFindSong()
    {
        $song = static::$entityManager->getRepository(Entity\Song::class)->find(1);
        $this->assertInstanceOf(Entity\Song::class, $song);
        $this->assertSame(1, $song->getId());
        $this->assertSame("...Baby One More Time", $song->getName());
        $this->assertSame("2017-01-01 15:00:00", $song->getTimeCreated()->format('Y-m-d H:i:s'));
        $this->assertSame(1, $song->getAlbums()->count());
        $this->assertSame(3, $song->getGenre()->getId());
    }
}

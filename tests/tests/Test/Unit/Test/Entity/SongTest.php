<?php
namespace IST\DoctrineFirebirdDriver\Test\Unit\Test\Resource\Entity;

use Doctrine\Common\Collections\Collection;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class SongTest extends \PHPUnit_Framework_TestCase
{
    public function testBasics()
    {
        $genre = $this
            ->getMockBuilder(Entity\Genre::class)
            ->disableOriginalConstructor()
            ->getMock();
        $song = new Entity\Song("Foo", $genre);
        $this->assertNull($song->getId());
        $this->assertInstanceOf(Collection::class, $song->getAlbums());
        $this->assertCount(0, $song->getAlbums());
        $this->assertNull($song->getArtist());
        $this->assertSame($genre, $song->getGenre());
        $this->assertSame("Foo", $song->getName());
        $this->assertInstanceOf(\DateTimeImmutable::class, $song->getTimeCreated());
    }

    public function testAddAndRemoveAlbum()
    {
        $genre = $this
            ->getMockBuilder(Entity\Genre::class)
            ->disableOriginalConstructor()
            ->getMock();
        $song = new Entity\Song("Foo", $genre);
        $albumA = $this
            ->getMockBuilder(Entity\Album::class)
            ->disableOriginalConstructor()
            ->getMock();
        $albumB = clone $albumA;
        $song->removeAlbum($albumA);
        $this->assertCount(0, $song->getAlbums());
        $song->addAlbum($albumA);
        $song->addAlbum($albumA);
        $this->assertCount(1, $song->getAlbums());
        $song->addAlbum($albumB);
        $this->assertCount(2, $song->getAlbums());
        $song->removeAlbum($albumA);
        $song->removeAlbum($albumA);
        $this->assertCount(1, $song->getAlbums());
        $song->removeAlbum($albumB);
        $this->assertCount(0, $song->getAlbums());
    }

    public function testSetArtistWorks()
    {
        $genre = $this
            ->getMockBuilder(Entity\Genre::class)
            ->disableOriginalConstructor()
            ->getMock();
        $song = new Entity\Song("Foo", $genre);
        $artist = $this
            ->getMockBuilder(Entity\Artist::class)
            ->disableOriginalConstructor()
            ->getMock();
        $song->setArtist($artist);
        $this->assertSame($artist, $song->getArtist());
        $song->setArtist(null);
        $this->assertNull($song->getArtist());
    }

    public function testSetGenreWorks()
    {
        $genreA = $this
            ->getMockBuilder(Entity\Genre::class)
            ->disableOriginalConstructor()
            ->getMock();
        $genreB = clone $genreA;
        $song = new Entity\Song("Foo", $genreA);
        $this->assertSame($genreA, $song->getGenre());
        $song->setGenre($genreB);
        $this->assertNotSame($genreA, $song->getGenre());
        $this->assertSame($genreB, $song->getGenre());
        $song->setGenre($genreA);
        $this->assertNotSame($genreB, $song->getGenre());
        $this->assertSame($genreA, $song->getGenre());
    }

    public function testSetNameWorks()
    {
        $genre = $this
            ->getMockBuilder(Entity\Genre::class)
            ->disableOriginalConstructor()
            ->getMock();
        $song = new Entity\Song("Foo", $genre);
        $song->setName("Bar");
        $this->assertSame("Bar", $song->getName());
    }
}

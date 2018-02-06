<?php
namespace IST\DoctrineFirebirdDriver\Test\Unit\Test\Resource\Entity;

use Doctrine\Common\Collections\Collection;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class AlbumTest extends \PHPUnit_Framework_TestCase
{
    public function testBasics()
    {
        $album = new Entity\Album("Communion");
        $this->assertNull($album->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $album->getTimeCreated());
        $this->assertSame("Communion", $album->getName());
        $this->assertNull($album->getArtist());
        $this->assertInstanceOf(Collection::class, $album->getSongs());
        $this->assertCount(0, $album->getSongs());
    }

    public function testCanAddAndRemoveSong()
    {
        $album = new Entity\Album("Communion");
        $song = $this
            ->getMockBuilder(Entity\Song::class)
            ->disableOriginalConstructor()
            ->getMock();
        $album->addSong($song);
        $this->assertCount(1, $album->getSongs());
        $this->assertSame($song, $album->getSongs()->first());
        $album->removeSong($song);
        $this->assertCount(0, $album->getSongs());
    }

    public function testSetArtist()
    {
        $album = new Entity\Album("Communion");
        $artistA = $this
            ->getMockBuilder(Entity\Artist::class)
            ->disableOriginalConstructor()
            ->getMock();
        $artistB = clone $artistA;
        $album->setArtist($artistA);
        $this->assertSame($artistA, $album->getArtist());
        $album->setArtist($artistB);
        $this->assertSame($artistB, $album->getArtist());
        $album->setArtist(null);
        $this->assertNull($album->getArtist());
    }

    public function testSetName()
    {
        $album = new Entity\Album("Communion");
        $album->setName("Something else");
        $this->assertSame("Something else", $album->getName());
    }
}

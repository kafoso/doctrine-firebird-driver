<?php
namespace IST\DoctrineFirebirdDriver\Test\Unit\Test\Resource\Entity;

use Doctrine\Common\Collections\Collection;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class ArtistTest extends \PHPUnit_Framework_TestCase
{
    public function testBasics()
    {
        $artistType = $this
            ->getMockBuilder(Entity\Artist\Type::class)
            ->disableOriginalConstructor()
            ->getMock();
        $artist = new Entity\Artist("Foo", $artistType);
        $this->assertNull($artist->getId());
        $this->assertInstanceOf(Collection::class, $artist->getAlbums());
        $this->assertCount(0, $artist->getAlbums());
        $this->assertSame("Foo", $artist->getName());
        $this->assertSame($artistType, $artist->getType());
    }

    public function testAddAndRemoveAlbumWorks()
    {
        $artistType = $this
            ->getMockBuilder(Entity\Artist\Type::class)
            ->disableOriginalConstructor()
            ->getMock();
        $artist = new Entity\Artist("Foo", $artistType);
        $albumA = $this
            ->getMockBuilder(Entity\Album::class)
            ->disableOriginalConstructor()
            ->getMock();
        $albumB = clone $albumA;
        $artist->removeAlbum($albumA);
        $this->assertCount(0, $artist->getAlbums());
        $artist->addAlbum($albumA);
        $this->assertCount(1, $artist->getAlbums());
        $artist->addAlbum($albumB);
        $this->assertCount(2, $artist->getAlbums());
        $artist->addAlbum($albumA);
        $artist->addAlbum($albumB);
        $this->assertCount(2, $artist->getAlbums());
        $artist->removeAlbum($albumA);
        $this->assertCount(1, $artist->getAlbums());
        $artist->removeAlbum($albumA);
        $this->assertCount(1, $artist->getAlbums());
        $artist->removeAlbum($albumB);
        $this->assertCount(0, $artist->getAlbums());
    }

    public function testSetNameWorks()
    {
        $artistType = $this
            ->getMockBuilder(Entity\Artist\Type::class)
            ->disableOriginalConstructor()
            ->getMock();
        $artist = new Entity\Artist("Foo", $artistType);
        $artist->setName("Bar");
        $this->assertSame("Bar", $artist->getName());
    }

    public function testSetType()
    {
        $artistTypeA = $this
            ->getMockBuilder(Entity\Artist\Type::class)
            ->disableOriginalConstructor()
            ->getMock();
        $artistTypeB = clone $artistTypeA;
        $artist = new Entity\Artist("Foo", $artistTypeA);
        $artist->setType($artistTypeB);
        $this->assertNotSame($artistTypeA, $artist->getType());
        $this->assertSame($artistTypeB, $artist->getType());
    }
}

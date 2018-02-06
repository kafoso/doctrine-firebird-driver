<?php
namespace IST\DoctrineFirebirdDriver\Test\Unit\Test\Resource\Entity;

use Doctrine\Common\Collections\Collection;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class GenreTest extends \PHPUnit_Framework_TestCase
{
    public function testBasics()
    {
        $genre = new Entity\Genre("Foo");
        $this->assertNull($genre->getId());
        $this->assertInstanceOf(Collection::class, $genre->getSongs());
        $this->assertCount(0, $genre->getSongs());
        $this->assertSame("Foo", $genre->getName());
    }

    public function testAddAndRemoveSongWorks()
    {
        $genre = new Entity\Genre("Foo");
        $song = $this
            ->getMockBuilder(Entity\Song::class)
            ->disableOriginalConstructor()
            ->getMock();
        $song
            ->expects($this->any())
            ->method('getGenre')
            ->will($this->returnValue($genre));
        $genre->addSong($song);
        $this->assertCount(1, $genre->getSongs());
        $this->assertSame($song, $genre->getSongs()->first());
        $this->assertSame($genre, $genre->getSongs()->first()->getGenre());
        $genre->removeSong($song);
        $this->assertCount(0, $genre->getSongs());
    }

    public function testSetNameWorks()
    {
        $genre = new Entity\Genre("Foo");
        $genre->setName("Bar");
        $this->assertSame("Bar", $genre->getName());
    }
}

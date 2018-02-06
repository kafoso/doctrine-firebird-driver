<?php
namespace IST\DoctrineFirebirdDriver\Test\Unit\Test\Resource\Entity\Artist;

use Doctrine\Common\Collections\Collection;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class TypeTest extends \PHPUnit_Framework_TestCase
{
    public function testBasics()
    {
        $type = new Entity\Artist\Type("Foo");
        $this->assertNull($type->getId());
        $this->assertSame("Foo", $type->getName());
        $this->assertInstanceOf(Collection::class, $type->getArtists());
        $this->assertCount(0, $type->getArtists());
    }

    public function testAddAndRemoveArtist()
    {
        $type = new Entity\Artist\Type("Foo");
        $artistA = $this
            ->getMockBuilder(Entity\Artist::class)
            ->disableOriginalConstructor()
            ->getMock();
        $artistB = clone $artistA;
        $type->removeArtist($artistA);
        $this->assertCount(0, $type->getArtists());
        $type->addArtist($artistA);
        $type->addArtist($artistA);
        $this->assertCount(1, $type->getArtists());
        $type->addArtist($artistB);
        $this->assertCount(2, $type->getArtists());
        $type->removeArtist($artistA);
        $type->removeArtist($artistA);
        $this->assertCount(1, $type->getArtists());
        $type->removeArtist($artistB);
        $this->assertCount(0, $type->getArtists());
    }

    public function testSetNameWorks()
    {
        $type = new Entity\Artist\Type("Foo");
        $type->setName("Bar");
        $this->assertSame("Bar", $type->getName());
    }
}

<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Doctrine\EntityManager\Detach;

use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

/**
 * @runTestsInSeparateProcesses
 */
class AlbumTest extends AbstractIntegrationTest
{
    public function testCanDetatch()
    {
        $albumA = new Entity\Album("Foo");
        $this->assertNull($albumA->getId());
        $this->_entityManager->persist($albumA);
        $this->_entityManager->flush($albumA);
        $id = $albumA->getId();
        $this->assertSame("Foo", $albumA->getName());
        $albumB = $this->_entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertSame($albumB, $albumA);
        $this->_entityManager->detach($albumA);
        $albumA->setName("Bar");
        $this->assertSame("Bar", $albumA->getName());
        $this->_entityManager->flush();
        $albumC = $this->_entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertSame("Foo", $albumC->getName());
        $this->assertNotSame($albumA, $albumC);
    }
}

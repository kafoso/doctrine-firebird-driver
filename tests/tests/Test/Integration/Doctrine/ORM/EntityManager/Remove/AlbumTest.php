<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\EntityManager\Remove;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class AlbumTest extends AbstractIntegrationTest
{
    public function testCanRemove()
    {
        $album = new Entity\Album("Some album " . __FUNCTION__);
        $this->_entityManager->persist($album);
        $this->_entityManager->flush($album);
        $this->assertInternalType('int', $album->getId());
        $id = $album->getId();
        $album = $this->_entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertInternalType('int', $album->getId());
        $this->assertInstanceOf(Entity\Album::class, $album);
        $this->_entityManager->remove($album);
        $this->_entityManager->flush($album);
        $this->assertNotNull($album);
        $album = $this->_entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertNull($album);
    }

    public function testCascaingRemoveWorks()
    {
        $subclass = new Entity\Cases\CascadingRemove\Subclass;
        $this->_entityManager->persist($subclass);
        $this->_entityManager->flush($subclass);
        $this->assertInternalType('int', $subclass->getId());
        $cascadingRemove = new Entity\Cases\CascadingRemove($subclass);
        $this->_entityManager->persist($cascadingRemove);
        $this->_entityManager->flush($cascadingRemove);
        $this->assertInternalType('int', $cascadingRemove->getId());
        $cascadingRemoveId = $cascadingRemove->getId();
        $subclassId = $cascadingRemove->getSubclass()->getId();
        $this->_entityManager->remove($cascadingRemove);
        $this->_entityManager->flush($cascadingRemove);
        $cascadingRemove = $this->_entityManager->getRepository(Entity\Cases\CascadingRemove::class)->find($cascadingRemoveId);
        $this->assertNull($cascadingRemove);
        $subclass = $this->_entityManager->getRepository(Entity\Cases\CascadingRemove\Subclass::class)->find($subclassId);
        $this->assertNull($subclass);
    }
}

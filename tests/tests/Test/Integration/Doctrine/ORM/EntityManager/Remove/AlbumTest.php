<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\EntityManager\Remove;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class AlbumTest extends AbstractIntegrationTest
{
    public function testCanRemove()
    {
        $album = new Entity\Album("Some album " . __FUNCTION__);
        static::$entityManager->persist($album);
        static::$entityManager->flush($album);
        $this->assertInternalType('int', $album->getId());
        $id = $album->getId();
        $album = static::$entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertInternalType('int', $album->getId());
        $this->assertInstanceOf(Entity\Album::class, $album);
        static::$entityManager->remove($album);
        static::$entityManager->flush($album);
        $album = static::$entityManager->getRepository(Entity\Album::class)->find($id);
        $this->assertNull($album);
    }

    public function testCascaingRemoveWorks()
    {
        $subclass = new Entity\Cases\CascadingRemove\Subclass;
        static::$entityManager->persist($subclass);
        static::$entityManager->flush($subclass);
        $this->assertInternalType('int', $subclass->getId());
        $cascadingRemove = new Entity\Cases\CascadingRemove($subclass);
        static::$entityManager->persist($cascadingRemove);
        static::$entityManager->flush($cascadingRemove);
        $this->assertInternalType('int', $cascadingRemove->getId());
        $cascadingRemoveId = $cascadingRemove->getId();
        $subclassId = $cascadingRemove->getSubclass()->getId();
        static::$entityManager->remove($cascadingRemove);
        static::$entityManager->flush($cascadingRemove);
        $cascadingRemove = static::$entityManager->getRepository(Entity\Cases\CascadingRemove::class)->find($cascadingRemoveId);
        $this->assertNull($cascadingRemove);
        $subclass = static::$entityManager->getRepository(Entity\Cases\CascadingRemove\Subclass::class)->find($subclassId);
        $this->assertNull($subclass);
    }
}

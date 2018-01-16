<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\EntityManager\Repository;

use IST\DoctrineFirebirdDriver\Test\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Entity;

class FindAllTest extends AbstractIntegrationTest
{
    public function testFindByAlbum()
    {
        $albums = static::$entityManager->getRepository(Entity\Album::class)->findAll();
        $this->assertGreaterThan(2, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertInstanceOf(Entity\Album::class, $albums[1]);
        $this->assertSame(1, $albums[0]->getId());
        $this->assertSame(2, $albums[1]->getId());
    }
}

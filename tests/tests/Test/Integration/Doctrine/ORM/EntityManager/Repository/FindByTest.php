<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\EntityManager\Repository;

use IST\DoctrineFirebirdDriver\Test\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Entity;

class FindByTest extends AbstractIntegrationTest
{
    public function testFindByAlbum()
    {
        $albums = static::$entityManager->getRepository(Entity\Album::class)->findBy([
            "id" => 1,
        ]);
        $this->assertCount(1, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }
}

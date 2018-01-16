<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\EntityManager\Repository;

use IST\DoctrineFirebirdDriver\Test\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Entity;

class FindOneByTest extends AbstractIntegrationTest
{
    public function testFindOneByAlbum()
    {
        $album = static::$entityManager->getRepository(Entity\Album::class)->findOneBy([
            "id" => 1,
        ]);
        $this->assertInstanceOf(Entity\Album::class, $album);
        $this->assertSame(1, $album->getId());
    }
}

<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\EntityManager\Repository;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class FindOneByTest extends AbstractIntegrationTest
{
    public function testFindOneByAlbum()
    {
        $album = $this->_entityManager->getRepository(Entity\Album::class)->findOneBy([
            "id" => 1,
        ]);
        $this->assertInstanceOf(Entity\Album::class, $album);
        $this->assertSame(1, $album->getId());
    }
}

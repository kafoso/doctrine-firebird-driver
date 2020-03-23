<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\EntityManager\Repository;

use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

class FindByTest extends AbstractIntegrationTest
{
    /**
     * @runInSeparateProcess
     */
    public function testFindByAlbum()
    {
        $albums = $this->_entityManager->getRepository(Entity\Album::class)->findBy([
            "id" => 1,
        ]);
        $this->assertCount(1, $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }
}

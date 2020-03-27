<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\EntityManager\Repository;

use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

class FindAllTest extends AbstractIntegrationTest
{
    /**
     * @runInSeparateProcess
     */
    public function testFindByAlbum()
    {
        $albums = $this->_entityManager->getRepository(Entity\Album::class)->findAll();
        $this->assertGreaterThan(2, $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertArrayHasKey(1, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[1]);
        $this->assertSame(1, $albums[0]->getId());
        $this->assertSame(2, $albums[1]->getId());
    }
}

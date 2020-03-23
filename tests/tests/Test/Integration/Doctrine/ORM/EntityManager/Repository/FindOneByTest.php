<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\EntityManager\Repository;

use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

/**
 * @runTestsInSeparateProcesses
 */
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

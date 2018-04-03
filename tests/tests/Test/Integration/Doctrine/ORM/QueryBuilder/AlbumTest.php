<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\QueryBuilder;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

/**
 * @runTestsInSeparateProcesses
 */
class AlbumTest extends AbstractIntegrationTest
{
    public function testSelect()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }

    public function testSelectColumn()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album.id')
            ->from(Entity\Album::class, 'album');
        $expectedDQL = "SELECT album.id FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0 FROM ALBUM a0_";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $ids = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $ids);
        $this->assertGreaterThan(0, count($ids));
        $this->assertArrayHasKey(0, $ids);
        $this->assertArrayHasKey('id', $ids[0]);
        $this->assertSame(1, $ids[0]['id']);
    }

    public function testSelectWithJoin()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->join('album.artist', 'artist'); // Inherited join
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " INNER JOIN album.artist artist";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ INNER JOIN ARTIST a1_ ON a0_.artist_id = a1_.id";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
        $this->assertSame(2, $albums[0]->getArtist()->getId());
    }

    public function testSelectWithManualJoin()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->join(Entity\Artist::class, 'artist', 'WITH', 'artist = album.artist'); // Manual join
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " INNER JOIN IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Artist artist WITH artist = album.artist";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_";
        $expectedSQL .= " INNER JOIN ARTIST a1_ ON (a1_.id = a0_.artist_id)";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
        $this->assertSame(2, $albums[0]->getArtist()->getId());
    }

    public function testSelectWithLeftJoinWhereJoinedElementExists()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->leftJoin('album.artist', 'artist');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " LEFT JOIN album.artist artist";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ LEFT JOIN ARTIST a1_ ON a0_.artist_id = a1_.id";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
        $this->assertSame(2, $albums[0]->getArtist()->getId());
    }

    public function testSelectWithLeftJoinWhereJoinedElementIsNull()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album', 'artist')
            ->from(Entity\Album::class, 'album')
            ->leftJoin(Entity\Artist::class, 'artist', 'WITH', 'artist.id = 0');
        $expectedDQL = "SELECT album, artist FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " LEFT JOIN IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Artist artist";
        $expectedDQL .= " WITH artist.id = 0";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a1_.id AS ID_3,";
        $expectedSQL .= " a1_.name AS NAME_4, a0_.artist_id AS ARTIST_ID_5, a1_.type_id AS TYPE_ID_6 FROM ALBUM a0_";
        $expectedSQL .= " LEFT JOIN ARTIST a1_ ON (a1_.id = 0)";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $results = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $results);
        $this->assertArrayHasKey(0, $results);
        $this->assertInstanceOf(Entity\Album::class, $results[0]);
        $this->assertSame(1, $results[0]->getId());
        $this->assertInstanceOf(Entity\Artist::class, $results[0]->getArtist());
        $this->assertSame(2, $results[0]->getArtist()->getId());
        $this->assertNull($results[1]);
    }

    public function testSelectWithWhere()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id > 1');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " WHERE album.id > 1";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id > 1";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(2, $albums[0]->getId());
    }

    public function testSelectWithLimit()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id > 0')
            ->setMaxResults(1);
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " WHERE album.id > 0";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id > 0 ROWS 1";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertCount(1, $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }

    public function testSelectWithOffset()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id > 0')
            ->setFirstResult(1);
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " WHERE album.id > 0";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id > 0 ROWS 2 TO 9000000000000000000";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(2, $albums[0]->getId());
    }

    public function testSelectWithOffsetAndLimit()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id > 0')
            ->setFirstResult(1)
            ->setMaxResults(1);
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " WHERE album.id > 0";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id > 0 ROWS 2 TO 2";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertCount(1, $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(2, $albums[0]->getId());
    }

    public function testSelectWithWhereAndParameters()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id = :id')
            ->setParameter('id', 1);
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " WHERE album.id = :id";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id = ?";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }

    public function testSelectWithGroupBy()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album.id')
            ->from(Entity\Album::class, 'album')
            ->join('album.artist', 'artist')
            ->groupBy('album.id');
        $expectedDQL = "SELECT album.id FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " INNER JOIN album.artist artist GROUP BY album.id";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0 FROM ALBUM a0_ INNER JOIN ARTIST a1_ ON a0_.artist_id = a1_.id GROUP BY a0_.id";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albumIds = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albumIds);
        $this->assertCount(2, $albumIds);
        $this->assertArrayHasKey(0, $albumIds);
        $this->assertArrayHasKey('id', $albumIds[0]);
        $this->assertSame(1, $albumIds[0]['id']);
    }

    public function testSelectWithHaving()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album.id')
            ->from(Entity\Album::class, 'album')
            ->join('album.artist', 'artist')
            ->groupBy('album.id')
            ->having('album.id > 1');
        $expectedDQL = "SELECT album.id FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " INNER JOIN album.artist artist GROUP BY album.id HAVING album.id > 1";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0 FROM ALBUM a0_ INNER JOIN ARTIST a1_ ON a0_.artist_id = a1_.id";
        $expectedSQL .= " GROUP BY a0_.id HAVING a0_.id > 1";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albumIds = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albumIds);
        $this->assertCount(1, $albumIds);
        $this->assertArrayHasKey(0, $albumIds);
        $this->assertArrayHasKey('id', $albumIds[0]);
        $this->assertSame(2, $albumIds[0]['id']);
    }

    public function testSelectWithOrderBy()
    {
        $qb = $this->_entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->orderBy('album.id', 'DESC');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " ORDER BY album.id DESC";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2,";
        $expectedSQL .= " a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ ORDER BY a0_.id DESC";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertNotSame(1, $albums[0]->getId());
    }

    public function testSelectWithSubselect()
    {
        $qb1 = $this->_entityManager->createQueryBuilder();
        $qb2 = $this->_entityManager->createQueryBuilder();
        $qb1
            ->select('artist')
            ->from(Entity\Artist::class, 'artist')
            ->where('artist.id = 2');
        $qb2
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where($qb2->expr()->in('album.artist', $qb1->getDQL()));
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $expectedDQL .= " WHERE album.artist IN(SELECT artist";
        $expectedDQL .= " FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Artist artist WHERE artist.id = 2)";
        $this->assertSame($expectedDQL, $qb2->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.artist_id IN (SELECT a1_.id FROM ARTIST a1_ WHERE a1_.id = 2)";
        $this->assertSame($expectedSQL, $qb2->getQuery()->getSQL());
        $albums = $qb2->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertCount(1, $albums);
        $this->assertArrayHasKey(0, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }
}

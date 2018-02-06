<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration\Doctrine\ORM\QueryBuilder;

use IST\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

class AlbumTest extends AbstractIntegrationTest
{
    public function testSelect()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }

    public function testSelectColumn()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album.id')
            ->from(Entity\Album::class, 'album');
        $expectedDQL = "SELECT album.id FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0 FROM ALBUM a0_";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $ids = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $ids);
        $this->assertSame(1, $ids[0]['id']);
    }

    public function testSelectWithJoin()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->join('album.artist', 'artist');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album INNER JOIN album.artist artist";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ INNER JOIN ARTIST a1_ ON a0_.artist_id = a1_.id";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
        $this->assertSame(2, $albums[0]->getArtist()->getId());
    }

    public function testSelectWithLeftJoinWhereJoinedElementExists()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->leftJoin('album.artist', 'artist');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album LEFT JOIN album.artist artist";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ LEFT JOIN ARTIST a1_ ON a0_.artist_id = a1_.id";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
        $this->assertSame(2, $albums[0]->getArtist()->getId());
    }

    public function testSelectWithLeftJoinWhereJoinedElementIsNull()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album', 'artist')
            ->from(Entity\Album::class, 'album')
            ->leftJoin(Entity\Artist::class, 'artist', 'WITH', 'artist.id = 0');
        $expectedDQL = "SELECT album, artist FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album LEFT JOIN IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Artist artist WITH artist.id = 0";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a1_.id AS ID_3, a1_.name AS NAME_4, a0_.artist_id AS ARTIST_ID_5, a1_.type_id AS TYPE_ID_6 FROM ALBUM a0_ LEFT JOIN ARTIST a1_ ON (a1_.id = 0)";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $results = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $results);
        $this->assertInstanceOf(Entity\Album::class, $results[0]);
        $this->assertSame(1, $results[0]->getId());
        $this->assertSame(2, $results[0]->getArtist()->getId());
        $this->assertNull($results[1]);
    }

    public function testSelectWithWhere()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id > 1');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album WHERE album.id > 1";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id > 1";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(2, $albums[0]->getId());
    }

    public function testSelectWithLimit()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id > 0')
            ->setMaxResults(1);
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album WHERE album.id > 0";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id > 0 ROWS 1";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertCount(1, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }

    public function testSelectWithOffset()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id > 0')
            ->setFirstResult(1);
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album WHERE album.id > 0";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id > 0 ROWS 2 TO 9000000000000000000";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(2, $albums[0]->getId());
    }

    public function testSelectWithOffsetAndLimit()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id > 0')
            ->setFirstResult(1)
            ->setMaxResults(1);
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album WHERE album.id > 0";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id > 0 ROWS 2 TO 2";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertCount(1, $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(2, $albums[0]->getId());
    }

    public function testSelectWithWhereAndParameters()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->where('album.id = :id')
            ->setParameter('id', 1);
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album WHERE album.id = :id";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id = ?";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }

    /** XXX
    public function testSelectWithGroupBy()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->join('album.artist', 'artist')
            ->groupBy('album.id');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album WHERE album.id = :id"; // XXX Fix
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id = ?"; // XXX Fix
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }
    */

    /** XXX
    public function testSelectWithHaving()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->join('album.artist', 'artist')
            ->groupBy('album.id')
            ->having('COUNT(artist.id) > 0');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album WHERE album.id = :id"; // XXX Fix
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ WHERE a0_.id = ?"; // XXX Fix
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertSame(1, $albums[0]->getId());
    }
    */

    public function testSelectWithOrderBy()
    {
        $qb = static::$entityManager->createQueryBuilder();
        $qb
            ->select('album')
            ->from(Entity\Album::class, 'album')
            ->orderBy('album.id', 'DESC');
        $expectedDQL = "SELECT album FROM IST\\DoctrineFirebirdDriver\\Test\\Resource\\Entity\\Album album ORDER BY album.id DESC";
        $this->assertSame($expectedDQL, $qb->getQuery()->getDQL());
        $expectedSQL = "SELECT a0_.id AS ID_0, a0_.timeCreated AS TIMECREATED_1, a0_.name AS NAME_2, a0_.artist_id AS ARTIST_ID_3 FROM ALBUM a0_ ORDER BY a0_.id DESC";
        $this->assertSame($expectedSQL, $qb->getQuery()->getSQL());
        $albums = $qb->getQuery()->getResult();
        $this->assertInternalType('array', $albums);
        $this->assertInstanceOf(Entity\Album::class, $albums[0]);
        $this->assertNotSame(1, $albums[0]->getId());
    }
}

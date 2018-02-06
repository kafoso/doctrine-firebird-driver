<?php
use IST\DoctrineFirebirdDriver\Test\Resource\Entity;

require(__DIR__ . "/tests/phpunit.bootstrap.php");

$album = $entityManager->getRepository(Entity\Album::class)->find(1);
echo "<pre>";var_dump("kafoso [] ".__FILE__."::".__LINE__, $album->getSongs());die("</pre>");

$result = $entityManager->getConnection()->query("SELECT a.RDB\$RELATION_NAME
FROM RDB\$RELATIONS a
WHERE RDB\$SYSTEM_FLAG = 0 AND RDB\$RELATION_TYPE = 0");

echo "<pre>";var_dump("kafoso [] ".__FILE__."::".__LINE__, $result->fetchAll());die("</pre>");

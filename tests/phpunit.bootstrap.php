<?php
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase;

require(__DIR__ . "/../script/bootstrap.php");
$loader->setPsr4('IST\\DoctrineFirebirdDriver\\Test\\', __DIR__ . "/resources/Test");

$cache = new \Doctrine\Common\Cache\ArrayCache;
$doctrineConfiguration = new Configuration;
$driverImpl = $doctrineConfiguration->newDefaultAnnotationDriver([__DIR__ . '/resources/Test/Entity'], false);
$doctrineConfiguration->setMetadataDriverImpl($driverImpl);
$doctrineConfiguration->setProxyDir(__DIR__ . '/../var/doctrine-proxies');
$doctrineConfiguration->setProxyNamespace('DoctrineFirebirdDriver\Proxies');
$doctrineConfiguration->setAutoGenerateProxyClasses(true);

$configuration = new FirebirdInterbase\Configuration(
    'localhost',
    null,
    '/var/lib/firebird/2.5/data/music_library.fdb',
    'SYSDBA',
    '88fb9f307125cc397f70e59c749715e1',
    'UTF-8'
);
$driver = new FirebirdInterbase\Driver($configuration);
$doctrineConnection = new Connection([], $driver, $doctrineConfiguration);
$entityManager = EntityManager::create($doctrineConnection, $doctrineConfiguration);

if (file_exists($configuration->getDatabase())) {
    unlink($configuration->getDatabase());
}

$cmd = sprintf(
    "isql-fb -input %s 2>&1",
    escapeshellarg(__DIR__ . "/resources/database_create.sql")
);
//echo $cmd . PHP_EOL; // XXX
exec($cmd);

chmod($configuration->getDatabase(), 0777);

$cmd = sprintf(
    "isql-fb %s -input %s -password %s -user %s",
    escapeshellarg($configuration->getDatabase()),
    escapeshellarg(__DIR__ . "/resources/database_setup.sql"),
    escapeshellarg($configuration->getPassword()),
    escapeshellarg($configuration->getUsername())
);
//echo $cmd . PHP_EOL; // XXX
exec($cmd);

\IST\DoctrineFirebirdDriver\Test\AbstractIntegrationTest::startup($entityManager);

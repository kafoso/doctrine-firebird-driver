<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use IST\DoctrineFirebirdDriver\Driver\FirebirdInterbase;
use IST\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;

abstract class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    protected $_entityManager;
    protected $_platform;

    public function setUp()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache;
        $doctrineConfiguration = new Configuration;
        $driverImpl = $doctrineConfiguration->newDefaultAnnotationDriver([ROOT_PATH . '/tests/resources/Test/Entity'], false);
        $doctrineConfiguration->setMetadataDriverImpl($driverImpl);
        $doctrineConfiguration->setProxyDir(ROOT_PATH . '/var/doctrine-proxies');
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
        $doctrineConnection->setNestTransactionsWithSavepoints(true);
        $this->_entityManager = EntityManager::create($doctrineConnection, $doctrineConfiguration);

        if (file_exists($configuration->getDatabase())) {
            unlink($configuration->getDatabase());
        }

        $cmd = sprintf(
            "isql-fb -input %s 2>&1",
            escapeshellarg(ROOT_PATH . "/tests/resources/database_create.sql")
        );
        exec($cmd);

        chmod($configuration->getDatabase(), 0777);

        $cmd = sprintf(
            "isql-fb %s -input %s -password %s -user %s",
            escapeshellarg($configuration->getDatabase()),
            escapeshellarg(ROOT_PATH . "/tests/resources/database_setup.sql"),
            escapeshellarg($configuration->getPassword()),
            escapeshellarg($configuration->getUsername())
        );
        exec($cmd);

        $this->_platform = new FirebirdInterbasePlatform;
    }

    protected static function statementArrayToText(array $statements)
    {
        $statements = array_filter($statements, function($statement){
            return is_string($statement);
        });
        if ($statements) {
            $indent = "    ";
            array_walk($statements, function(&$v) use ($indent){
                $v = $indent . $v;
            });
            return PHP_EOL . implode(PHP_EOL, $statements);
        }
        return "";
    }
}

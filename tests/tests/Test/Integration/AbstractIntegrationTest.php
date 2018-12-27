<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase;
use Kafoso\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;

abstract class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_DATABASE_FILE_PATH = '/var/lib/firebird/2.5/data/music_library.fdb';
    const DEFAULT_DATABASE_USERNAME = 'SYSDBA';
    const DEFAULT_DATABASE_PASSWORD = '88fb9f307125cc397f70e59c749715e1';

    protected $_entityManager;
    protected $_platform;

    public function setUp()
    {
        $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
        $configurationArray = static::getSetUpDoctrineConfigurationArray();
        static::installFirebirdDatabase($configurationArray);
        $this->_entityManager = static::createEntityManager($doctrineConfiguration, $configurationArray);
        $this->_platform = new FirebirdInterbasePlatform;
    }

    public function tearDown()
    {
        if ($this->_entityManager) {
            $this->_entityManager->getConnection()->close();
        }
    }

    /**
     * @return EntityManager
     */
    protected static function createEntityManager(Configuration $configuration, array $configurationArray)
    {
        $doctrineConnection = new Connection(
            $configurationArray,
            new FirebirdInterbase\Driver,
            $configuration
        );
        $doctrineConnection->setNestTransactionsWithSavepoints(true);
        return EntityManager::create($doctrineConnection, $configuration);
    }

    protected static function installFirebirdDatabase(array $configurationArray)
    {
        if (file_exists($configurationArray['dbname'])) {
            unlink($configurationArray['dbname']); // Don't do this outside tests
        }

        $cmd = sprintf(
            "isql-fb -input %s 2>&1",
            escapeshellarg(ROOT_PATH . "/tests/resources/database_create.sql")
        );
        exec($cmd);

        chmod($configurationArray['dbname'], 0777);

        $cmd = sprintf(
            "isql-fb %s -input %s -password %s -user %s",
            escapeshellarg($configurationArray['dbname']),
            escapeshellarg(ROOT_PATH . "/tests/resources/database_setup.sql"),
            escapeshellarg($configurationArray['password']),
            escapeshellarg($configurationArray['user'])
        );
        exec($cmd);
    }

    /**
     * @return string
     */
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

    /**
     * @return Configuration
     */
    protected static function getSetUpDoctrineConfiguration()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache;
        $doctrineConfiguration = new Configuration;
        $driverImpl = $doctrineConfiguration->newDefaultAnnotationDriver([ROOT_PATH . '/tests/resources/Test/Entity'], false);
        $doctrineConfiguration->setMetadataDriverImpl($driverImpl);
        $doctrineConfiguration->setProxyDir(ROOT_PATH . '/var/doctrine-proxies');
        $doctrineConfiguration->setProxyNamespace('DoctrineFirebirdDriver\Proxies');
        $doctrineConfiguration->setAutoGenerateProxyClasses(true);
        return $doctrineConfiguration;
    }

    /**
     * @return array
     */
    protected static function getSetUpDoctrineConfigurationArray(array $overrideConfigs = [])
    {
        return [
            'host' => 'localhost',
            'dbname' => static::DEFAULT_DATABASE_FILE_PATH,
            'user' => static::DEFAULT_DATABASE_USERNAME,
            'password' => static::DEFAULT_DATABASE_PASSWORD,
            'charset' => 'UTF-8',
        ];
    }
}

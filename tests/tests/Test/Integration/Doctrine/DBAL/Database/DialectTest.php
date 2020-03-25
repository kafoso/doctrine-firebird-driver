<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Integration\Doctrine\DBAL\Database;

use Kafoso\DoctrineFirebirdDriver\Test\Integration\AbstractIntegrationTest;

/**
 * Tests based on table from:
 * @link https://www.firebirdsql.org/pdfmanual/html/isql-dialects.html
 *
 * @runTestsInSeparateProcesses
 */
class DialectTest extends AbstractIntegrationTest
{
    /**
     * @override
     */
    public function setUp()
    {

    }

    public function testDialect3()
    {
        $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
        $configurationArray = static::getSetUpDoctrineConfigurationArray([
            'dialect' => 3,
        ]);
        static::installFirebirdDatabase($configurationArray);
        $entityManager = static::createEntityManager($doctrineConfiguration, $configurationArray);
        $connection = $entityManager->getConnection();

        $stmt = $connection->prepare("SELECT CAST(CAST('2018-01-01' AS DATE) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertSame(100, strlen($result['TXT']));
        $this->assertStringStartsWith("2018-01-01", $result['TXT']);

        $stmt = $connection->prepare("SELECT CAST(CAST('2018-01-01 00:00:00' AS TIMESTAMP) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertSame(100, strlen($result['TXT']));
        $this->assertSame("2018-01-01 00:00:00.0000", rtrim($result['TXT']));

        $stmt = $connection->prepare("SELECT CAST(CAST('00:00:00' AS TIME) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertSame(100, strlen($result['TXT']));
        $this->assertSame("00:00:00.0000", rtrim($result['TXT']));

        $stmt = $connection->prepare("SELECT a.\"ID\" FROM Album AS a");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertInternalType("array", $result);
        $this->assertArrayHasKey("ID", $result);
        $this->assertSame(1, $result["ID"]);

        $stmt = $connection->prepare("SELECT 1/3 AS NUMBER FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertInternalType("array", $result);
        $this->assertArrayHasKey("NUMBER", $result);
        $this->assertInternalType("integer", $result["NUMBER"]);
        $this->assertSame(0, $result["NUMBER"]);

        $entityManager->getConnection()->close();
    }

    public function testDialect1()
    {
        $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
        $configurationArray = static::getSetUpDoctrineConfigurationArray([
            'dialect' => 1,
        ]);
        static::installFirebirdDatabase($configurationArray);
        $entityManager = static::createEntityManager($doctrineConfiguration, $configurationArray);
        $connection = $entityManager->getConnection();

        $stmt = $connection->prepare("SELECT CAST(CAST('2018-01-01' AS DATE) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertSame(100, strlen($result['TXT']));
        $this->assertStringStartsWith("1-JAN-2018", $result['TXT']);

        $stmt = $connection->prepare("SELECT CAST(CAST('2018-01-01 00:00:00' AS TIMESTAMP) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertSame(100, strlen($result['TXT']));
        $this->assertSame("1-JAN-2018", rtrim($result['TXT']));

        try {
            $connection->prepare("SELECT CAST(CAST('00:00:00' AS TIME) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing ', $t->getMessage());
        }

        try {
            $connection->prepare("SELECT a.\"ID\" FROM Album AS a");
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing \'', $t->getMessage());
        }

        $stmt = $connection->prepare("SELECT 1/3 AS NUMBER FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertInternalType("array", $result);
        $this->assertArrayHasKey("NUMBER", $result);
        $this->assertSame("0.333333", $result["NUMBER"]);

        $entityManager->getConnection()->close();
    }

    public function testDialect2()
    {
        $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
        $configurationArray = static::getSetUpDoctrineConfigurationArray([
            'dialect' => 2,
        ]);
        static::installFirebirdDatabase($configurationArray);
        $entityManager = static::createEntityManager($doctrineConfiguration, $configurationArray);
        $connection = $entityManager->getConnection();

        try {
            $connection->prepare("SELECT CAST(CAST('2018-01-01' AS DATE) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing \'', $t->getMessage());
        }

        $stmt = $connection->prepare("SELECT CAST(CAST('2018-01-01 00:00:00' AS TIMESTAMP) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertSame(100, strlen($result['TXT']));
        $this->assertSame("2018-01-01 00:00:00.0000", rtrim($result['TXT']));

        try {
            $connection->prepare("SELECT CAST(CAST('00:00:00' AS TIME) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing ', $t->getMessage());
        }

        try {
            $connection->prepare("SELECT a.\"ID\" FROM Album AS a");
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing \'', $t->getMessage());
        }

        $stmt = $connection->prepare("SELECT 1/3 AS NUMBER FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertInternalType("array", $result);
        $this->assertArrayHasKey("NUMBER", $result);
        $this->assertInternalType("integer", $result["NUMBER"]);
        $this->assertSame(0, $result["NUMBER"]);

        $entityManager->getConnection()->close();
    }

    /**
     * @inheritDoc
     */
    protected static function getSetUpDoctrineConfigurationArray(array $overrideConfigs = [])
    {
        return array_merge(parent::getSetUpDoctrineConfigurationArray(), $overrideConfigs);
    }
}

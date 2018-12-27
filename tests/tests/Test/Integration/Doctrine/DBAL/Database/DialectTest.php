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

    public function testDialect0And3()
    {
        foreach ([0,3] as $dialect) {
            $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
            $configurationArray = static::getSetUpDoctrineConfigurationArray([
                'dialect' => 0
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
    }

    public function testDialect1()
    {
        $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
        $configurationArray = static::getSetUpDoctrineConfigurationArray([
            'dialect' => 1
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

        $stmt = $connection->prepare("SELECT CAST(CAST('00:00:00' AS TIME) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        try {
            $stmt->execute();
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing ', $t->getMessage());
            $this->assertInternalType("object", $t->getPrevious());
            $this->assertSame('Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception', get_class($t->getPrevious()));
            $this->assertStringStartsWith('Dynamic SQL Error SQL error code = -104 Client SQL dialect 1 does not support reference to TIME datatype', $t->getPrevious()->getMessage());
        }

        $stmt = $connection->prepare("SELECT a.\"ID\" FROM Album AS a");
        try {
            $stmt->execute();
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing \'', $t->getMessage());
            $this->assertInternalType("object", $t->getPrevious());
            $this->assertSame('Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception', get_class($t->getPrevious()));
            $this->assertStringStartsWith('Dynamic SQL Error SQL error code = -104 Token unknown - line 1, column 10 "ID"', $t->getPrevious()->getMessage());
        }

        $stmt = $connection->prepare("SELECT 1/3 AS NUMBER FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertInternalType("array", $result);
        $this->assertArrayHasKey("NUMBER", $result);
        $this->assertInternalType("float", $result["NUMBER"]);
        $this->assertSame("0.33333333", number_format($result["NUMBER"], 8));

        $entityManager->getConnection()->close();
    }

    public function testDialect2()
    {
        $doctrineConfiguration = static::getSetUpDoctrineConfiguration();
        $configurationArray = static::getSetUpDoctrineConfigurationArray([
            'dialect' => 2
        ]);
        static::installFirebirdDatabase($configurationArray);
        $entityManager = static::createEntityManager($doctrineConfiguration, $configurationArray);
        $connection = $entityManager->getConnection();

        $stmt = $connection->prepare("SELECT CAST(CAST('2018-01-01' AS DATE) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        try {
            $stmt->execute();
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing \'', $t->getMessage());
            $this->assertInternalType("object", $t->getPrevious());
            $this->assertSame('Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception', get_class($t->getPrevious()));
            $this->assertStringStartsWith('Dynamic SQL Error SQL error code = -104 DATE must be changed to TIMESTAMP', $t->getPrevious()->getMessage());
        }

        $stmt = $connection->prepare("SELECT CAST(CAST('2018-01-01 00:00:00' AS TIMESTAMP) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertSame(100, strlen($result['TXT']));
        $this->assertSame("2018-01-01 00:00:00.0000", rtrim($result['TXT']));

        $stmt = $connection->prepare("SELECT CAST(CAST('00:00:00' AS TIME) AS CHAR(25)) AS TXT FROM RDB\$DATABASE");
        try {
            $stmt->execute();
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing ', $t->getMessage());
            $this->assertInternalType("object", $t->getPrevious());
            $this->assertSame('Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception', get_class($t->getPrevious()));
            $this->assertStringStartsWith('Dynamic SQL Error SQL error code = -104 Client SQL dialect 1 does not support reference to TIME datatype', $t->getPrevious()->getMessage());
        }

        $stmt = $connection->prepare("SELECT a.\"ID\" FROM Album AS a");
        try {
            $stmt->execute();
        } catch (\Throwable $t) {
            $this->assertSame('Doctrine\DBAL\Exception\SyntaxErrorException', get_class($t));
            $this->assertStringStartsWith('Error -104: An exception occurred while executing \'', $t->getMessage());
            $this->assertInternalType("object", $t->getPrevious());
            $this->assertSame('Kafoso\DoctrineFirebirdDriver\Driver\FirebirdInterbase\Exception', get_class($t->getPrevious()));
            $this->assertStringStartsWith('Dynamic SQL Error SQL error code = -104 a string constant is delimited by double quotes', $t->getPrevious()->getMessage());
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

<?php
namespace IST\DoctrineFirebirdDriver\Test\Integration;

use Doctrine\ORM\EntityManager;
use IST\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;

class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    private static $_staticEntityManager;

    protected $_entityManager;
    protected $_platform;

    public function setUp()
    {
        $this->_entityManager = clone self::$_staticEntityManager;
        $this->_platform = new FirebirdInterbasePlatform;
    }

    public static function startup(EntityManager $entityManager)
    {
        self::$_staticEntityManager = $entityManager;
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

<?php
namespace IST\DoctrineFirebirdDriver\Test\Unit\Platforms;

use IST\DoctrineFirebirdDriver\Platforms\FirebirdInterbasePlatform;

abstract class AbstractFirebirdInterbasePlatformTest extends \PHPUnit_Framework_TestCase
{
    protected $_platform;

    public function setUp()
    {
        $this->_platform = new FirebirdInterbasePlatform;
    }
}

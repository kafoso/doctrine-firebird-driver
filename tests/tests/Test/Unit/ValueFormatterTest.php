<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Unit\Kafoso\DoctrineFirebirdDriver;

use Kafoso\DoctrineFirebirdDriver\ValueFormatter;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testEscapeAndQuote()
    {
        $this->assertSame('"2"', ValueFormatter::escapeAndQuote('2'));
        $this->assertSame('"2\""', ValueFormatter::escapeAndQuote('2"'));
        $this->assertSame('"2\\\\"', ValueFormatter::escapeAndQuote('2\\'));
    }

    public function testCast()
    {
        $this->assertSame('null', ValueFormatter::cast(null));
        $this->assertSame('true', ValueFormatter::cast(true));
        $this->assertSame('false', ValueFormatter::cast(false));
        $this->assertSame('42', ValueFormatter::cast(42));
        $this->assertSame('3.14', ValueFormatter::cast(3.14));
        $this->assertSame('"foo"', ValueFormatter::cast("foo"));
        $this->assertSame('Array(2)', ValueFormatter::cast([1,2]));
        $this->assertSame('\stdClass', ValueFormatter::cast(new \stdClass));
        $this->assertRegExp('/^#Resource id #\d+$/', ValueFormatter::cast(fopen(__FILE__, 'r')));
    }

    public function testFound()
    {
        $this->assertSame('(null) null', ValueFormatter::found(null));
        $this->assertSame('(boolean) true', ValueFormatter::found(true));
        $this->assertSame('(boolean) false', ValueFormatter::found(false));
        $this->assertSame('(integer) 42', ValueFormatter::found(42));
        $this->assertSame('(float) 3.14', ValueFormatter::found(3.14));
        $this->assertSame('(string) "foo"', ValueFormatter::found("foo"));
        $this->assertSame('(array) Array(2)', ValueFormatter::found([1,2]));
        $this->assertSame('(object) \\stdClass', ValueFormatter::found(new \stdClass));
        $this->assertRegExp('/^\(resource\) Resource id #\d+$/', ValueFormatter::found(fopen(__FILE__, 'r')));
    }
}

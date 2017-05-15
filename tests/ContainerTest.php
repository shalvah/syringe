<?php

use Syringe\Container;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    public $c;

    public function setUp()
    {
        parent::setUp();
        $this->c = new Container();
    }

    public function testCanBindAndRetrieveString()
    {
        $this->c->bind("item1", "value");
        $this->assertEquals("value", $this->c->get("item1"));
    }

    public function testCanBindAndRetrieveArray()
    {
        $c = new Container();
        $c->bind("item2", ["value"]);
        $this->assertEquals(["value"], $c->get("item2"));
    }

    public function testCanBindAndRetrieveObjectInstance()
    {
        $obj = new StdClass();
        $this->c->bind("item3", $obj);
        $this->assertEquals($obj, $this->c->get("item3"));
    }

    public function testCanBindAndRetrieveObjectInstanceViaClosure()
    {
        $obj = new stdClass();
        $this->c->bindInstance("item4", function ($c) use ($obj) {
            return $obj;
        });
        $this->assertEquals($obj, $this->c->get("item4"));
    }

    public function testCanBindAndRetrieveClassViaCallable()
    {
        $this->c->bind("item5", function ($c) {
            return new DateTime();
        });
        $this->assertInstanceOf(DateTime::class, $this->c->get("item5"));
    }

    public function testBindCanBindAndRetrieveClassViaClassname()
    {
        $this->c->bind("item6", DateTime::class);
        $this->assertInstanceOf(DateTime::class, $this->c->get("item6"));
    }

    public function testGetShouldThrowExceptionIfBindingNotSet()
    {
        $this->setExpectedException(\Syringe\ContainerValueNotFoundException::class, "You haven't bound anything for item7");
        $this->c->get("item7");
    }

    public function testExtendCanModifyBinding()
    {
        $this->c->extend("item1", function ($value, $c) {
            return $value." extra";
        });
        $this->assertEquals("value extra", $this->c->get("item1"));
    }

    public function testExtendCanBeUsedMoreThanOnce()
    {
        $this->c->extend("item1", function ($value, $c) {
            return $value." extra";
        });
        $this->assertEquals("value extra extra", $this->c->get("item1"));
    }

    public function testRawReturnsOriginalCallable()
    {
        $this->assertInternalType("callable", $this->c->raw("item5"));
    }

    public function testRawReturnsOriginalString()
    {
        $this->assertEquals(DateTime::class, $this->c->raw("item6"));
    }

    public function testRawThrowsExceptionIfBindingNotSet()
    {
        $this->setExpectedException(\Syringe\ContainerValueNotFoundException::class, "You haven't bound anything for item7");
        $this->c->raw("item7");
    }

    public function testHasReturnsTrueeIfBindingSet()
    {
        $this->assertTrue($this->c->has("item1"));
    }

    public function testHasReturnsFalseIfBindingNotSet()
    {
        $this->assertFalse($this->c->has("item7"));
    }

    public function testCanAccessBoundValueFromContainerInCallable()
    {
        $this->c->bind("item9", function ($c) {
            return [$c->get("item2")[0], "another"];
        });
        $this->assertEquals(["value", "another"], $this->c->get("item9"));
    }
}

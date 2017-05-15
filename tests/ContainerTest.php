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

    //test the specific binding methods
    public function testBindValueCanBindString()
    {
        $this->c->bindValue("string_item", "string_value");
        $this->assertEquals("string_value", $this->c->get("string_item"));
    }

    public function testBindClassCanBindClassname()
    {
        $this->c->bindClass("class_item", stdClass::class);
        $this->assertInstanceOf(stdClass::class, $this->c->get("class_item"));
    }

    public function testBindClassCanBindCallable()
    {
        $this->c->bindClass("class_item", function ($c) {
            return new stdClass();
        });
        $this->assertInstanceOf(stdClass::class, $this->c->get("class_item"));
    }

    public function testBindInstanceCanBindObject()
    {
        $obj = new DateTime();
        $this->c->bindInstance("object_item", $obj);
        $this->assertInstanceOf(DateTime::class, $this->c->get("object_item"));
    }

    public function testBindInstanceReturnsSameInstanceFromObject()
    {
        $obj = new DateTime();
        $this->c->bindInstance("object_item", $obj);
        $this->assertEquals($obj, $this->c->get("object_item"));
    }

    public function testBindInstanceCanBindCallable()
    {
        $this->c->bindClass("object_item", function ($c) {
            return new DateTime();
        });
        $this->assertInstanceOf(DateTime::class, $this->c->get("object_item"));
    }

    public function testBindInstanceShouldReturnSameInstanceFromCallable()
    {
        $this->c->bindClass("object_item", function ($c) {
            return new DateTime();
        });
        $this->assertEquals($this->c->get("object_item"), $this->c->get("object_item"));
    }

    public function testBindCanBindString()
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

    public function testBindCanBindInstanceFromObject()
    {
        $obj = new StdClass();
        $this->c->bind("item3", $obj);
        $this->assertEquals($obj, $this->c->get("item3"));
    }

    public function testCanBindInstanceFromClosure()
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

    public function testBindCanBindClassFromClassname()
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

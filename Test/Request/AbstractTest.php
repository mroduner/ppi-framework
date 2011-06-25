<?php

class PPI_Request_AbstractTest extends PHPUnit_Framework_TestCase
{
	protected $object = null;

	public function setUp()
	{
		$this->object = new TestPPI_Request_Abstract;
	}

	public function testSetAndGetOffset()
	{
		$this->assertEquals(null,  $this->object['foo']);
		$this->assertEquals(false, isset($this->object['foo']));

		$this->object['foo'] = 'bar';
		$this->assertEquals('bar', $this->object['foo']);
		$this->assertEquals(true,  isset($this->object['foo']));

		unset($this->object['foo']);
		$this->assertEquals(null,  $this->object['foo']);
		$this->assertEquals(false, isset($this->object['foo']));
	}

	public function testShouldCallSet()
	{
		$this->assertEquals(null, $this->object['foo']);
		$this->assertEquals(false, isset($this->object['foo']));

		$this->object['foo'] = 'bar';
		$this->assertEquals('bar', $this->object['foo']);
		$this->assertEquals(true,  isset($this->object['foo']));

		$this->object['foo'] = null;
		$this->assertEquals(null,  $this->object['foo']);
		$this->assertEquals(false, isset($this->object['foo']));
	}

	public function testIterator()
	{
		$this->object['foo'] = 'bar';
		$this->object['bar'] = 'foo';

		$viewed = array();
		foreach ($this->object as $key => $value) {
			$this->assertEquals(true,   isset($this->object[$key]));
			$this->assertEquals(false,  isset($viewed[$key]));
			$this->assertEquals($value, $this->object[$key]);
			$viewed[$key] = $value;
		}

		$this->assertEquals(2, count($viewed));
	}

	public function testCountable()
	{
		$this->assertEquals(0, count($this->object));

		$this->object['foo'] = 'bar';
		$this->assertEquals(1, count($this->object));

		$this->object['bar'] = 'foo';
		$this->assertEquals(2, count($this->object));

		unset($this->object['foo']);
		$this->assertEquals(1, count($this->object));
	}

	public function testGetIsCollected()
	{
		$this->assertEquals(true, $this->object->isCollected());
	}
}


class TestPPI_Request_Abstract extends PPI_Request_Abstract
{
}


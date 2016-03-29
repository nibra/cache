<?php
/**
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Cache\Tests;
require_once __DIR__ . '/Stubs/Concrete.php';

use Joomla\Test\TestHelper;
use Psr\Cache\CacheItemInterface;

/**
 * Tests for the Joomla\Cache\Cache class.
 *
 * @since  1.0
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var    \Joomla\Cache\Cache
	 * @since  1.0
	 */
	public $instance;

	/**
	 * @var    string  Cache Classname to test
	 * @since  1.0
	 */
	public $cacheClass = 'Joomla\\Cache\\Tests\\ConcreteCache';

	/**
	 * @var    array
	 * @since  1.0
	 */
	public $cacheOptions = array('foo' => 900);

	/**
	 * Tests the registry options is correctly initialised.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::__construct
	 * @covers  Joomla\Cache\Apc::__construct
	 * @covers  Joomla\Cache\Memcached::__construct
	 *
	 * @since   1.0
	 */
	public function test__construct()
	{
		$this->assertEquals('900', $this->instance->getOption('foo'));
	}

	/**
	 * Tests the Joomla\Cache\Cache::__construct method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::__construct
	 * @covers  Joomla\Cache\Apc::__construct
	 * @covers  Joomla\Cache\Memcached::__construct
	 * @expectedException  \Joomla\Cache\Exception\InvalidArgumentException
	 * @since   1.0
	 */
	public function test__constructWithInvalidParams()
	{
		// Throws exception, options is null
		$className = $this->cacheClass;
		new $className(null);
	}

	/**
	 * Tests the Joomla\Cache\Cache::clear method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::clear
	 * @covers  Joomla\Cache\Memcached::clear
	 * @since   1.1.3
	 */
	public function testClear()
	{
		$cacheInstance = $this->instance;
		$cacheInstance->clear();

		$this->assertFalse(
			$cacheInstance->hasItem('foobar'),
			__LINE__
		);

		// Create a stub for the CacheItemInterface class.
		$stub = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		// Configure the stub.
		$stub->method('get')
			->willReturn('barfoo');

		// Configure the stub.
		$stub->method('getKey')
			->willReturn('foobar');

		$this->assertTrue(
			$cacheInstance->save($stub),
			__LINE__
		);

		$this->assertTrue(
			$cacheInstance->hasItem('foobar'),
			__LINE__
		);

		$this->assertTrue(
			$cacheInstance->clear(),
			__LINE__
		);

		$this->assertFalse(
			$cacheInstance->hasItem('foobar'),
			__LINE__
		);
	}

	/**
	 * Tests the the Joomla\Cache\Cache::get method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Memcached::get
	 * @covers  Joomla\Cache\Memcached::connect
	 * @since   1.0
	 */
	public function testGet()
	{
		$cacheInstance = $this->instance;
		$cacheInstance->clear();

		// Create a stub for the CacheItemInterface class.
		$stub = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub->method('get')
			->willReturn('bar');

		$stub->method('getKey')
			->willReturn('foo');

		$cacheInstance->save($stub);
		$this->hitKey('foo', 'bar');
		$this->missKey('foobar', 'foobar');
	}

	/**
	 * Checks to ensure a that $key is not set at all in the Cache
	 *
	 * @param   string  $key    Key of cache item to check
	 * @param   string  $value  Value cache item should be
	 *
	 * @return  void
	 *
	 * @since   1.1
	 */
	protected function missKey($key = '', $value = '')
	{
		$cacheInstance = $this->instance;
		$cacheItem = $cacheInstance->getItem($key);
		$cacheValue = $cacheItem->get();
		$cacheKey = $cacheItem->getKey();
		$cacheHit = $cacheItem->isHit();
		$this->assertThat($cacheKey, $this->equalTo($key), __LINE__);
		$this->assertNull($cacheValue,  __LINE__);
		$this->assertFalse($cacheHit, __LINE__);
	}

	/**
	 * Checks to ensure a that $key is set to $value in the Cache
	 *
	 * @param   string  $key    Key of cache item to check
	 * @param   string  $value  Value cache item should be
	 *
	 * @return  void
	 *
	 * @since   1.1
	 */
	protected function hitKey($key = '', $value = '')
	{
		$cacheInstance = $this->instance;
		$cacheItem = $cacheInstance->getItem($key);
		$cacheKey = $cacheItem->getKey();
		$cacheValue = $cacheItem->get();
		$cacheHit = $cacheItem->isHit();
		$this->assertThat($cacheKey, $this->equalTo($key), __LINE__);
		$this->assertThat($cacheValue, $this->equalTo($value), __LINE__);
		$this->assertTrue($cacheHit, __LINE__);
	}

	/**
	 * Tests the Joomla\Cache\Cache::set method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::set
	 * @covers  Joomla\Cache\Memcached::set
	 * @covers  Joomla\Cache\Memcached::connect
	 * @since   1.0
	 */
	public function testSet()
	{
		$cacheInstance = $this->instance;
		$cacheInstance->clear();

		// Create a stub for the CacheItemInterface class.
		$stub = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub->method('get')
			->willReturn('barSet');

		$stub->method('getKey')
			->willReturn('fooSet');

		$result = $cacheInstance->save($stub);
		$this->assertTrue($result, __LINE__);

		$fooValue = $cacheInstance->getItem('fooSet')->get();
		$this->assertThat($fooValue, $this->equalTo('barSet'), __LINE__);
	}

	/**
	 * Tests the Joomla\Cache\Cache::getItems method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::getItems
	 * @covers  Joomla\Cache\Apc::getItems
	 * @since   1.0
	 */
	public function testGetItems()
	{
		$cacheInstance = $this->instance;
		$cacheInstance->clear();

		// Create a stub for the CacheItemInterface class.
		$stub = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub->method('get')
			->willReturn('foo');

		$stub->method('getKey')
			->willReturn('foo');

		// Create a stub for the CacheItemInterface class.
		$stub2 = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub2->method('get')
			->willReturn('bar');

		$stub2->method('getKey')
			->willReturn('bar');

		// Create a stub for the CacheItemInterface class.
		$stub3 = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub3->method('get')
			->willReturn('world');

		$stub3->method('getKey')
			->willReturn('hello');

		$samples = array($stub, $stub2, $stub3);
		$moreSamples = $samples;

		// Create a stub for the CacheItemInterface class.
		$stub4 = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub4->method('get')
			->willReturn('bar');

		$stub4->method('getKey')
			->willReturn('next');

		$moreSamples[] = $stub4;
		$lessSamples = $samples;
		$badSampleKeys = array('foobar', 'barfoo', 'helloworld');

		// Pop an item from the array
		array_pop($lessSamples);

		$keys = array('foo', 'bar', 'hello');

		foreach ($samples as $poolItem)
		{
			$cacheInstance->save($poolItem);
		}

		$results = $cacheInstance->getItems($keys);
		$this->assertSameSize($samples, $results, __LINE__);
		$this->assertNotSameSize($moreSamples, $results, __LINE__);
		$this->assertNotSameSize($lessSamples, $results, __LINE__);

		/** @var CacheItemInterface $item */
		foreach ($results as $item)
		{
			$itemKey = $item->getKey();
			$itemValue = $item->get();
			$sampleValue = $samples[$itemKey];
			$this->assertEquals($itemValue, $sampleValue, __LINE__);
		}

		// Even if no keys are set, we should still$ have an array of keys
		$badResults = $cacheInstance->getItems($badSampleKeys);
		$this->assertSameSize($badSampleKeys, $badResults, __LINE__);
	}

	/**
	 * Tests the Joomla\Cache\Cache::testDeleteItems method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::testDeleteItems
	 * @since   1.0
	 */
	public function testDeleteItems()
	{
		$cacheInstance = $this->instance;
		$cacheInstance->clear();

		$stub = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub->method('get')
			->willReturn('bars');

		$stub->method('getKey')
			->willReturn('foo');

		// Create a stub for the CacheItemInterface class.
		$stub2 = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub2->method('get')
			->willReturn('google');

		$stub2->method('getKey')
			->willReturn('goo');

		// Create a stub for the CacheItemInterface class.
		$stub3 = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub3->method('get')
			->willReturn('world');

		$stub3->method('getKey')
			->willReturn('hello');

		$samples = array($stub, $stub2, $stub3);

		foreach ($samples as $cacheItem)
		{
			$cacheInstance->save($cacheItem);
		}

		$trueSampleKeys = array('foo', 'goo', 'hello');

		$sampleKeys = array_merge(
			$trueSampleKeys,
			array('foobar')
		);
		$results = $cacheInstance->deleteItems($sampleKeys);

		foreach ($results as $key => $removed)
		{
			$msg = "Removal of $key was $removed::";

			if (in_array($key, $trueSampleKeys))
			{
				$this->assertTrue($removed, $msg . __LINE__);
			}
			else
			{
				$this->assertFalse($removed, $msg . __LINE__);
			}
		}
	}

	/**
	 * Tests the Joomla\Cache\Cache::deleteItem method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::deleteItem
	 * @covers  Joomla\Cache\Memcached::deleteItem
	 * @covers  Joomla\Cache\Memcached::connect
	 * @since   1.0
	 */
	public function testDeleteItem()
	{
		$cacheInstance = $this->instance;
		$cacheInstance->clear();

		$stub = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub->method('get')
			->willReturn('bars');

		$stub->method('getKey')
			->willReturn('foo2');

		// Create a stub for the CacheItemInterface class.
		$stub2 = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub2->method('get')
			->willReturn('google');

		$stub2->method('getKey')
			->willReturn('goo2');

		// Create a stub for the CacheItemInterface class.
		$stub3 = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub3->method('get')
			->willReturn('world');

		$stub3->method('getKey')
			->willReturn('hello2');

		$samples = array($stub, $stub2, $stub3);

		foreach ($samples as $cacheItem)
		{
			$cacheInstance->save($cacheItem);
		}

		$getFoo = $cacheInstance->getItem('foo2');
		$this->assertTrue($getFoo->isHit(), __LINE__);
		$removeFoo = $cacheInstance->deleteItem('foo2');
		$this->assertTrue($removeFoo, __LINE__);
		$removeFoobar = $cacheInstance->deleteItem('foobar');
		$this->assertFalse($removeFoobar, __LINE__);
		$getResult = $cacheInstance->getItem('foo2');
		$this->assertFalse($getResult->isHit(), __LINE__);
	}

	/**
	 * Tests the Joomla\Cache\Cache::setOption method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::getOption
	 * @covers  Joomla\Cache\Cache::setOption
	 * @since   1.0
	 */
	public function testSetOption()
	{
		$cacheInstance = $this->instance;
		$this->assertSame($cacheInstance, $cacheInstance->setOption('foo', 'bar'), 'Checks chaining');
		$this->assertEquals('bar', $cacheInstance->getOption('foo'));
	}

	/**
	 * Tests the Joomla\Cache\Cache::hasItem method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::hasItem
	 * @covers  Joomla\Cache\Memcached::hasItem
	 * @since   1.1.3
	 */
	public function testHasItem()
	{
		$cacheInstance = $this->instance;
		$cacheInstance->clear();

		$this->assertFalse(
			$cacheInstance->hasItem('foobar'),
			__LINE__
		);

		$stub = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub->method('get')
			->willReturn('barfoo');

		$stub->method('getKey')
			->willReturn('foobar');

		$this->assertTrue(
			$cacheInstance->save($stub),
			__LINE__
		);

		$this->assertTrue(
			$cacheInstance->hasItem('foobar'),
			__LINE__
		);
	}

	/**
	 * Tests for the correct Psr\Cache return values.
	 *
	 * @return  void
	 *
	 * @coversNothing
	 * @since   1.0
	 */
	public function testPsrCache()
	{
		$cacheInstance = $this->instance;
		$cacheClass = get_class($cacheInstance);
		$interfaces = class_implements($cacheClass);
		$psrInterface = 'Psr\\Cache\\CacheItemPoolInterface';
		$targetClass = $this->cacheClass;
		$this->assertArrayHasKey($psrInterface, $interfaces, __LINE__);
		$cacheClass = get_class($cacheInstance);
		$this->assertEquals($cacheClass, $targetClass, __LINE__);

		// Create a stub for the CacheItemInterface class.
		$stub = $this->getMockBuilder('\\Psr\\Cache\\CacheItemInterface')
			->getMock();

		$stub->method('get')
			->willReturn('bar');

		$stub->method('getKey')
			->willReturn('foo');

		$this->assertInternalType('boolean', $cacheInstance->clear(), 'Checking clear.');
		$this->assertInternalType('boolean', $cacheInstance->save($stub), 'Checking set.');
		$this->assertInternalType('string', $cacheInstance->getItem('foo')->get(), 'Checking get.');
		$this->assertInternalType('boolean', $cacheInstance->deleteItem('foo'), 'Checking remove.');
		$this->assertInternalType('array', $cacheInstance->getItems(array('foo')), 'Checking getMultiple.');
		$this->assertInternalType('array', $cacheInstance->deleteItems(array('foo')), 'Checking removeMultiple.');
	}

	/**
	 * Setup the tests.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function setUp()
	{
		$options = $this->cacheOptions;
		$className = $this->cacheClass;

		try
		{
			$cacheInstance = new $className($options);
		}
		catch (\RuntimeException $e)
		{
			$this->markTestSkipped();
		}

		$this->instance =& $cacheInstance;

		parent::setUp();
	}
}

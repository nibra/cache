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
	public $cacheOptions = array();

	/**
	 * Tests the Joomla\Cache\Cache::__construct method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::__construct
	 * @covers  Joomla\Cache\Apc::__construct
	 * @covers  Joomla\Cache\Memcached::__construct
	 * @expectedException  \RuntimeException
	 * @since   1.0
	 */
	public function test__construct()
	{
		// This checks the default ttl and also that the options registry was initialised.
		$this->assertEquals('900', $this->instance->getOption('ttl'));

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

		$this->assertTrue(
			$cacheInstance->set('foobar', 'barfoo'),
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
		$cacheInstance->set('foo', 'bar');
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

		$result = $cacheInstance->set('fooSet', 'barSet');
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
		$samples = array( 'foo' => 'foo', 'bar' => 'bar', 'hello' => 'world');
		$moreSamples = $samples;
		$moreSamples['next'] = 'bar';
		$lessSamples = $samples;
		$badSampleKeys = array( 'foobar', 'barfoo', 'helloworld');

		// Pop an item from the array
		array_pop($lessSamples);
		$keys = array_keys($samples);

		foreach ($samples as $key => $value)
		{
			$cacheInstance->set($key, $value);
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
			$this->assertThat($itemValue, $this->equalTo($sampleValue), __LINE__);
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
		$samples = array( 'foo' => 'bars', 'goo' => 'google', 'hello' => 'world');

		foreach ($samples as $key => $value)
		{
			$cacheInstance->set($key, $value);
		}

		$sampleKeys = array_merge(
			array_keys($samples),
			array('foobar')
		);
		$results = $cacheInstance->deleteItems($sampleKeys);

		foreach ($results as $key => $removed)
		{
			$msg = "Removal of $key was $removed::";

			if (array_key_exists($key, $samples))
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
		$samples = array( 'foo2' => 'bars', 'goo2' => 'google', 'hello2' => 'world');

		foreach ($samples as $key => $value)
		{
			$cacheInstance->set($key, $value);
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
	 * Tests the Joomla\Cache\Cache::setMultiple method.
	 *
	 * @return  void
	 *
	 * @covers  Joomla\Cache\Cache::setMultiple
	 * @since   1.0
	 */
	public function testSetMultiple()
	{
		$cacheInstance = $this->instance;
		$cacheInstance->clear();
		$samples = array( 'foo' => 'fooSet', 'bar' => 'barSet', 'hello' => 'worldSet');
		$keys = array_keys($samples);

		$this->assertTrue(
			$cacheInstance->setMultiple($samples, 50),
			__LINE__
		);

		$i = 0;

		foreach ($keys as $key)
		{
			$cacheValue = $cacheInstance->getItem($key)->get();
			$sampleValue = $samples[$key];
			$this->assertThat($cacheValue, $this->equalTo($sampleValue), __LINE__);
			$i++;
		}
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

		$this->assertTrue(
			$cacheInstance->set('foobar', 'barfoo'),
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
		$psrInterface = 'Psr\\Cache\\CacheInterface';
		$targetClass = $this->cacheClass;
		$this->assertArrayHasKey($psrInterface, $interfaces, __LINE__);
		$cacheClass = get_class($cacheInstance);
		$this->assertThat($cacheClass, $this->equalTo($targetClass), __LINE__);

		$this->assertInternalType('boolean', $cacheInstance->clear(), 'Checking clear.');
		$this->assertInternalType('boolean', $cacheInstance->set('foo', 'bar'), 'Checking set.');
		$this->assertInternalType('string', $cacheInstance->getItem('foo')->get(), 'Checking get.');
		$this->assertInternalType('boolean', $cacheInstance->deleteItem('foo'), 'Checking remove.');
		$this->assertInternalType('boolean', $cacheInstance->setMultiple(array('foo' => 'bar')), 'Checking setMultiple.');
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

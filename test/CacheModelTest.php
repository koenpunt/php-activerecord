<?php
use ActiveRecord\Cache;

class CacheModelTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		if (!extension_loaded('memcache'))
		{
			$this->markTestSkipped('The memcache extension is not available');
			return;
		}
		parent::setUp($connection_name);
		ActiveRecord\Config::instance()->set_cache('memcache://localhost');
	}

	protected static function setMethodPublic($className, $methodName)
	{
		$class = new ReflectionClass($className);
		$method = $class->getMethod($methodName);
		$method->setAccessible(true);
		return $method;
	}

	public function tearDown()
	{
		Cache::flush();
		Cache::initialize(null);
	}

	public function testDefaultExpire()
	{
		$this->assertEquals(30,Author::table()->cache_model_expire);
	}

	public function testExplicitExpire()
	{
		$this->assertEquals(2592000,Publisher::table()->cache_model_expire);
	}

	public function testCacheKey()
	{
		$method = $this->setMethodPublic('Author', 'cache_key');
		$author = Author::first();

		$this->assertEquals("Author-1", $method->invokeArgs($author, array()));
	}

	public function testModelCacheFindByPk()
	{
		$publisher = Publisher::find(1);
		$method = $this->setMethodPublic('Publisher', 'cache_key');
		$cache_key = $method->invokeArgs($publisher, array());
		$from_cache = Cache::$adapter->read($cache_key);

		$this->assertEquals($publisher->name, $from_cache->name);
	}

	public function testModelCacheNew()
	{
		$publisher = new Publisher(array(
			'name' => 'HarperCollins'
		));
		$publisher->save();

		$method = $this->setMethodPublic('Publisher', 'cache_key');
		$cache_key = $method->invokeArgs($publisher, array());

		// Model is cached on first find
		$actual = Publisher::find($publisher->id);
		$from_cache = Cache::$adapter->read($cache_key);

		$this->assertEquals($actual, $from_cache);
	}

	public function testModelCacheFind()
	{
		$method = $this->setMethodPublic('Publisher', 'cache_key');
		$publishers = Publisher::all();

		foreach($publishers as $publisher)
		{
			$cache_key = $method->invokeArgs($publisher, array());
			$from_cache = Cache::$adapter->read($cache_key);

			$this->assertEquals($publisher->name, $from_cache->name);
		}
	}

	public function testRegularModelsNotCached()
	{
		$method = $this->setMethodPublic('Author', 'cache_key');
		$author = Author::first();
		$cache_key = $method->invokeArgs($author, array());
		$this->assertFalse(Cache::$adapter->read($cache_key));
	}

	public function testModelDeleteFromCache()
	{
		$method = $this->setMethodPublic('Publisher', 'cache_key');
		$publisher = Publisher::find(1);
		$cache_key = $method->invokeArgs($publisher, array());

		$publisher->delete();

		// at this point, the cached record should be gone
		$this->assertFalse(Cache::$adapter->read($cache_key));

	}

	public function testModelUpdateCache(){
		$method = $this->setMethodPublic('Publisher', 'cache_key');

		$publisher = Publisher::find(1);
		$cache_key = $method->invokeArgs($publisher, array());
		$this->assertEquals('Random House', $publisher->name);

		$from_cache = Cache::$adapter->read($cache_key);
		$this->assertEquals('Random House', $from_cache->name);

		// make sure that updates make it to cache
		$publisher->name = 'Puppy Publishing';
		$publisher->save();

		$actual = Publisher::find($publisher->id);
		$from_cache = Cache::$adapter->read($cache_key);

		$this->assertEquals('Puppy Publishing', $from_cache->name);
	}

	public function testModelReloadExpiresCache(){
		$method = $this->setMethodPublic('Publisher', 'cache_key');

		$publisher = Publisher::find(1);
		$cache_key = $method->invokeArgs($publisher, array());
		$this->assertEquals('Random House', $publisher->name);

		// Raw query to not update model properties
		Publisher::query('UPDATE publishers SET name = ? WHERE publisher_id = ?', array('Specific House', 1));

		$publisher->reload();

		$this->assertEquals('Specific House', $publisher->name);

		$from_cache = Cache::$adapter->read($cache_key);

		$this->assertEquals('Specific House', $from_cache->name);
	}

}

<?php
use ActiveRecord\Cache;

class ActiveRecordCacheTest extends DatabaseTest
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

	public function tearDown()
	{
		Cache::flush();
		Cache::initialize(null);
	}

	public function testDefaultExpire()
	{
		$this->assertEquals(30,Cache::$options['expire']);
	}

	public function testExplicitDefaultExpire()
	{
		ActiveRecord\Config::instance()->set_cache('memcache://localhost',array('expire' => 1));
		$this->assertEquals(1,Cache::$options['expire']);
	}

	public function testCachesColumnMetaData()
	{
		Author::first();

		$table_name = Author::table()->get_fully_qualified_table_name(!($this->conn instanceof ActiveRecord\PgsqlAdapter));
		$value = Cache::$adapter->read("get_meta_data-$table_name");
		$this->assertTrue(is_array($value));
	}
}


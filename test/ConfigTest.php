<?php

use ActiveRecord\Config;
use ActiveRecord\ConfigException;

class TestLogger
{
	private function log() {}
}

class TestDateTimeWithoutCreateFromFormat
{
   public function format($format=null) {}
}

class TestDateTime
{
   public function format($format=null) {}
   public static function createFromFormat($format, $time) {}
}

class ConfigTest extends TestCase
{
	public function setUp()
	{
		$this->config = new Config();
		$this->connections = array('development' => 'mysql://blah/development', 'test' => 'mysql://blah/test');
		$this->config->set_connections($this->connections);
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function testSetConnectionsMustBeArray()
	{
		$this->config->set_connections(null);
	}

	public function testGetConnections()
	{
		$this->assertEquals($this->connections,$this->config->get_connections());
	}

	public function testGetConnection()
	{
		$this->assertEquals($this->connections['development'],$this->config->get_connection('development'));
	}

	public function testGetInvalidConnection()
	{
		$this->assertNull($this->config->get_connection('whiskey tango foxtrot'));
	}

	public function testGetDefaultConnectionAndConnection()
	{
		$this->config->set_default_connection('development');
		$this->assertEquals('development',$this->config->get_default_connection());
		$this->assertEquals($this->connections['development'],$this->config->get_default_connection_string());
	}

	public function testGetDefaultConnectionAndConnectionStringDefaultsToDevelopment()
	{
		$this->assertEquals('development',$this->config->get_default_connection());
		$this->assertEquals($this->connections['development'],$this->config->get_default_connection_string());
	}

	public function testGetDefaultConnectionStringWhenConnectionNameIsNotValid()
	{
		$this->config->set_default_connection('little mac');
		$this->assertNull($this->config->get_default_connection_string());
	}

	public function testDefaultConnectionIsSetWhenOnlyOneConnectionIsPresent()
	{
		$this->config->set_connections(array('development' => $this->connections['development']));
		$this->assertEquals('development',$this->config->get_default_connection());
	}

	public function testSetConnectionsWithDefault()
	{
		$this->config->set_connections($this->connections,'test');
		$this->assertEquals('test',$this->config->get_default_connection());
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function testSetModelDirectoriesMustBeArray()
	{
		$this->config->set_model_directories(null);
	}

	public function testGetDateClassWithDefault()
	{
		$this->assertEquals('ActiveRecord\\DateTime', $this->config->get_date_class());
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function testSetDateClassWhenClassDoesntExist()
	{
		$this->config->set_date_class('doesntexist');
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function testSetModelDirectoriesDirectoriesMustExist(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			'/some-non-existing-directory'
		));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function testSetModelDirectoryStoresAsArray(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directory(realpath(__DIR__ . '/models'));
		$this->assertInternalType('array', $this->config->get_model_directories());

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function testGetModelDirectoryReturnsFirstModelDirectory(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			realpath(__DIR__ . '/backup-models'),
		));
		$this->assertEquals(realpath(__DIR__ . '/models'), $this->config->get_model_directory());

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function testSetDateClassWhenClassDoesntHaveFormatOrCreatefromformat()
	{
		$this->config->set_date_class('TestLogger');
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function testSetDateClassWhenClassDoesntHaveCreatefromformat()
	{
		$this->config->set_date_class('TestDateTimeWithoutCreateFromFormat');
	}

	public function testSetDateClassWithValidClass()
	{
		$this->config->set_date_class('TestDateTime');
		$this->assertEquals('TestDateTime', $this->config->get_date_class());
	}

	public function testInitializeClosure()
	{
		$test = $this;

		Config::initialize(function($cfg) use ($test)
		{
			$test->assertNotNull($cfg);
			$test->assertEquals('ActiveRecord\Config',get_class($cfg));
		});
	}

	public function testLoggerObjectMustImplementLogMethod()
	{
		try {
			$this->config->set_logger(new TestLogger);
			$this->fail();
		} catch (ConfigException $e) {
			$this->assertEquals($e->getMessage(), "Logger object must implement a public log method");
		}
	}
}

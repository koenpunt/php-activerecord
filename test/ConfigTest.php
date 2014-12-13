<?php

use ActiveRecord\Config;
use ActiveRecord\ConfigException;

class TestLogger
{
	private function log() {}
}

class ConfigTest extends ActiveRecord_TestCase
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
	public function test_set_connections_must_be_array()
	{
		$this->config->set_connections(null);
	}

	public function test_get_connections()
	{
		$this->assertEquals($this->connections,$this->config->get_connections());
	}

	public function test_get_connection()
	{
		$this->assertEquals($this->connections['development'],$this->config->get_connection('development'));
	}

	public function test_get_invalid_connection()
	{
		$this->assertNull($this->config->get_connection('whiskey tango foxtrot'));
	}

	public function test_get_default_connection_and_connection()
	{
		$this->config->set_default_connection('development');
		$this->assertEquals('development',$this->config->get_default_connection());
		$this->assertEquals($this->connections['development'],$this->config->get_default_connection_string());
	}

	public function test_get_default_connection_and_connection_string_defaults_to_development()
	{
		$this->assertEquals('development',$this->config->get_default_connection());
		$this->assertEquals($this->connections['development'],$this->config->get_default_connection_string());
	}

	public function test_get_default_connection_string_when_connection_name_is_not_valid()
	{
		$this->config->set_default_connection('little mac');
		$this->assertNull($this->config->get_default_connection_string());
	}

	public function test_default_connection_is_set_when_only_one_connection_is_present()
	{
		$this->config->set_connections(array('development' => $this->connections['development']));
		$this->assertEquals('development',$this->config->get_default_connection());
	}

	public function test_set_connections_with_default()
	{
		$this->config->set_connections($this->connections,'test');
		$this->assertEquals('test',$this->config->get_default_connection());
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function test_set_model_directories_must_be_array()
	{
		$this->config->set_model_directories(null);
	}

	/**
	 * @expectedException ActiveRecord\ConfigException
	 */
	public function test_set_model_directories_directories_must_exist(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			'/some-non-existing-directory'
		));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_set_model_directory_stores_as_array(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directory(realpath(__DIR__ . '/models'));
		$this->assertInternalType('array', $this->config->get_model_directories());

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_get_model_directory_returns_first_model_directory(){
		$home = ActiveRecord\Config::instance()->get_model_directory();

		$this->config->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			realpath(__DIR__ . '/backup-models'),
		));
		$this->assertEquals(realpath(__DIR__ . '/models'), $this->config->get_model_directory());

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function test_initialize_closure()
	{
		$test = $this;

		Config::initialize(function($cfg) use ($test)
		{
			$test->assertNotNull($cfg);
			$test->assertEquals('ActiveRecord\Config',get_class($cfg));
		});
	}

	public function test_logger_object_must_implement_log_method()
	{
		try {
			$this->config->set_logger(new TestLogger);
			$this->fail();
		} catch (ConfigException $e) {
			$this->assertEquals($e->getMessage(), "Logger object must implement a public log method");
		}
	}
}

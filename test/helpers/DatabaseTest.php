<?php
require_once __DIR__ . '/DatabaseLoader.php';

class DatabaseTest extends ActiveRecord_TestCase
{
	protected $conn;
	public static $log = false;
	public static $db;

	public function setUp($connection_name=null)
	{
		ActiveRecord\Table::clear_cache();

		$config = ActiveRecord\Config::instance();
		$this->original_default_connection = $config->get_default_connection();

		if ($connection_name)
			$config->set_default_connection($connection_name);

		if ($connection_name == 'sqlite' || $config->get_default_connection() == 'sqlite')
		{
			// need to create the db. the adapter specifically does not create it for us.
			static::$db = substr(ActiveRecord\Config::instance()->get_connection('sqlite'),9);
			new SQLite3(static::$db);
		}

		$this->connection_name = $connection_name;
		try {
			$this->conn = ActiveRecord\ConnectionManager::get_connection($connection_name);
		} catch (ActiveRecord\DatabaseException $e) {
			$this->markTestSkipped($connection_name . ' failed to connect. '.$e->getMessage());
		}

		$GLOBALS['ACTIVERECORD_LOG'] = false;

		$loader = new DatabaseLoader($this->conn);
		$loader->reset_table_data();

		if (self::$log)
			$GLOBALS['ACTIVERECORD_LOG'] = true;
	}

	public function tearDown()
	{
		if ($this->original_default_connection)
			ActiveRecord\Config::instance()->set_default_connection($this->original_default_connection);
	}

}

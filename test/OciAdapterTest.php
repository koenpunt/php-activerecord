<?php
require_once __DIR__ . '/../lib/adapters/OciAdapter.php';

class OciAdapterTest extends AdapterTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp('oci');
	}

	public function testGetSequenceName()
	{
		$this->assertEquals('authors_seq',$this->conn->get_sequence_name('authors','author_id'));
	}

	public function testColumnsText()
	{
		$author_columns = $this->conn->columns('authors');
		$this->assertEquals('varchar2',$author_columns['some_text']->raw_type);
		$this->assertEquals(100,$author_columns['some_text']->length);
	}

	public function testDatetimeToString()
	{
		$this->assertEquals('01-Jan-2009 01:01:01 AM',$this->conn->datetime_to_string(date_create('2009-01-01 01:01:01 EST')));
	}

	public function testDateToString()
	{
		$this->assertEquals('01-Jan-2009',$this->conn->date_to_string(date_create('2009-01-01 01:01:01 EST')));
	}

	public function testInsertId() {}
	public function testInsertIdWithParams() {}
	public function testInsertIdShouldReturnExplicitlyInsertedId() {}
	public function testColumnsTime() {}
	public function testColumnsSequence() {}

	public function testSetCharset()
	{
		$connection_string = ActiveRecord\Config::instance()->get_connection($this->connection_name);
		$conn = ActiveRecord\Connection::instance($connection_string . '?charset=utf8');
		$this->assertEquals(';charset=utf8', $conn->dsn_params);
	}
}

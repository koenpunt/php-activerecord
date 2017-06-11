<?php

class BookPresence extends ActiveRecord\Model
{
	static $table_name = 'books';

	static $validates_presence_of = array(
		array('name')
	);
}

class AuthorPresence extends ActiveRecord\Model
{
	static $table_name = 'authors';

	static $validates_presence_of = array(
		array('some_date')
	);
}

class ValidatesPresenceOfTest extends DatabaseTest
{
	public function testPresence()
	{
		$book = new BookPresence(array('name' => 'blah'));
		$this->assertFalse($book->is_invalid());
	}

	public function testPresenceOnDateFieldIsValid()
	{
		$author = new AuthorPresence(array('some_date' => '2010-01-01'));
		$this->assertTrue($author->is_valid());
	}

	public function testPresenceOnDateFieldIsNotValid()
	{
		$author = new AuthorPresence();
		$this->assertFalse($author->is_valid());
	}
	
	public function testInvalidNull()
	{
		$book = new BookPresence(array('name' => null));
		$this->assertTrue($book->is_invalid());
	}

	public function testInvalidBlank()
	{
		$book = new BookPresence(array('name' => ''));
		$this->assertTrue($book->is_invalid());
	}

	public function testValidWhiteSpace()
	{
		$book = new BookPresence(array('name' => ' '));
		$this->assertFalse($book->is_invalid());
	}

	public function testCustomMessage()
	{
		BookPresence::$validates_presence_of[0]['message'] = 'is using a custom message.';

		$book = new BookPresence(array('name' => null));
		$book->is_valid();
		$this->assertEquals('is using a custom message.', $book->errors->on('name'));
	}

	public function testValidZero()
	{
		$book = new BookPresence(array('name' => 0));
		$this->assertTrue($book->is_valid());
	}
}

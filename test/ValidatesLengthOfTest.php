<?php

class BookLength extends ActiveRecord\Model
{
	static $table = 'books';
	static $validates_length_of = array();
}

class BookSize extends ActiveRecord\Model
{
	static $table = 'books';
	static $validates_size_of = array();
}

class ValidatesLengthOfTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		BookLength::$validates_length_of[0] = array('name', 'allow_blank' => false, 'allow_null' => false);
	}
	
	public function testWithin()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 5);
		$book = new BookLength;
		$book->name = '12345';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testWithinErrorMessage()
	{
		BookLength::$validates_length_of[0]['within'] = array(2,5);
		$book = new BookLength();
		$book->name = '1';
		$book->is_valid();
		$this->assertEquals(array('Name is too short (minimum is 2 characters)'),$book->errors->full_messages());

		$book->name = '123456';
		$book->is_valid();
		$this->assertEquals(array('Name is too long (maximum is 5 characters)'),$book->errors->full_messages());
	}

	public function testWithinCustomErrorMessage()
	{
		BookLength::$validates_length_of[0]['within'] = array(2,5);
		BookLength::$validates_length_of[0]['too_short'] = 'is too short';
		BookLength::$validates_length_of[0]['message'] = 'is not between 2 and 5 characters';
		$book = new BookLength();
		$book->name = '1';
		$book->is_valid();
		$this->assertEquals(array('Name is not between 2 and 5 characters'),$book->errors->full_messages());

		$book->name = '123456';
		$book->is_valid();
		$this->assertEquals(array('Name is not between 2 and 5 characters'),$book->errors->full_messages());
	}
	
	public function testValidIn()
	{
		BookLength::$validates_length_of[0]['in'] = array(1, 5);
		$book = new BookLength;
		$book->name = '12345';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testAliasedSizeOf()
	{
		BookSize::$validates_size_of = BookLength::$validates_length_of;
		BookSize::$validates_size_of[0]['within'] = array(1, 5);
		$book = new BookSize;
		$book->name = '12345';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInvalidWithinAndIn()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		$book = new BookLength;
		$book->name = 'four';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));

		$this->setUp();
		BookLength::$validates_length_of[0]['in'] = array(1, 3);
		$book = new BookLength;
		$book->name = 'four';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
	}

	public function testValidNull()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		BookLength::$validates_length_of[0]['allow_null'] = true;

		$book = new BookLength;
		$book->name = null;
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testValidBlank()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		BookLength::$validates_length_of[0]['allow_blank'] = true;

		$book = new BookLength;
		$book->name = '';
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testInvalidBlank()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);

		$book = new BookLength;
		$book->name = '';
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
		$this->assertEquals('is too short (minimum is 1 characters)', $book->errors->on('name'));
	}

	public function testInvalidNullWithin()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);

		$book = new BookLength;
		$book->name = null;
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
		$this->assertEquals('is too short (minimum is 1 characters)', $book->errors->on('name'));
	}
	
	public function testInvalidNullMinimum()
	{
		BookLength::$validates_length_of[0]['minimum'] = 1;

		$book = new BookLength;
		$book->name = null;
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
		$this->assertEquals('is too short (minimum is 1 characters)', $book->errors->on('name'));
		
	}
	
	public function testValidNullMaximum()
	{
		BookLength::$validates_length_of[0]['maximum'] = 1;

		$book = new BookLength;
		$book->name = null;
		$book->save();
		$this->assertFalse($book->errors->is_invalid('name'));
	}

	public function testFloatAsImpossibleRangeOption()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3.6);
		$book = new BookLength;
		$book->name = '123';
		try {
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('maximum value cannot use a float for length.', $e->getMessage());
		}

		$this->setUp();
		BookLength::$validates_length_of[0]['is'] = 1.8;
		$book = new BookLength;
		$book->name = '123';
		try {
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('is value cannot use a float for length.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testSignedIntegerAsImpossibleWithinOption()
	{
		BookLength::$validates_length_of[0]['within'] = array(-1, 3);

		$book = new BookLength;
		$book->name = '123';
		try {
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('minimum value cannot use a signed integer.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testSignedIntegerAsImpossibleIsOption()
	{
		BookLength::$validates_length_of[0]['is'] = -8;

		$book = new BookLength;
		$book->name = '123';
		try {
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('is value cannot use a signed integer.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testLackOfOption()
	{
		try {
			$book = new BookLength;
			$book->name = null;
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('Range unspecified.  Specify the [within], [maximum], or [is] option.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testTooManyOptions()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		BookLength::$validates_length_of[0]['in'] = array(1, 3);

		try {
			$book = new BookLength;
			$book->name = null;
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('Too many range options specified.  Choose only one.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	public function testTooManyOptionsWithDifferentOptionTypes()
	{
		BookLength::$validates_length_of[0]['within'] = array(1, 3);
		BookLength::$validates_length_of[0]['is'] = 3;

		try {
			$book = new BookLength;
			$book->name = null;
			$book->save();
		} catch (ActiveRecord\ValidationsArgumentError $e) {
			$this->assertEquals('Too many range options specified.  Choose only one.', $e->getMessage());
			return;
		}

		$this->fail('An expected exception has not be raised.');
	}

	/**
	 * @expectedException ActiveRecord\ValidationsArgumentError
	 */
	public function testWithOptionAsNonNumeric()
	{
		BookLength::$validates_length_of[0]['with'] = array('test');

		$book = new BookLength;
		$book->name = null;
		$book->save();
	}

	/**
	 * @expectedException ActiveRecord\ValidationsArgumentError
	 */
	public function testWithOptionAsNonNumericNonArray()
	{
		BookLength::$validates_length_of[0]['with'] = 'test';

		$book = new BookLength;
		$book->name = null;
		$book->save();
	}

	public function testValidatesLengthOfMaximum()
	{
		BookLength::$validates_length_of[0] = array('name', 'maximum' => 10);
		$book = new BookLength(array('name' => '12345678901'));
		$book->is_valid();
		$this->assertEquals(array("Name is too long (maximum is 10 characters)"),$book->errors->full_messages());
	}

	public function testValidatesLengthOfMinimum()
	{
		BookLength::$validates_length_of[0] = array('name', 'minimum' => 2);
		$book = new BookLength(array('name' => '1'));
		$book->is_valid();
		$this->assertEquals(array("Name is too short (minimum is 2 characters)"),$book->errors->full_messages());
	}
	
	public function testValidatesLengthOfMinMaxCustomMessage()
	{
		BookLength::$validates_length_of[0] = array('name', 'maximum' => 10, 'message' => 'is far too long');
		$book = new BookLength(array('name' => '12345678901'));
		$book->is_valid();
		$this->assertEquals(array("Name is far too long"),$book->errors->full_messages());

		BookLength::$validates_length_of[0] = array('name', 'minimum' => 10, 'message' => 'is far too short');
		$book = new BookLength(array('name' => '123456789'));
		$book->is_valid();
		$this->assertEquals(array("Name is far too short"),$book->errors->full_messages());
	}
	
	public function testValidatesLengthOfMinMaxCustomMessageOverridden()
	{
		BookLength::$validates_length_of[0] = array('name', 'minimum' => 10, 'too_short' => 'is too short', 'message' => 'is custom message');
		$book = new BookLength(array('name' => '123456789'));
		$book->is_valid();
		$this->assertEquals(array("Name is custom message"),$book->errors->full_messages());
	}

	public function testValidatesLengthOfIs()
	{
		BookLength::$validates_length_of[0] = array('name', 'is' => 2);
		$book = new BookLength(array('name' => '123'));
		$book->is_valid();
		$this->assertEquals(array("Name is the wrong length (should be 2 characters)"),$book->errors->full_messages());
	}
}

<?php

use ActiveRecord as AR;

class BookValidations extends ActiveRecord\Model
{
	static $table_name = 'books';
	static $alias_attribute = array('name_alias' => 'name', 'x' => 'secondary_author_id');
	static $validates_presence_of = array();
	static $validates_uniqueness_of = array();
	static $custom_validator_error_msg = 'failed custom validation';

	// fired for every validation - but only used for custom validation test
	public function validate()
	{
		if ($this->name == 'test_custom_validation')
			$this->errors->add('name', self::$custom_validator_error_msg);
	}
}

class UserValidations extends AR\Model
{
	static $table_name = 'users';

	public $password_confirm;

	// Only for test purpose. This will double encrypt pass from the DB!
	public function set_password($pass)
	{
		$this->assign_attribute('password', static::encrypt($pass));
	}

	public function validate()
	{
		// Another BAD idea
		$this->password_confirm = static::encrypt($this->password_confirm);
		if($this->password_confirm !== $this->password)
			$this->errors->add('password', 'Password Mismatch');
	}

	public static function encrypt($data)
	{
		return md5($data);
	}
	
}

class ValuestoreValidations extends ActiveRecord\Model
{
	static $table_name = 'valuestore';
	static $validates_uniqueness_of = array();
}

class ValidationsTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);

		BookValidations::$validates_presence_of[0] = 'name';
		BookValidations::$validates_uniqueness_of[0] = 'name';
		
		ValuestoreValidations::$validates_uniqueness_of[0] = 'key';
	}

	public function testIsValidInvokesValidations()
	{
		$book = new Book;
		$this->assertTrue(empty($book->errors));
		$book->is_valid();
		$this->assertFalse(empty($book->errors));
	}

	public function testIsValidReturnsTrueIfNoValidationsExist()
	{
		$book = new Book;
		$this->assertTrue($book->is_valid());
	}

	public function testIsValidReturnsFalseIfFailedValidations()
	{
		$book = new BookValidations;
		$this->assertFalse($book->is_valid());
	}

	public function testIsInvalid()
	{
		$book = new Book();
		$this->assertFalse($book->is_invalid());
	}

	public function testIsInvalidIsTrue()
	{
		$book = new BookValidations();
		$this->assertTrue($book->is_invalid());
	}

	public function testIsValidDoesNotRevalidate()
	{
		$attrs = array(
			'password' => 'secret',
			'password_confirm' => 'secret'
		);

		$user = new UserValidations($attrs);
		/**
		 * The `is_valid()` method will validate the User. In this test it will
		 * be valid.
		 * If `is_valid()` had revalidated it again, `password_confirm` would be
		 * rehashed, becoming different from `password` and then the result
		 * would be different from precedent (and also a bug).
		 */
		$this->assertTrue($user->is_valid());
		$this->assertEquals(!$user->is_valid(), $user->is_invalid());
	}

	public function testIsValidWillRevalidateIfAttributeChanges()
	{
		$attrs = array(
			'password' => 'bad',
			'password_confirm' => 'secret'
		);

		$user = new UserValidations($attrs);
		$this->assertFalse($user->is_valid());

		$user->password = 'secret';
		// because custom validation is coded bad (on purpose), we have to
		// reset password_confirm
		$user->password_confirm = 'secret';
		$this->assertTrue($user->is_valid());
	}

	public function testIsInvalidWillRevalidateIfAttributeChanges()
	{
		$attrs = array(
			'password' => 'bad',
			'password_confirm' => 'secret'
		);

		$user = new UserValidations($attrs);
		$this->assertTrue($user->is_invalid());

		$user->password = 'secret';
		// because custom validation is coded bad (on purpose), we have to
		// reset password_confirm
		$user->password_confirm = 'secret';
		$this->assertFalse($user->is_invalid());
	}

	public function testIsValidMustBeForcedIfAVirtualAttributeChanges()
	{
		$attrs = array(
			'password' => 'secret',
			'password_confirm' => 'bad'
		);

		$user = new UserValidations($attrs);
		$this->assertFalse($user->is_valid());

		$user->password_confirm = 'secret';
		// Actually we check only attribute set by `__set` magic method.
		$this->assertFalse($user->is_valid());

		// Passing `true` will force the validation of `user`, giving the
		// right result.
		$this->assertTrue($user->is_valid(true));
	}

	public function testIsIterable()
	{
		$book = new BookValidations();
		$book->is_valid();

		foreach ($book->errors as $name => $message)
			$this->assertEquals("Name can't be blank",$message);
	}

	public function testFullMessages()
	{
		$book = new BookValidations();
		$book->is_valid();

		$this->assertEquals(array("Name can't be blank"),array_values($book->errors->full_messages(array('hash' => true))));
	}

	public function testToArray()
	{
		$book = new BookValidations();
		$book->is_valid();

		$this->assertEquals(array("name" => array("Name can't be blank")), $book->errors->to_array());
	}
	
	public function testToString()
	{
		$book = new BookValidations();
		$book->is_valid();
		$book->errors->add('secondary_author_id', "is invalid");
		
		$this->assertEquals("Name can't be blank\nSecondary author id is invalid", (string) $book->errors);
	}

	public function testValidatesUniquenessOf()
	{
		BookValidations::create(array('name' => 'bob'));
		$book = BookValidations::create(array('name' => 'bob'));

		$this->assertEquals(array("Name must be unique"),$book->errors->full_messages());
		$this->assertEquals(1,BookValidations::count(array('conditions' => "name='bob'")));
	}

	public function testValidatesUniquenessOfExcludesSelf()
	{
		$book = BookValidations::first();
		$this->assertEquals(true,$book->is_valid());
	}

	public function testValidatesUniquenessOfWithMultipleFields()
	{
		BookValidations::$validates_uniqueness_of[0] = array(array('name','special'));
		$book1 = BookValidations::first();
		$book2 = new BookValidations(array('name' => $book1->name, 'special' => $book1->special+1));
		$this->assertTrue($book2->is_valid());
	}

	public function testValidatesUniquenessOfWithMultipleFieldsIsNotUnique()
	{
		BookValidations::$validates_uniqueness_of[0] = array(array('name','special'));
		$book1 = BookValidations::first();
		$book2 = new BookValidations(array('name' => $book1->name, 'special' => $book1->special));
		$this->assertFalse($book2->is_valid());
		$this->assertEquals(array('Name and special must be unique'),$book2->errors->full_messages());
	}

	public function testValidatesUniquenessOfWorksWithAliasAttribute()
	{
		BookValidations::$validates_uniqueness_of[0] = array(array('name_alias','x'));
		$book = BookValidations::create(array('name_alias' => 'Another Book', 'x' => 2));
		$this->assertFalse($book->is_valid());
		$this->assertEquals(array('Name alias and x must be unique'), $book->errors->full_messages());
	}

	public function testValidatesUniquenessOfWorksWithMysqlReservedWordAsColumnName()
	{
		ValuestoreValidations::create(array('key' => 'GA_KEY', 'value' => 'UA-1234567-1'));
		$valuestore = ValuestoreValidations::create(array('key' => 'GA_KEY', 'value' => 'UA-1234567-2'));

		$this->assertEquals(array("Key must be unique"),$valuestore->errors->full_messages());
		$this->assertEquals(1,ValuestoreValidations::count(array('conditions' => "`key`='GA_KEY'")));
	}

	public function testGetValidationRules()
	{
		$validators = BookValidations::first()->get_validation_rules();
		$this->assertTrue(in_array(array('validator' => 'validates_presence_of'),$validators['name']));
	}

	public function testModelIsNulledOutToPreventMemoryLeak()
	{
		$book = new BookValidations();
		$book->is_valid();
		$this->assertTrue(strpos(serialize($book->errors),'model";N;') !== false);
	}

	public function testValidationsTakesStrings()
	{
		BookValidations::$validates_presence_of = array('numeric_test', array('special'), 'name');
		$book = new BookValidations(array('numeric_test' => 1, 'special' => 1));
		$this->assertFalse($book->is_valid());
	}

	public function testGh131CustomValidation()
	{
		$book = new BookValidations(array('name' => 'test_custom_validation'));
		$book->save();
		$this->assertTrue($book->errors->is_invalid('name'));
		$this->assertEquals(BookValidations::$custom_validator_error_msg, $book->errors->on('name'));
	}
}


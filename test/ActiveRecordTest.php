<?php

class ActiveRecordTest extends DatabaseTest
{
	public function setUp($connection_name=null)
	{
		parent::setUp($connection_name);
		$this->options = array('conditions' => 'blah', 'order' => 'blah');
	}

	public function testOptionsIsNot()
	{
		$this->assertFalse(Author::is_options_hash(null));
		$this->assertFalse(Author::is_options_hash(''));
		$this->assertFalse(Author::is_options_hash('tito'));
		$this->assertFalse(Author::is_options_hash(array()));
		$this->assertFalse(Author::is_options_hash(array(1,2,3)));
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testOptionsHashWithUnknownKeys() {
		$this->assertFalse(Author::is_options_hash(array('conditions' => 'blah', 'sharks' => 'laserz', 'dubya' => 'bush')));
	}

	public function testOptionsIsHash()
	{
		$this->assertTrue(Author::is_options_hash($this->options));
	}

	public function testExtractAndValidateOptions() {
		$args = array('first',$this->options);
		$this->assertEquals($this->options,Author::extract_and_validate_options($args));
		$this->assertEquals(array('first'),$args);
	}

	public function testExtractAndValidateOptionsWithArrayInArgs() {
		$args = array('first',array(1,2),$this->options);
		$this->assertEquals($this->options,Author::extract_and_validate_options($args));
	}

	public function testExtractAndValidateOptionsRemovesOptionsHash() {
		$args = array('first',$this->options);
		Author::extract_and_validate_options($args);
		$this->assertEquals(array('first'),$args);
	}

	public function testExtractAndValidateOptionsNope() {
		$args = array('first');
		$this->assertEquals(array(),Author::extract_and_validate_options($args));
		$this->assertEquals(array('first'),$args);
	}

	public function testExtractAndValidateOptionsNopeBecauseWasntAtEnd() {
		$args = array('first',$this->options,array(1,2));
		$this->assertEquals(array(),Author::extract_and_validate_options($args));
	}

	/**
	 * @expectedException ActiveRecord\UndefinedPropertyException
	 */
	public function testInvalidAttribute()
	{
		$author = Author::find('first',array('conditions' => 'author_id=1'));
		$author->some_invalid_field_name;
	}

	public function testInvalidAttributes()
	{
		$book = Book::find(1);
		try {
			$book->update_attributes(array('name' => 'new name', 'invalid_attribute' => true , 'another_invalid_attribute' => 'something'));
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			$exceptions = explode("\r\n", $e->getMessage());
		}

		$this->assertEquals(1, substr_count($exceptions[0], 'invalid_attribute'));
		$this->assertEquals(1, substr_count($exceptions[1], 'another_invalid_attribute'));
	}

	public function testGetterUndefinedPropertyExceptionIncludesModelName()
	{
		$this->assertExceptionMessageContains("Author->this_better_not_exist",function()
		{
			$author = new Author();
			$author->this_better_not_exist;
		});
	}

	public function testMassAssignmentUndefinedPropertyExceptionIncludesModelName()
	{
		$this->assertExceptionMessageContains("Author->this_better_not_exist",function()
		{
			new Author(array("this_better_not_exist" => "hi"));
		});
	}

	public function testSetterUndefinedPropertyExceptionIncludesModelName()
	{
		$this->assertExceptionMessageContains("Author->this_better_not_exist",function()
		{
			$author = new Author();
			$author->this_better_not_exist = "hi";
		});
	}

	public function testGetValuesFor()
	{
		$book = Book::find_by_name('Ancient Art of Main Tanking');
		$ret = $book->get_values_for(array('book_id','author_id'));
		$this->assertEquals(array('book_id','author_id'),array_keys($ret));
		$this->assertEquals(array(1,1),array_values($ret));
	}

	public function testHyphenatedColumnNamesToUnderscore()
	{
		if ($this->conn instanceof ActiveRecord\OciAdapter)
			return;

		$keys = array_keys(RmBldg::first()->attributes());
		$this->assertTrue(in_array('rm_name',$keys));
	}

	public function testColumnNamesWithSpaces()
	{
		if ($this->conn instanceof ActiveRecord\OciAdapter)
			return;

		$keys = array_keys(RmBldg::first()->attributes());
		$this->assertTrue(in_array('space_out',$keys));
	}

	public function testMixedCaseColumnName()
	{
		$keys = array_keys(Author::first()->attributes());
		$this->assertTrue(in_array('mixedcasefield',$keys));
	}

	public function testMixedCasePrimaryKeySave()
	{
		$venue = Venue::find(1);
		$venue->name = 'should not throw exception';
		$venue->save();
		$this->assertEquals($venue->name,Venue::find(1)->name);
	}

	public function testReload()
	{
		$venue = Venue::find(1);
		$this->assertEquals('NY', $venue->state);
		$venue->state = 'VA';
		$this->assertEquals('VA', $venue->state);
		$venue->reload();
		$this->assertEquals('NY', $venue->state);
	}
	
	public function testReloadProtectedAttribute()
	{
		$book = BookAttrAccessible::find(1);
	
		$book->name = "Should not stay";
		$book->reload();
		$this->assertNotEquals("Should not stay", $book->name);
	}

	public function testActiveRecordModelHomeNotSet()
	{
		$home = ActiveRecord\Config::instance()->get_model_directory();
		ActiveRecord\Config::instance()->set_model_directory(__DIR__);
		$this->assertEquals(false,class_exists('TestAutoload'));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function testAutoLoadWithModelInSecondaryModelDirectory(){
		$home = ActiveRecord\Config::instance()->get_model_directory();
		ActiveRecord\Config::instance()->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			realpath(__DIR__ . '/backup-models'),
		));
		$this->assertTrue(class_exists('Backup'));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function testAutoLoadWithNamespacedModel()
	{
		$this->assertTrue(class_exists('NamespaceTest\Book'));
	}

	public function testAutoLoadWithNamespacedModelInSecondaryModelDirectory(){
		$home = ActiveRecord\Config::instance()->get_model_directory();
		ActiveRecord\Config::instance()->set_model_directories(array(
			realpath(__DIR__ . '/models'),
			realpath(__DIR__ . '/backup-models'),
		));
		$this->assertTrue(class_exists('NamespaceTest\Backup'));

		ActiveRecord\Config::instance()->set_model_directory($home);
	}

	public function testNamespaceGetsStrippedFromTableName()
	{
		$model = new NamespaceTest\Book();
		$this->assertEquals('books',$model->table()->table);
	}

	public function testNamespaceGetsStrippedFromInferredForeignKey()
	{
		$model = new NamespaceTest\Book();
		$table = ActiveRecord\Table::load(get_class($model));

		$this->assertEquals($table->get_relationship('parent_book')->foreign_key[0], 'book_id');
		$this->assertEquals($table->get_relationship('parent_book_2')->foreign_key[0], 'book_id');
		$this->assertEquals($table->get_relationship('parent_book_3')->foreign_key[0], 'book_id');
	}

	public function testNamespacedRelationshipAssociatesCorrectly()
	{
		$model = new NamespaceTest\Book();
		$table = ActiveRecord\Table::load(get_class($model));

		$this->assertNotNull($table->get_relationship('parent_book'));
		$this->assertNotNull($table->get_relationship('parent_book_2'));
		$this->assertNotNull($table->get_relationship('parent_book_3'));

		$this->assertNotNull($table->get_relationship('pages'));
		$this->assertNotNull($table->get_relationship('pages_2'));

		$this->assertNull($table->get_relationship('parent_book_4'));
		$this->assertNull($table->get_relationship('pages_3'));

		// Should refer to the same class
		$this->assertSame(
			ltrim($table->get_relationship('parent_book')->class_name, '\\'),
			ltrim($table->get_relationship('parent_book_2')->class_name, '\\')
		);

		// Should refer to different classes
		$this->assertNotSame(
			ltrim($table->get_relationship('parent_book_2')->class_name, '\\'),
			ltrim($table->get_relationship('parent_book_3')->class_name, '\\')
		);

		// Should refer to the same class
		$this->assertSame(
			ltrim($table->get_relationship('pages')->class_name, '\\'),
			ltrim($table->get_relationship('pages_2')->class_name, '\\')
		);
	}

	public function testShouldHaveAllColumnAttributesWhenInitializingWithArray()
	{
		$author = new Author(array('name' => 'Tito'));
		$this->assertTrue(count(array_keys($author->attributes())) >= 9);
	}

	public function testDefaults()
	{
		$author = new Author();
		$this->assertEquals('default_name',$author->name);
	}

	public function testAliasAttributeGetter()
	{
		$venue = Venue::find(1);
		$this->assertEquals($venue->marquee, $venue->name);
		$this->assertEquals($venue->mycity, $venue->city);
	}

	public function testAliasAttributeSetter()
	{
		$venue = Venue::find(1);
		$venue->marquee = 'new name';
		$this->assertEquals($venue->marquee, 'new name');
		$this->assertEquals($venue->marquee, $venue->name);

		$venue->name = 'another name';
		$this->assertEquals($venue->name, 'another name');
		$this->assertEquals($venue->marquee, $venue->name);
	}

	public function testAliasAttributeCustomSetter()
	{
		Venue::$use_custom_set_city_setter = true;
		$venue = Venue::find(1);

		$venue->mycity = 'cityname';
		$this->assertEquals($venue->mycity, 'cityname#');
		$this->assertEquals($venue->mycity, $venue->city);

		$venue->city = 'anothercity';
		$this->assertEquals($venue->city, 'anothercity#');
		$this->assertEquals($venue->city, $venue->mycity);

		Venue::$use_custom_set_city_setter = false;
	}

	public function testAliasFromMassAttributes()
	{
		$venue = new Venue(array('marquee' => 'meme', 'id' => 123));
		$this->assertEquals('meme',$venue->name);
		$this->assertEquals($venue->marquee,$venue->name);
	}

	public function testGh18IssetOnAliasedAttribute()
	{
		$this->assertTrue(isset(Venue::first()->marquee));
	}

	public function testAttrAccessible()
	{
		$book = new BookAttrAccessible(array('name' => 'should not be set', 'author_id' => 1));
		$this->assertNull($book->name);
		$this->assertEquals(1,$book->author_id);
		$book->name = 'test';
		$this->assertEquals('test', $book->name);
	}

	public function testAttrProtected()
	{
		$book = new BookAttrAccessible(array('book_id' => 999));
		$this->assertNull($book->book_id);
		$book->book_id = 999;
		$this->assertEquals(999, $book->book_id);
	}

	public function testIsset()
	{
		$book = new Book();
		$this->assertTrue(isset($book->name));
		$this->assertFalse(isset($book->sharks));
	}

	public function testReadonlyOnlyHaltOnWriteMethod()
	{
		$book = Book::first(array('readonly' => true));
		$this->assertTrue($book->is_readonly());

		try {
			$book->save();
			$this->fail('expected exception ActiveRecord\ReadonlyException');
		} catch (ActiveRecord\ReadonlyException $e) {
		}

		$book->name = 'some new name';
		$this->assertEquals($book->name, 'some new name');
	}

	public function testCastWhenUsingSetter()
	{
		$book = new Book();
		$book->book_id = '1';
		$this->assertSame(1,$book->book_id);
	}

	public function testCastWhenLoading()
	{
		$book = Book::find(1);
		$this->assertSame(1,$book->book_id);
		$this->assertSame('Ancient Art of Main Tanking',$book->name);
	}

	public function testCastDefaults()
	{
		$book = new Book();
		$this->assertSame(0.0,$book->special);
	}

	public function testTransactionCommitted()
	{
		$original = Author::count();
		$ret = Author::transaction(function() { Author::create(array("name" => "blah")); });
		$this->assertEquals($original+1,Author::count());
		$this->assertTrue($ret);
	}
	
	public function testTransactionCommittedWhenReturningTrue()
	{
		$original = Author::count();
		$ret = Author::transaction(function() { Author::create(array("name" => "blah")); return true; });
		$this->assertEquals($original+1,Author::count());
		$this->assertTrue($ret);
	}
	
	public function testTransactionRolledbackByReturningFalse()
	{
		$original = Author::count();
		
		$ret = Author::transaction(function()
		{
			Author::create(array("name" => "blah"));
			return false;
		});
		
		$this->assertEquals($original,Author::count());
		$this->assertFalse($ret);
	}
	
	public function testTransactionRolledbackByThrowingException()
	{
		$original = Author::count();
		$exception = null;

		try
		{
			Author::transaction(function()
			{
				Author::create(array("name" => "blah"));
				throw new Exception("blah");
			});
		}
		catch (Exception $e)
		{
			$exception = $e;
		}

		$this->assertNotNull($exception);
		$this->assertEquals($original,Author::count());
	}

	public function testDelegate()
	{
		$event = Event::first();
		$this->assertEquals($event->venue->state,$event->state);
		$this->assertEquals($event->venue->address,$event->address);
	}

	public function testDelegatePrefix()
	{
		$event = Event::first();
		$this->assertEquals($event->host->name,$event->woot_name);
	}

	public function testDelegateReturnsNullIfRelationshipDoesNotExist()
	{
		$event = new Event();
		$this->assertNull($event->state);
	}

	public function testDelegateSetAttribute()
	{
		$event = Event::first();
		$event->state = 'MEXICO';
		$this->assertEquals('MEXICO',$event->venue->state);
	}

	public function testDelegateGetterGh98()
	{
		Venue::$use_custom_get_state_getter = true;

		$event = Event::first();
		$this->assertEquals('ny', $event->venue->state);
		$this->assertEquals('ny', $event->state);

		Venue::$use_custom_get_state_getter = false;
	}

	public function testDelegateSetterGh98()
	{
		Venue::$use_custom_set_state_setter = true;

		$event = Event::first();
		$event->state = 'MEXICO';
		$this->assertEquals('MEXICO#',$event->venue->state);

		Venue::$use_custom_set_state_setter = false;
	}

	public function testTableNameWithUnderscores()
	{
		$this->assertNotNull(AwesomePerson::first());
	}

	public function testModelShouldDefaultAsNewRecord()
	{
		$author = new Author();
		$this->assertTrue($author->is_new_record());
	}

	public function testSetter()
	{
		$author = new Author();
		$author->password = 'plaintext';
		$this->assertEquals(md5('plaintext'),$author->encrypted_password);
	}

	public function testSetterWithSameNameAsAnAttribute()
	{
		$author = new Author();
		$author->name = 'bob';
		$this->assertEquals('BOB',$author->name);
	}

	public function testGetter()
	{
		$book = Book::first();
		$this->assertEquals(strtoupper($book->name), $book->upper_name);
	}

	public function testGetterWithSameNameAsAnAttribute()
	{
		Book::$use_custom_get_name_getter = true;
		$book = new Book;
		$book->name = 'bob';
		$this->assertEquals('BOB', $book->name);
		Book::$use_custom_get_name_getter = false;
	}

	public function testSettingInvalidDateShouldSetDateToNull()
	{
		$author = new Author();
		$author->created_at = 'CURRENT_TIMESTAMP';
		$this->assertNull($author->created_at);
	}

	public function testTableName()
	{
		$this->assertEquals('authors',Author::table_name());
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testUndefinedInstanceMethod()
	{
		Author::first()->find_by_name('sdf');
	}

	public function testClearCacheForSpecificClass()
	{
		$book_table1 = ActiveRecord\Table::load('Book');
		$book_table2 = ActiveRecord\Table::load('Book');
		ActiveRecord\Table::clear_cache('Book');
		$book_table3 = ActiveRecord\Table::load('Book');

		$this->assertTrue($book_table1 === $book_table2);
		$this->assertTrue($book_table1 !== $book_table3);
	}

	public function testFlagDirty()
	{
		$author = new Author();
		$author->flag_dirty('some_date');
		$this->assertHasKeys('some_date', $author->dirty_attributes());
		$this->assertTrue($author->attribute_is_dirty('some_date'));
		$author->save();
		$this->assertFalse($author->attribute_is_dirty('some_date'));
	}

	public function testFlagDirtyAttributeWhichDoesNotExit()
	{
		$author = new Author();
		$author->flag_dirty('some_inexistant_property');
		$this->assertNull($author->dirty_attributes());
		$this->assertFalse($author->attribute_is_dirty('some_inexistant_property'));
	}

	public function testGh245DirtyAttributeShouldNotRaisePhpNoticeIfNotDirty()
	{
		$event = new Event(array('title' => "Fun"));
		$this->assertFalse($event->attribute_is_dirty('description'));
		$this->assertTrue($event->attribute_is_dirty('title'));
	}

	public function testAttributeIsNotFlaggedDirtyIfAssigningSameValue() {
		$event = Event::find(1);
		$event->type = "Music";
		$this->assertFalse($event->attribute_is_dirty('type'));
	}

	public function testChangedAttributes() {
		$event = Event::find(1);

		$event->type = "Groovy Music";
		$changed_attributes = $event->changed_attributes();
		$this->assertTrue(is_array($changed_attributes));
		$this->assertEquals(1, count($changed_attributes));
		$this->assertTrue(isset($changed_attributes['type']));
		$this->assertEquals("Music", $changed_attributes['type']);

		$event->type = "Funky Music";
		$changed_attributes = $event->changed_attributes();
		$this->assertTrue(is_array($changed_attributes));
		$this->assertEquals(1, count($changed_attributes));
		$this->assertTrue(isset($changed_attributes['type']));
		$this->assertEquals("Music", $changed_attributes['type']);
	}

	public function testChanges() {
		$event = Event::find(1);

		$event->type = "Groovy Music";
		$changes = $event->changes();
		$this->assertTrue(is_array($changes));
		$this->assertEquals(1, count($changes));
		$this->assertTrue(isset($changes['type']));
		$this->assertTrue(is_array($changes['type']));
		$this->assertEquals("Music", $changes['type'][0]);
		$this->assertEquals("Groovy Music", $changes['type'][1]);

		$event->type = "Funky Music";
		$changes = $event->changes();
		$this->assertTrue(is_array($changes));
		$this->assertEquals(1, count($changes));
		$this->assertTrue(isset($changes['type']));
		$this->assertTrue(is_array($changes['type']));
		$this->assertEquals("Music", $changes['type'][0]);
		$this->assertEquals("Funky Music", $changes['type'][1]);
	}

	public function testAttributeWas() {
		$event = Event::find(1);
		$event->type = "Funky Music";
		$this->assertEquals("Music", $event->attribute_was("type"));
		$event->type = "Groovy Music";
		$this->assertEquals("Music", $event->attribute_was("type"));
	}

	public function testPreviousChanges() {
		$event = Event::find(1);
		$event->type = "Groovy Music";
		$previous_changes = $event->previous_changes();
		$this->assertTrue(empty($previous_changes));
		$event->save();
		$previous_changes = $event->previous_changes();
		$this->assertTrue(is_array($previous_changes));
		$this->assertEquals(1, count($previous_changes));
		$this->assertTrue(isset($previous_changes['type']));
		$this->assertTrue(is_array($previous_changes['type']));
		$this->assertEquals("Music", $previous_changes['type'][0]);
		$this->assertEquals("Groovy Music", $previous_changes['type'][1]);
	}

	public function testSaveResetsChangedAttributes() {
		$event = Event::find(1);
		$event->type = "Groovy Music";
		$event->save();
		$changed_attributes = $event->changed_attributes();
		$this->assertTrue(empty($changed_attributes));
	}

	public function testChangingDatetimeAttributeTracksChange() {
		$author = new Author();
		$author->created_at = $original = new \DateTime("yesterday");
		$author->created_at = $now = new \DateTime();
		$changes = $author->changes();
		$this->assertTrue(isset($changes['created_at']));
		$this->assertDatetimeEquals($original, $changes['created_at'][0]);
		$this->assertDatetimeEquals($now, $changes['created_at'][1]);
	}

	public function testChangingEmptyAttributeValueTracksChange() {
		$event = new Event();
		$event->description = "The most fun";
		$changes = $event->changes();
		$this->assertTrue(array_key_exists("description", $changes));
		$this->assertEquals("", $changes['description'][0]);
		$this->assertEquals("The most fun", $changes['description'][1]);
	}

	public function testAssigningPhpDatetimeGetsConvertedToDateClassWithDefaults()
	{
		$author = new Author();
		$author->created_at = $now = new \DateTime();
		$this->assertIsA("ActiveRecord\\DateTime", $author->created_at);
		$this->assertDatetimeEquals($now,$author->created_at);
	}

	public function testAssigningPhpDatetimeGetsConvertedToDateClassWithCustomDateClass()
	{
		ActiveRecord\Config::instance()->set_date_class('\\DateTime'); // use PHP built-in DateTime
		$author = new Author();
		$author->created_at = $now = new \DateTime();
		$this->assertIsA("DateTime", $author->created_at);
		$this->assertDatetimeEquals($now,$author->created_at);
	}

	public function testAssigningFromMassAssignmentPhpDatetimeGetsConvertedToArDatetime()
	{
		$author = new Author(array('created_at' => new \DateTime()));
		$this->assertIsA("ActiveRecord\\DateTime",$author->created_at);
	}

	public function testGetRealAttributeName()
	{
		$venue = new Venue();
		$this->assertEquals('name', $venue->get_real_attribute_name('name'));
		$this->assertEquals('name', $venue->get_real_attribute_name('marquee'));
		$this->assertEquals(null, $venue->get_real_attribute_name('invalid_field'));
	}

	public function testIdSetterWorksWithTableWithoutPkNamedAttribute()
	{
		$author = new Author(array('id' => 123));
		$this->assertEquals(123,$author->author_id);
	}

	public function testQuery()
	{
		$row = Author::query('SELECT COUNT(*) AS n FROM authors',null)->fetch();
		$this->assertTrue($row['n'] > 1);

		$row = Author::query('SELECT COUNT(*) AS n FROM authors WHERE name=?',array('Tito'))->fetch();
		$this->assertEquals(array('n' => 1), $row);
	}
}

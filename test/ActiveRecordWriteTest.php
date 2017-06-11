<?php
use ActiveRecord\DateTime;

class DirtyAuthor extends ActiveRecord\Model
{
	static $table = 'authors';
	static $before_save = 'before_save';

	public function before_save()
	{
		$this->name = 'i saved';
	}
}

class AuthorWithoutSequence extends ActiveRecord\Model
{
	static $table = 'authors';
	static $sequence = 'invalid_seq';
}

class AuthorExplicitSequence extends ActiveRecord\Model
{
	static $sequence = 'blah_seq';
}

class ActiveRecordWriteTest extends DatabaseTest
{
	private function makeNewBookAnd($save=true)
	{
		$book = new Book();
		$book->name = 'rivers cuomo';
		$book->special = 1;

		if ($save)
			$book->save();

		return $book;
	}

	public function testSave()
	{
		$venue = new Venue(array('name' => 'Tito'));
		$venue->save();
	}

	public function testInsert()
	{
		$author = new Author(array('name' => 'Blah Blah'));
		$author->save();
		$this->assertNotNull(Author::find($author->id));
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testInsertWithNoSequenceDefined()
	{
		if (!$this->conn->supports_sequences())
			throw new ActiveRecord\DatabaseException('');

		AuthorWithoutSequence::create(array('name' => 'Bob!'));
	}

	public function testInsertShouldQuoteKeys()
	{
		$author = new Author(array('name' => 'Blah Blah'));
		$author->save();
		$this->assertTrue(strpos($author->connection()->last_query,$author->connection()->quote_name('updated_at')) !== false);
	}

	public function testSaveAutoIncrementId()
	{
		$venue = new Venue(array('name' => 'Bob'));
		$venue->save();
		$this->assertTrue($venue->id > 0);
	}

	public function testSequenceWasSet()
	{
		if ($this->conn->supports_sequences())
			$this->assertEquals($this->conn->get_sequence_name('authors','author_id'),Author::table()->sequence);
		else
			$this->assertNull(Author::table()->sequence);
	}

	public function testSequenceWasExplicitlySet()
	{
		if ($this->conn->supports_sequences())
			$this->assertEquals(AuthorExplicitSequence::$sequence,AuthorExplicitSequence::table()->sequence);
		else
			$this->assertNull(Author::table()->sequence);
	}

	public function testDelete()
	{
		$author = Author::find(1);
		$author->delete();

		$this->assertFalse(Author::exists(1));
	}

	public function testDeleteByFindAll()
	{
		$books = Book::all();

		foreach ($books as $model)
			$model->delete();

		$res = Book::all();
		$this->assertEquals(0,count($res));
	}

	public function testUpdate()
	{
		$book = Book::find(1);
		$new_name = 'new name';
		$book->name = $new_name;
		$book->save();

		$this->assertSame($new_name, $book->name);
		$this->assertSame($new_name, $book->name, Book::find(1)->name);
	}

	public function testUpdateShouldQuoteKeys()
	{
		$book = Book::find(1);
		$book->name = 'new name';
		$book->save();
		$this->assertTrue(strpos($book->connection()->last_query,$book->connection()->quote_name('name')) !== false);
	}

	public function testUpdateAttributes()
	{
		$book = Book::find(1);
		$new_name = 'How to lose friends and alienate people'; // jax i'm worried about you
		$attrs = array('name' => $new_name);
		$book->update_attributes($attrs);

		$this->assertSame($new_name, $book->name);
		$this->assertSame($new_name, $book->name, Book::find(1)->name);
	}

	/**
	 * @expectedException ActiveRecord\UndefinedPropertyException
	 */
	public function testUpdateAttributesUndefinedProperty()
	{
		$book = Book::find(1);
		$book->update_attributes(array('name' => 'new name', 'invalid_attribute' => true , 'another_invalid_attribute' => 'blah'));
	}

	public function testUpdateAttribute()
	{
		$book = Book::find(1);
		$new_name = 'some stupid self-help book';
		$book->update_attribute('name', $new_name);

		$this->assertSame($new_name, $book->name);
		$this->assertSame($new_name, $book->name, Book::find(1)->name);
	}

	/**
	 * @expectedException ActiveRecord\UndefinedPropertyException
	 */
	public function testUpdateAttributeUndefinedProperty()
	{
		$book = Book::find(1);
		$book->update_attribute('invalid_attribute', true);
	}

	public function testSaveNullValue()
	{
		$book = Book::first();
		$book->name = null;
		$book->save();
		$this->assertSame(null,Book::find($book->id)->name);
	}

	public function testSaveBlankValue()
	{
		// oracle doesn't do blanks. probably an option to enable?
		if ($this->conn instanceof ActiveRecord\OciAdapter)
			return;

		$book = Book::find(1);
		$book->name = '';
		$book->save();
		$this->assertSame('',Book::find(1)->name);
	}

	public function testDirtyAttributes()
	{
		$book = $this->makeNewBookAnd(false);
		$this->assertEquals(array('name','special'),array_keys($book->dirty_attributes()));
	}

	public function testDirtyAttributesClearedAfterSaving()
	{
		$book = $this->makeNewBookAnd();
		$this->assertTrue(strpos($book->table()->last_sql,'name') !== false);
		$this->assertTrue(strpos($book->table()->last_sql,'special') !== false);
		$this->assertEquals(null,$book->dirty_attributes());
	}

	public function testDirtyAttributesClearedAfterInserting()
	{
		$book = $this->makeNewBookAnd();
		$this->assertEquals(null,$book->dirty_attributes());
	}

	public function testNoDirtyAttributesButStillInsertRecord()
	{
		$book = new Book;
		$this->assertEquals(null,$book->dirty_attributes());
		$book->save();
		$this->assertEquals(null,$book->dirty_attributes());
		$this->assertNotNull($book->id);
	}

	public function testDirtyAttributesClearedAfterUpdating()
	{
		$book = Book::first();
		$book->name = 'rivers cuomo';
		$book->save();
		$this->assertEquals(null,$book->dirty_attributes());
	}

	public function testDirtyAttributesAfterReloading()
	{
		$book = Book::first();
		$book->name = 'rivers cuomo';
		$book->reload();
		$this->assertEquals(null,$book->dirty_attributes());
	}

	public function testDirtyAttributesWithMassAssignment()
	{
		$book = Book::first();
		$book->set_attributes(array('name' => 'rivers cuomo'));
		$this->assertEquals(array('name'), array_keys($book->dirty_attributes()));
	}

	public function testTimestampsSetBeforeSave()
	{
		$author = new Author;
		$author->save();
		$this->assertNotNull($author->created_at, $author->updated_at);

		$author->reload();
		$this->assertNotNull($author->created_at, $author->updated_at);
	}

	public function testTimestampsUpdatedAtOnlySetBeforeUpdate()
	{
		$author = new Author();
		$author->save();
		$created_at = $author->created_at;
		$updated_at = $author->updated_at;
		sleep(1);

		$author->name = 'test';
		$author->save();

		$this->assertNotNull($author->updated_at);
		$this->assertSame($created_at, $author->created_at);
		$this->assertNotEquals($updated_at, $author->updated_at);
	}

	public function testCreate()
	{
		$author = Author::create(array('name' => 'Blah Blah'));
		$this->assertNotNull(Author::find($author->id));
	}

	public function testCreateShouldSetCreatedAt()
	{
		$author = Author::create(array('name' => 'Blah Blah'));
		$this->assertNotNull($author->created_at);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testUpdateWithNoPrimaryKeyDefined()
	{
		Author::table()->pk = array();
		$author = Author::first();
		$author->name = 'blahhhhhhhhhh';
		$author->save();
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testDeleteWithNoPrimaryKeyDefined()
	{
		Author::table()->pk = array();
		$author = author::first();
		$author->delete();
	}

	public function testInsertingWithExplicitPk()
	{
		$author = Author::create(array('author_id' => 9999, 'name' => 'blah'));
		$this->assertEquals(9999,$author->author_id);
	}

	/**
	 * @expectedException ActiveRecord\ReadOnlyException
	 */
	public function testReadonly()
	{
		$author = Author::first(array('readonly' => true));
		$author->save();
	}

	public function testModifiedAttributesInBeforeHandlersGetSaved()
	{
		$author = DirtyAuthor::first();
		$author->encrypted_password = 'coco';
		$author->save();
		$this->assertEquals('i saved',DirtyAuthor::find($author->id)->name);
	}

	public function testIsDirty()
	{
		$author = Author::first();
		$this->assertEquals(false,$author->is_dirty());

		$author->name = 'coco';
		$this->assertEquals(true,$author->is_dirty());
	}

	public function testSetDateFlagsDirty()
	{
		$author = Author::create(array('some_date' => new DateTime()));
		$author = Author::find($author->id);
		$author->some_date->setDate(2010,1,1);
		$this->assertHasKeys('some_date', $author->dirty_attributes());
	}

	public function testSetDateFlagsDirtyWithPhpDatetime()
	{
		$author = Author::create(array('some_date' => new \DateTime()));
		$author = Author::find($author->id);
		$author->some_date->setDate(2010,1,1);
		$this->assertHasKeys('some_date', $author->dirty_attributes());
	}

	public function testDeleteAllWithConditionsAsString()
	{
		$num_affected = Author::delete_all(array('conditions' => 'parent_author_id = 2'));
		$this->assertEquals(2, $num_affected);
	}

	public function testDeleteAllWithConditionsAsHash()
	{
		$num_affected = Author::delete_all(array('conditions' => array('parent_author_id' => 2)));
		$this->assertEquals(2, $num_affected);
	}

	public function testDeleteAllWithConditionsAsArray()
	{
		$num_affected = Author::delete_all(array('conditions' => array('parent_author_id = ?', 2)));
		$this->assertEquals(2, $num_affected);
	}

	public function testDeleteAllWithLimitAndOrder()
	{
		if (!$this->conn->accepts_limit_and_order_for_update_and_delete())
			$this->markTestSkipped('Only MySQL & Sqlite accept limit/order with UPDATE clause');

		$num_affected = Author::delete_all(array('conditions' => array('parent_author_id = ?', 2), 'limit' => 1, 'order' => 'name asc'));
		$this->assertEquals(1, $num_affected);
		$this->assertTrue(strpos(Author::table()->last_sql, 'ORDER BY name asc LIMIT 1') !== false);
	}

	public function testUpdateAllWithSetAsString()
	{
		$num_affected = Author::update_all(array('set' => 'parent_author_id = 2'));
		$this->assertEquals(2, $num_affected);
		$this->assertEquals(4, Author::count_by_parent_author_id(2));
	}

	public function testUpdateAllWithSetAsHash()
	{
		$num_affected = Author::update_all(array('set' => array('parent_author_id' => 2)));
		$this->assertEquals(2, $num_affected);
	}

	/**
	 * TODO: not implemented
	public function testUpdateAllWithSetAsArray()
	{
		$num_affected = Author::update_all(array('set' => array('parent_author_id = ?', 2)));
		$this->assertEquals(2, $num_affected);
	}
	 */

	public function testUpdateAllWithConditionsAsString()
	{
		$num_affected = Author::update_all(array('set' => 'parent_author_id = 2', 'conditions' => 'name = "Tito"'));
		$this->assertEquals(1, $num_affected);
	}

	public function testUpdateAllWithConditionsAsHash()
	{
		$num_affected = Author::update_all(array('set' => 'parent_author_id = 2', 'conditions' => array('name' => "Tito")));
		$this->assertEquals(1, $num_affected);
	}

	public function testUpdateAllWithConditionsAsArray()
	{
		$num_affected = Author::update_all(array('set' => 'parent_author_id = 2', 'conditions' => array('name = ?', "Tito")));
		$this->assertEquals(1, $num_affected);
	}

	public function testUpdateAllWithLimitAndOrder()
	{
		if (!$this->conn->accepts_limit_and_order_for_update_and_delete())
			$this->markTestSkipped('Only MySQL & Sqlite accept limit/order with UPDATE clause');

		$num_affected = Author::update_all(array('set' => 'parent_author_id = 2', 'limit' => 1, 'order' => 'name asc'));
		$this->assertEquals(1, $num_affected);
		$this->assertTrue(strpos(Author::table()->last_sql, 'ORDER BY name asc LIMIT 1') !== false);
	}

	public function testUpdateNativeDatetime()
	{
		$author = Author::create(array('name' => 'Blah Blah'));
		$native_datetime = new \DateTime('1983-12-05');
		$author->some_date = $native_datetime;
		$this->assertFalse($native_datetime === $author->some_date);
	}

	public function testUpdateOurDatetime()
	{
		$author = Author::create(array('name' => 'Blah Blah'));
		$our_datetime = new DateTime('1983-12-05');
		$author->some_date = $our_datetime;
		$this->assertTrue($our_datetime === $author->some_date);
	}

}

<?php

class ActiveRecordFindTest extends DatabaseTest
{
	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function testFindWithNoParams()
	{
		Author::find();
	}

	public function testFindByPk()
	{
		$author = Author::find(3);
		$this->assertEquals(3,$author->id);
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function testFindByPknoResults()
	{
		Author::find(99999999);
	}

	public function testFindByMultiplePkWithPartialMatch()
	{
		try
		{
			Author::find(1,999999999);
			$this->fail();
		}
		catch (ActiveRecord\RecordNotFound $e)
		{
			$this->assertTrue(strpos($e->getMessage(),'found 1, but was looking for 2') !== false);
		}
	}

	public function testFindByPkWithOptions()
	{
		$author = Author::find(3,array('order' => 'name'));
		$this->assertEquals(3,$author->id);
		$this->assertTrue(strpos(Author::table()->last_sql,'ORDER BY name') !== false);
	}

	public function testFindByPkArray()
	{
		$authors = Author::find(1,'2');
		$this->assertEquals(2, count($authors));
		$this->assertEquals(1, $authors[0]->id);
		$this->assertEquals(2, $authors[1]->id);
	}

	public function testFindByPkArrayWithOptions()
	{
		$authors = Author::find(1,'2',array('order' => 'name'));
		$this->assertEquals(2, count($authors));
		$this->assertTrue(strpos(Author::table()->last_sql,'ORDER BY name') !== false);
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function testFindNothingWithSqlInString()
	{
		Author::first('name = 123123123');
	}

	public function testFindAll()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(?)',array(1,2,3))));
		$this->assertTrue(count($authors) >= 3);
	}

	public function testFindAllWithNoBindValues()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(1,2,3)')));
		$this->assertEquals(1,$authors[0]->author_id);
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testFindAllWithEmptyArrayBindValueThrowsException()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(?)', array())));
		$this->assertCount(0,$authors);
	}

	public function testFindHashUsingAlias()
	{
		$venues = Venue::all(array('conditions' => array('marquee' => 'Warner Theatre', 'city' => array('Washington','New York'))));
		$this->assertTrue(count($venues) >= 1);
	}

	public function testFindHashUsingAliasWithNull()
	{
		$venues = Venue::all(array('conditions' => array('marquee' => null)));
		$this->assertEquals(0,count($venues));
	}

	public function testDynamicFinderUsingAlias()
	{
		$this->assertNotNull(Venue::find_by_marquee('Warner Theatre'));
	}

	public function testFindAllHash()
	{
		$books = Book::find('all',array('conditions' => array('author_id' => 1)));
		$this->assertTrue(count($books) > 0);
	}

	public function testFindAllHashWithOrder()
	{
		$books = Book::find('all',array('conditions' => array('author_id' => 1), 'order' => 'name DESC'));
		$this->assertTrue(count($books) > 0);
	}

	public function testFindAllNoArgs()
	{
		$author = Author::all();
		$this->assertTrue(count($author) > 1);
	}

	public function testFindAllNoResults()
	{
		$authors = Author::find('all',array('conditions' => array('author_id IN(11111111111,22222222222,333333333333)')));
		$this->assertEquals(array(),$authors);
	}

	public function testFindFirst()
	{
		$author = Author::find('first',array('conditions' => array('author_id IN(?)', array(1,2,3))));
		$this->assertEquals(1,$author->author_id);
		$this->assertEquals('Tito',$author->name);
	}

	public function testFindFirstNoResults()
	{
		$this->assertNull(Author::find('first',array('conditions' => 'author_id=1111111')));
	}

	public function testFindFirstUsingPk()
	{
		$author = Author::find('first',3);
		$this->assertEquals(3,$author->author_id);
	}

	public function testFindFirstWithConditionsAsString()
	{
		$author = Author::find('first',array('conditions' => 'author_id=3'));
		$this->assertEquals(3,$author->author_id);
	}

	public function testFindAllWithConditionsAsString()
	{
		$author = Author::find('all',array('conditions' => 'author_id in(2,3)'));
		$this->assertEquals(2,count($author));
	}

	public function testFindBySql()
	{
		$author = Author::find_by_sql("SELECT * FROM authors WHERE author_id in(1,2)");
		$this->assertEquals(1,$author[0]->author_id);
		$this->assertEquals(2,count($author));
	}

	public function testFindBySqltakesValuesArray()
	{
		$author = Author::find_by_sql("SELECT * FROM authors WHERE author_id=?",array(1));
		$this->assertNotNull($author);
	}

	public function testFindWithConditions()
	{
		$author = Author::find(array('conditions' => array('author_id=? and name=?', 1, 'Tito')));
		$this->assertEquals(1,$author->author_id);
	}

	public function testFindLast()
	{
		$author = Author::last();
		$this->assertEquals(4, $author->author_id);
		$this->assertEquals('Uncle Bob',$author->name);
	}

	public function testFindLastUsingStringCondition()
	{
		$author = Author::find('last', array('conditions' => 'author_id IN(1,2,3,4)'));
		$this->assertEquals(4, $author->author_id);
		$this->assertEquals('Uncle Bob',$author->name);
	}

	public function testLimitBeforeOrder()
	{
		$authors = Author::all(array('limit' => 2, 'order' => 'author_id desc', 'conditions' => 'author_id in(1,2)'));
		$this->assertEquals(2,$authors[0]->author_id);
		$this->assertEquals(1,$authors[1]->author_id);
	}

	public function testForEach()
	{
		$i = 0;
		$res = Author::all();

		foreach ($res as $author)
		{
			$this->assertTrue($author instanceof ActiveRecord\Model);
			$i++;
		}
		$this->assertTrue($i > 0);
	}

	public function testFetchAll()
	{
		$i = 0;

		foreach (Author::all() as $author)
		{
			$this->assertTrue($author instanceof ActiveRecord\Model);
			$i++;
		}
		$this->assertTrue($i > 0);
	}

	public function testCount()
	{
		$this->assertSame(1,Author::count(1));
		$this->assertSame(2,Author::count(array(1,2)));
		$this->assertTrue(Author::count() > 1);
		$this->assertSame(0,Author::count(array('conditions' => 'author_id=99999999999999')));
		$this->assertSame(2,Author::count(array('conditions' => 'author_id=1 or author_id=2')));
		$this->assertSame(1,Author::count(array('name' => 'Tito', 'author_id' => 1)));
	}

	public function testGh149EmptyCount()
	{
		$total = Author::count();
		$this->assertEquals($total, Author::count(null));
		$this->assertEquals($total, Author::count(array()));
	}

	public function testExists()
	{
		$this->assertTrue(Author::exists(1));
		$this->assertTrue(Author::exists(array('conditions' => 'author_id=1')));
		$this->assertTrue(Author::exists(array('conditions' => array('author_id=? and name=?', 1, 'Tito'))));
		$this->assertFalse(Author::exists(9999999));
		$this->assertFalse(Author::exists(array('conditions' => 'author_id=999999')));
	}

	public function testFindByCallStatic()
	{
		$this->assertEquals('Tito',Author::find_by_name('Tito')->name);
		$this->assertEquals('Tito',Author::find_by_author_id_and_name(1,'Tito')->name);
		$this->assertEquals('George W. Bush',Author::find_by_author_id_or_name(2,'Tito',array('order' => 'author_id desc'))->name);
		$this->assertEquals('Tito',Author::find_by_name(array('Tito','George W. Bush'),array('order' => 'name desc'))->name);
	}

	public function testFindByCallStaticNoResults()
	{
		$this->assertNull(Author::find_by_name('SHARKS WIT LASERZ'));
		$this->assertNull(Author::find_by_name_or_author_id());
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testFindByCallStaticInvalidColumnName()
	{
		Author::find_by_sharks();
	}

	public function testFindAllByCallStatic()
	{
		$x = Author::find_all_by_name('Tito');
		$this->assertEquals('Tito',$x[0]->name);
		$this->assertEquals(1,count($x));

		$x = Author::find_all_by_author_id_or_name(2,'Tito',array('order' => 'name asc'));
		$this->assertEquals(2,count($x));
		$this->assertEquals('George W. Bush',$x[0]->name);
	}

	public function testFindAllByCallStaticNoResults()
	{
		$x = Author::find_all_by_name('SHARKSSSSSSS');
		$this->assertEquals(0,count($x));
	}

	public function testFindAllByCallStaticWithArrayValuesAndOptions()
	{
		$author = Author::find_all_by_name(array('Tito','Bill Clinton'),array('order' => 'name desc'));
		$this->assertEquals('Tito',$author[0]->name);
		$this->assertEquals('Bill Clinton',$author[1]->name);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testFindAllByCallStaticUndefinedMethod()
	{
		Author::find_sharks('Tito');
	}

	public function testFindAllTakesLimitOptions()
	{
		$authors = Author::all(array('limit' => 1, 'offset' => 2, 'order' => 'name desc'));
		$this->assertEquals('George W. Bush',$authors[0]->name);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testFindByCallStaticWithInvalidFieldName()
	{
		Author::find_by_some_invalid_field_name('Tito');
	}

	public function testFindWithSelect()
	{
		$author = Author::first(array('select' => 'name, 123 as bubba', 'order' => 'name desc'));
		$this->assertEquals('Uncle Bob',$author->name);
		$this->assertEquals(123,$author->bubba);
	}

	public function testFindWithSelectNonSelectedFieldsShouldNotHaveAttributes()
	{
		$author = Author::first(array('select' => 'name, 123 as bubba'));
		try {
			$author->id;
			$this->fail('expected ActiveRecord\UndefinedPropertyExecption');
		} catch (ActiveRecord\UndefinedPropertyException $e) {
			;
		}
	}

	public function testJoinsOnModelWithAssociationAndExplicitJoins()
	{
		JoinBook::$belongs_to = array(array('author'));
		JoinBook::first(array('joins' => array('author','LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)')));
		$this->assertSqlHas('INNER JOIN authors ON(books.author_id = authors.author_id)',JoinBook::table()->last_sql);
		$this->assertSqlHas('LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)',JoinBook::table()->last_sql);
	}

	public function testJoinsOnModelWithExplicitJoins()
	{
		JoinBook::first(array('joins' => array('LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)')));
		$this->assertSqlHas('LEFT JOIN authors a ON(books.secondary_author_id=a.author_id)',JoinBook::table()->last_sql);
	}

	public function testGroup()
	{
		$venues = Venue::all(array('select' => 'state', 'group' => 'state'));
		$this->assertTrue(count($venues) > 0);
		$this->assertSqlHas('GROUP BY state',ActiveRecord\Table::load('Venue')->last_sql);
	}

	public function testGroupWithOrderAndLimitAndHaving()
	{
		$venues = Venue::all(array('select' => 'state', 'group' => 'state', 'having' => 'length(state) = 2', 'order' => 'state', 'limit' => 2));
		$this->assertTrue(count($venues) > 0);
		$this->assertSqlHas($this->conn->limit('SELECT state FROM venues GROUP BY state HAVING length(state) = 2 ORDER BY state',null,2),Venue::table()->last_sql);
	}

	public function testEscapeQuotes()
	{
		$author = Author::find_by_name("Tito's");
		$this->assertNotEquals("Tito's",Author::table()->last_sql);
	}

	public function testFrom()
	{
		$author = Author::find('first', array('from' => 'books', 'order' => 'author_id asc'));
		$this->assertTrue($author instanceof Author);
		$this->assertNotNull($author->book_id);

		$author = Author::find('first', array('from' => 'authors', 'order' => 'author_id asc'));
		$this->assertTrue($author instanceof Author);
		$this->assertEquals(1, $author->id);
	}

	public function testHaving()
	{
		if ($this->conn instanceof ActiveRecord\OciAdapter)
		{
			$author = Author::first(array(
				'select' => 'to_char(created_at,\'YYYY-MM-DD\') as created_at',
				'group'  => 'to_char(created_at,\'YYYY-MM-DD\')',
				'having' => "to_char(created_at,'YYYY-MM-DD') > '2009-01-01'"));
			$this->assertSqlHas("GROUP BY to_char(created_at,'YYYY-MM-DD') HAVING to_char(created_at,'YYYY-MM-DD') > '2009-01-01'",Author::table()->last_sql);
		}
		else
		{
			$author = Author::first(array(
				'select' => 'date(created_at) as created_at',
				'group'  => 'date(created_at)',
				'having' => "date(created_at) > '2009-01-01'"));
			$this->assertSqlHas("GROUP BY date(created_at) HAVING date(created_at) > '2009-01-01'",Author::table()->last_sql);
		}
	}

	/**
	 * @expectedException ActiveRecord\DatabaseException
	 */
	public function testFromWithInvalidTable()
	{
		$author = Author::find('first', array('from' => 'wrong_authors_table'));
	}

	public function testFindWithHash()
	{
		$this->assertNotNull(Author::find(array('name' => 'Tito')));
		$this->assertNotNull(Author::find('first',array('name' => 'Tito')));
		$this->assertEquals(1,count(Author::find('all',array('name' => 'Tito'))));
		$this->assertEquals(1,count(Author::all(array('name' => 'Tito'))));
	}

	public function testFindOrCreateByOnExistingRecord()
	{
		$this->assertNotNull(Author::find_or_create_by_name('Tito'));
	}

	public function testFindOrCreateByCreatesNewRecord()
	{
		$author = Author::find_or_create_by_name_and_encrypted_password('New Guy','pencil');
		$this->assertTrue($author->author_id > 0);
		$this->assertEquals('pencil',$author->encrypted_password);
	}

	/**
	 * @expectedException ActiveRecord\ActiveRecordException
	 */
	public function testFindOrCreateByThrowsExceptionWhenUsingOr()
	{
		Author::find_or_create_by_name_or_encrypted_password('New Guy','pencil');
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function testFindByZero()
	{
		Author::find(0);
	}

	/**
	 * @expectedException ActiveRecord\RecordNotFound
	 */
	public function testFindByNull()
	{
		Author::find(null);
	}

	public function testCountBy()
	{
		$this->assertEquals(2,Venue::count_by_state('VA'));
		$this->assertEquals(3,Venue::count_by_state_or_name('VA','Warner Theatre'));
		$this->assertEquals(0,Venue::count_by_state_and_name('VA','zzzzzzzzzzzzz'));
	}

	public function testFindByPkShouldNotUseLimit()
	{
		Author::find(1);
		$this->assertSqlHas('SELECT * FROM authors WHERE author_id=?',Author::table()->last_sql);
	}

	public function testFindByDatetime()
	{
		$now = new DateTime();
		$arnow = new ActiveRecord\DateTime();
		$arnow->setTimestamp($now->getTimestamp());

		Author::find(1)->update_attribute('created_at',$now);
		$this->assertNotNull(Author::find_by_created_at($now));
		$this->assertNotNull(Author::find_by_created_at($arnow));
	}
}

<?php

use ActiveRecord\Expressions;
use ActiveRecord\ConnectionManager;
use ActiveRecord\DatabaseException;

class ExpressionsTest extends TestCase
{
	public function testValues()
	{
		$c = new Expressions(null,'a=? and b=?',1,2);
		$this->assertEquals(array(1,2), $c->values());
	}

	public function testOneVariable()
	{
		$c = new Expressions(null,'name=?','Tito');
		$this->assertEquals('name=?',$c->to_s());
		$this->assertEquals(array('Tito'),$c->values());
	}

	public function testArrayVariable()
	{
		$c = new Expressions(null,'name IN(?) and id=?',array('Tito','George'),1);
		$this->assertEquals(array(array('Tito','George'),1),$c->values());
	}

	public function testMultipleVariables()
	{
		$c = new Expressions(null,'name=? and book=?','Tito','Sharks');
		$this->assertEquals('name=? and book=?',$c->to_s());
		$this->assertEquals(array('Tito','Sharks'),$c->values());
	}

	public function testToString()
	{
		$c = new Expressions(null,'name=? and book=?','Tito','Sharks');
		$this->assertEquals('name=? and book=?',$c->to_s());
	}

	public function testToStringWithArrayVariable()
	{
		$c = new Expressions(null,'name IN(?) and id=?',array('Tito','George'),1);
		$this->assertEquals('name IN(?,?) and id=?',$c->to_s());
	}

	public function testToStringWithNullOptions()
	{
		$c = new Expressions(null,'name=? and book=?','Tito','Sharks');
		$x = null;
		$this->assertEquals('name=? and book=?',$c->to_s(false,$x));
	}

	/**
	 * @expectedException ActiveRecord\ExpressionsException
	 */
	public function testInsufficientVariables()
	{
		$c = new Expressions(null,'name=? and id=?','Tito');
		$c->to_s();
	}

	public function testNoValues()
	{
		$c = new Expressions(null,"name='Tito'");
		$this->assertEquals("name='Tito'",$c->to_s());
		$this->assertEquals(0,count($c->values()));
	}

	public function testNullVariable()
	{
		$a = new Expressions(null,'name=?',null);
		$this->assertEquals('name=?',$a->to_s());
		$this->assertEquals(array(null),$a->values());
	}

	public function testZeroVariable()
	{
		$a = new Expressions(null,'name=?',0);
		$this->assertEquals('name=?',$a->to_s());
		$this->assertEquals(array(0),$a->values());
	}

	public function testEmptyArrayVariable()
	{
		$a = new Expressions(null,'id IN(?)',array());
		$this->assertEquals('id IN(?)',$a->to_s());
		$this->assertEquals(array(array()),$a->values());
	}

	public function testIgnoreInvalidParameterMarker()
	{
		$a = new Expressions(null,"question='Do you love backslashes?' and id in(?)",array(1,2));
		$this->assertEquals("question='Do you love backslashes?' and id in(?,?)",$a->to_s());
	}

	public function testIgnoreParameterMarkerWithEscapedQuote()
	{
		$a = new Expressions(null,"question='Do you love''s backslashes?' and id in(?)",array(1,2));
		$this->assertEquals("question='Do you love''s backslashes?' and id in(?,?)",$a->to_s());
	}

	public function testIgnoreParameterMarkerWithBackspaceEscapedQuote()
	{
		$a = new Expressions(null,"question='Do you love\\'s backslashes?' and id in(?)",array(1,2));
		$this->assertEquals("question='Do you love\\'s backslashes?' and id in(?,?)",$a->to_s());
	}

	public function testSubstitute()
	{
		$a = new Expressions(null,'name=? and id=?','Tito',1);
		$this->assertEquals("name='Tito' and id=1",$a->to_s(true));
	}

	public function testSubstituteQuotesScalarsButNotOthers()
	{
		$a = new Expressions(null,'id in(?)',array(1,'2',3.5));
		$this->assertEquals("id in(1,'2',3.5)",$a->to_s(true));
	}

	public function testSubstituteWhereValueHasQuestionMark()
	{
		$a = new Expressions(null,'name=? and id=?','??????',1);
		$this->assertEquals("name='??????' and id=1",$a->to_s(true));
	}

	public function testSubstituteArrayValue()
	{
		$a = new Expressions(null,'id in(?)',array(1,2));
		$this->assertEquals("id in(1,2)",$a->to_s(true));
	}

	public function testSubstituteEscapesQuotes()
	{
		$a = new Expressions(null,'name=? or name in(?)',"Tito's Guild",array(1,"Tito's Guild"));
		$this->assertEquals("name='Tito''s Guild' or name in(1,'Tito''s Guild')",$a->to_s(true));
	}

	public function testSubstituteEscapeQuotesWithConnectionsEscapeMethod()
	{
		try {
			$conn = ConnectionManager::get_connection();
		} catch (DatabaseException $e) {
			$this->markTestSkipped('failed to connect. '.$e->getMessage());
		}
		$a = new Expressions(null,'name=?',"Tito's Guild");
		$a->set_connection($conn);
		$escaped = $conn->escape("Tito's Guild");
		$this->assertEquals("name=$escaped",$a->to_s(true));
	}

	public function testBind()
	{
		$a = new Expressions(null,'name=? and id=?','Tito');
		$a->bind(2,1);
		$this->assertEquals(array('Tito',1),$a->values());
	}

	public function testBindOverwriteExisting()
	{
		$a = new Expressions(null,'name=? and id=?','Tito',1);
		$a->bind(2,99);
		$this->assertEquals(array('Tito',99),$a->values());
	}

	/**
	 * @expectedException ActiveRecord\ExpressionsException
	 */
	public function testBindInvalidParameterNumber()
	{
		$a = new Expressions(null,'name=?');
		$a->bind(0,99);
	}

	public function testSubsituteUsingAlternateValues()
	{
		$a = new Expressions(null,'name=?','Tito');
		$this->assertEquals("name='Tito'",$a->to_s(true));
		$x = array('values' => array('Hocus'));
		$this->assertEquals("name='Hocus'",$a->to_s(true,$x));
	}

	public function testNullValue()
	{
		$a = new Expressions(null,'name=?',null);
		$this->assertEquals('name=NULL',$a->to_s(true));
	}

	public function testHashWithDefaultGlue()
	{
		$a = new Expressions(null,array('id' => 1, 'name' => 'Tito'));
		$this->assertEquals('id=? AND name=?',$a->to_s());
	}

	public function testHashWithGlue()
	{
		$a = new Expressions(null,array('id' => 1, 'name' => 'Tito'),', ');
		$this->assertEquals('id=?, name=?',$a->to_s());
	}

	public function testHashWithArray()
	{
		$a = new Expressions(null,array('id' => 1, 'name' => array('Tito','Mexican')));
		$this->assertEquals('id=? AND name IN(?,?)',$a->to_s());
	}
}

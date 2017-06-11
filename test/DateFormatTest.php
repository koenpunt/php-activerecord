<?php

class DateFormatTest extends DatabaseTest
{

	public function testDatefieldGetsConvertedToArDatetime()
	{
		//make sure first author has a date
		$author = Author::first();
		$author->some_date = new DateTime();
		$author->save();
		
		$author = Author::first();
		$this->assertIsA("ActiveRecord\\DateTime",$author->some_date);
	}

}

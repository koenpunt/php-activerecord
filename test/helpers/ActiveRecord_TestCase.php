<?php

require_once __DIR__ . '/../constraints/CountGreaterThan.php';
require_once __DIR__ . '/../constraints/Count.php';

class ActiveRecord_TestCase extends PHPUnit_Framework_TestCase
{

	private function setup_assert_keys($args)
	{
		$last = count($args)-1;
		$keys = array_slice($args,0,$last);
		$array = $args[$last];
		return array($keys,$array);
	}

	public function assert_has_keys(/* $keys..., $array */)
	{
		list($keys,$array) = $this->setup_assert_keys(func_get_args());

		$this->assertNotNull($array,'Array was null');

		foreach ($keys as $name)
			$this->assertArrayHasKey($name,$array);
	}

	public function assert_doesnt_has_keys(/* $keys..., $array */)
	{
		list($keys,$array) = $this->setup_assert_keys(func_get_args());

		foreach ($keys as $name)
			$this->assertArrayNotHasKey($name,$array);
	}

	public function assertDateTimeEquals($expected, $actual)
	{
		$this->assertEquals($expected->getTimestamp(), $actual->getTimestamp());
	}

	public function assertCountGreaterThan($expected, $actual, $message = '')
	{
		$this->assertThat($actual, new Constraint\CountGreaterThan($expected), $message);
	}

	public function assertCountNotGreaterThan($expected, $actual, $message = '')
	{
		$this->assertThat($actual, $this->logicalNot(new Constraint\CountGreaterThan($expected)), $message);
	}

	public function assertCountGreaterThanOrEqual($expected, $actual, $message = '')
	{
		$this->assertThat($actual, $this->logicalOr(new Constraint\CountGreaterThan($expected), new Constraint\Count($expected)), $message);
	}

	public function assertSqlContains($expected, $actual, $message = '')
	{
		$actual = str_replace('`', '', $actual);
		$this->assertThat($actual, $this->stringContains($expected), $message);
	}

	public function assertSqlNotContains($expected, $actual, $message = '')
	{
		$actual = str_replace('`', '', $actual);
		$this->assertThat($actual, $this->logicalNot($this->stringContains($expected)), $message);
	}

}

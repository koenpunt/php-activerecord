<?php

namespace Constraint;

class Count extends \PHPUnit_Framework_Constraint_Count
{
	/**
	 * Returns a string representation of the constraint.
	 *
	 * Due to a bug in PHPUnit the error message misses the values of the constraint.
	 *  So that's the reason of this extension (may be fixed soon: sebastianbergmann/phpunit#1528)
	 *
	 * @return string
	 */
	public function toString()
	{
		return sprintf(
			'count matches %d',
			$this->expectedCount
		);
	}
}

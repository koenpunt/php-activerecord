<?php

namespace Constraint;

class CountGreaterThan extends \PHPUnit_Framework_Constraint_Count
{
	/**
	 * Evaluates the constraint for parameter $other. Returns TRUE if the
	 * constraint is met, FALSE otherwise.
	 *
	 * @param mixed $other Value or object to evaluate.
	 * @return bool
	 */
	protected function matches($other)
	{
		return $this->getCountOf($other) > $this->expectedCount;
	}

	/**
	 * Returns the description of the failure
	 *
	 * The beginning of failure messages is "Failed asserting that" in most
	 * cases. This method should return the second part of that sentence.
	 *
	 * @param  mixed  $other Evaluated value or object.
	 * @return string
	 */
	protected function failureDescription($other)
	{
		return sprintf(
			'actual size %d is greater than expected size %d',
			$this->getCountOf($other),
			$this->expectedCount
		);
	}

	/**
	 * Returns a string representation of the constraint.
	 *
	 * @return string
	 */
	public function toString()
	{
		return sprintf(
			'count is greater than %d',
			$this->expectedCount
		);
	}
}

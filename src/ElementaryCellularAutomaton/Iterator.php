<?php

namespace ElementaryCellularAutomaton;

// TODO: allow setting edge type (wrapping, rect (on), rect (off))
// TODO: make start value more adjustable:
// 		['...', 0, 1, 0, '...'] => [0, 0, 0, 0, 1, 0, 0, 0, 0]
//		['..', 0, 1, 0, '....'] => [0, 0, 1, 0, 0, 0, 0]  -> 2x as many on side with 2x as many dots
//		['random'] => random
//		['...', 1, 0, '...'] => [1, 1, 1, 1, 0, 0, 0, 0]
//		etc.

/**
 * Class Iterator
 *
 * @see https://en.wikipedia.org/wiki/Elementary_cellular_automaton
 * @package ElementaryCellularAutomaton
 */
class Iterator implements \Iterator {

	/**
	 * The rule to use
	 * 0 <= $rule <= 255
	 *
	 * @see https://en.wikipedia.org/wiki/Elementary_cellular_automaton#The_numbering_system
	 * @var int
	 */
	protected $rule = 0;

	/**
	 * The processed rule array
	 *
	 * @var array
	 */
	protected $rule_array = [];

	/**
	 * The width of the world
	 * 0 <= width
	 *
	 * @var int
	 */
	protected $width = 0;

	/**
	 * The starting array for the world's first row
	 *
	 * If len($start) < $width, 0s will be appended until len($start) = $width
	 * If len($start) > $width, $start will be truncated
	 *
	 * @var array
	 */
	protected $start = [];

	/**
	 * The iterable key
	 *
	 * @var int
	 */
	protected $key = 0;

	/**
	 * The current row of the iteration
	 * When $key = 0, $current = $start
	 *
	 * @var array
	 *
	 * @throws Exception
	 */
	protected $current;

	public function __construct(int $rule = 0, int $width = 0, array $start = []) {
		try {
			$this->setRule($rule);
			$this->setWidth($width);
			$this->setStart($start);

			$this->rewind();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * @param int $rule
	 *
	 * @throws Exception
	 */
	public function setRule(int $rule): void {
		if (($rule < 0) || (255 < $rule)) {
			throw new Exception("Rule out of bounds, must be between 0 and 255, inclusive");
		}

		$this->rule = $rule;
	}

	/**
	 * @return int
	 */
	public function getRule(): int {
		return $this->rule;
	}

	/**
	 * @param int $width
	 *
	 * @throws Exception
	 */
	public function setWidth(int $width): void {
		if ($width < 0) {
			throw new Exception("Width must be a positive integer");
		}

		$this->width = $width;
	}

	/**
	 * @return int
	 */
	public function getWidth(): int {
		return $this->width;
	}

	/**
	 * @param array $start
	 */
	public function setStart(array $start): void {
		$this->start = $start;
	}

	/**
	 * @return array
	 */
	public function getStart(): array {
		return $this->start;
	}

	/**
	 * Adjust the length of the $start array to match $width
	 */
	protected function adjustStart() {
		$this->start = array_pad($this->start, $this->width, 0);
		$this->start = array_slice($this->start, 0, $this->width);
		$this->current = $this->start;
	}

	/**
	 * Return the current element
	 *
	 * @return array
	 */
	public function current() {
		return $this->current;
	}

	/**
	 * Return the key of the current element
	 *
	 * @return int
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * Move forward to next element
	 */
	public function next() {
		$next = array_fill(0, $this->width, 0);

		for ($n = 0; $n < $this->width; ++$n) {
			$next[$n] = $this->getNextCell($this->current[ $n - 1 ] ?? 0, $this->current[ $n ], $this->current[ $n + 1 ] ?? 0);
		}

		$this->current = $next;
		++$this->key;
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	public function rewind() {
		$this->adjustStart();
		$this->genRuleArray();
		$this->key = 0;
		$this->current = $this->start;
	}

	/**
	 * Checks if current position is valid
	 * Cellular Automaton is always valid
	 *
	 * @return bool
	 */
	public function valid() {
		return true;
	}

	/**
	 * Calculate the next cell given the three adjacent cells above
	 *
	 * @param int $left cell above ( 0 | 1 )
	 * @param int $center cell above ( 0 | 1 )
	 * @param int $right cell above ( 0 | 1 )
	 *
	 * @return int next cell ( 0 | 1 )
	 */
	public function calcNextCell(int $left, int $center, int $right): int {
		$n = 0;
		if ($left)   { $n |= 4; }
		if ($center) { $n |= 2; }
		if ($right)  { $n |= 1; }

		return (int) (($this->rule & pow(2, $n)) > 0);
	}

	/**
	 * Get the next cell from the rule array
	 * This method is only marginally faster than calcNextCell at the time of testing.
	 * 2.85s vs 3.04s for 10000000 iterations
	 *
	 * @param int $left cell above ( 0 | 1 )
	 * @param int $center cell above ( 0 | 1 )
	 * @param int $right cell above ( 0 | 1 )
	 *
	 * @return int next cell ( 0 | 1 )
	 */
	public function getNextCell(int $left, int $center, int $right): int {
		if ( ! $this->rule_array) {
			$this->genRuleArray();
		}

		$key = bindec($left . $center . $right);
		return $this->rule_array[$key];
	}

	/**
	 * Generate the rule array from the rule
	 */
	protected function genRuleArray() {
		$this->rule_array = array_fill(0, 8, 0);

		$bin = str_pad(decbin($this->rule), 8, '0', STR_PAD_LEFT);

		for ($n = 0; $n < 8; ++$n) {
			$this->rule_array[$n] = (int) (bool) $bin[$n];
		}

		$this->rule_array = array_reverse($this->rule_array);
	}

	/**
	 * @return array
	 */
	public function getRuleArray(): array {
		if ( ! $this->rule_array) {
			$this->genRuleArray();
		}

		return $this->rule_array;
	}

}

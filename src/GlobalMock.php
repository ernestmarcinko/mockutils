<?php

namespace ErnestMarcinko\MockUtils;

use phpmock\Mock;
use phpmock\MockBuilder;
use phpmock\MockEnabledException;

/**
 * Helper for mocking global PHP functions
 */
class GlobalMock {
	private static ?self $the_instance = null;

	/**
	 * @var array<string, Mock>
	 */
	private static array $mocked = array();

	/**
	 * Creates a global function mock with a pre-determined response
	 *
	 * @param string $function_name
	 * @param mixed|callable $return
	 * @param string $namespace
	 * @throws MockEnabledException
	 */
	public function mock(string $function_name, mixed $return, string $namespace): void {
		self::disable($function_name);
		$builder = new MockBuilder();
		$builder->setNamespace($namespace)
			->setName($function_name)
			->setFunction(is_callable($return) ? $return : fn()=>$return);
		$mock = $builder->build();
		$mock->enable();
		self::$mocked[$function_name] = $mock;
	}

	/**
	 * Disables all global function mocks or a single function mock if $function_name is set.
	 *
	 * @param string $function_name
	 * @return void
	 */
	public function disable(string $function_name = ''): void {
		if ($function_name === '') {
			foreach (self::$mocked as $name => $return) {
				self::$mocked[$name]->disable();
				unset(self::$mocked[$name]);
			}
		} elseif (isset(self::$mocked[$function_name])) {
			self::$mocked[$function_name]->disable();
			unset(self::$mocked[$function_name]);
		}
	}

	protected function  __construct() { }

	final protected function  __clone() { }
	final public static function instance(): self {
		if (self::$the_instance == null) {
			self::$the_instance = new self();
		}
		return self::$the_instance;
	}

}

<?php

namespace ErnestMarcinko\MockUtils;

use Exception;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;

/**
 * Mock utility methods for PHPUnit PHPUnit\Framework\TestCase
 *
 * This trait should be only used in PHPUnit\Framework\TestCase and descendants.
 */
trait MockUtils {
	protected string $mock_namespace = __NAMESPACE__;

	/**
	 * Defines the global mocks via an array of function_name=>reponse|callable
	 *
	 * Note, that this only works if the tested code is in a Namespace. Global functions in
	 * Global namespace can't be mocked. The $namespace parameter should be the actual code namespace,
	 * not the test code namespace.
	 *
	 * @param array<string, mixed|callable> $global_mocks key as function name, value as response value or callable
	 * @param string|null $namespace the tested code namespace
	 * @return void
	 */
	protected function setGlobalMocks(array $global_mocks, ?string $namespace): void {
		try {
			$this->mock_namespace = $namespace ?? $this->mock_namespace;
			foreach ($global_mocks as $function_name => $return) {
				GlobalMock::instance()->mock($function_name, $return, $this->mock_namespace);
			}
		} catch (Exception $e) {
			$this->fail('Mocking failed: ' . $e->getMessage());
		}
	}

	/**
	 * Unsets the listed global mocks, or all if $global_mock is not set
	 *
	 * @param array|null $global_mocks
	 * @return void
	 */
	protected function unsetGlobalMocks(?array $global_mocks=null): void {
		if (isset($global_mocks)) {
			foreach ($global_mocks as $global_mock) {
				GlobalMock::instance()->disable($global_mock);
			}
		} else {
			GlobalMock::instance()->disable();
		}
	}

	/**
	 * Checks if the exception was thrown without execution termination
	 *
	 * Compared to TestCase::expectException this function will not terminate the
	 * test execution. The function to test must be however passed in as a closure,
	 * ex.: expectCatchException(fn()=>$o->myMethod(), ...)
	 *
	 * @param callable $fn Callable function, use a closure like fn()=>your_method()
	 * @param class-string<Throwable> $throwable Throwable class
	 * @param string|null $message Message to compare to, not required
	 * @return void
	 * @throws AssertionFailedError
	 */
	protected function expectCatchException(callable $fn, string $throwable, ?string $message = null): void {
		if (!is_subclass_of( $throwable, Throwable::class)) {
			$this->fail("Class $throwable, is not a subclass of " . Throwable::class);
		}
		try {
			call_user_func($fn);
		} catch (Throwable $e) {
			$this->assertSame($throwable, get_class($e));
			if (isset($message)) {
				$this->assertSame($message, $e->getMessage());
			}
		}
		if (!isset($e)) {
			$this->fail("Expected to throw $throwable, but no exceptions were thrown.");
		}
	}
}
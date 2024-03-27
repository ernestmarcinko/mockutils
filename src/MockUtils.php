<?php

namespace ErnestMarcinko\MockUtils;

use ErrorException;
use Exception;
use InvalidArgumentException;
use Throwable;

trait MockUtils {
	protected string $mock_namespace = __NAMESPACE__;

	/**
	 * Defines the global mocks via an array of function_name=>reponse
	 *
	 * @param array<string, mixed> $global_mocks key as function name, value as response
	 * @param string|null $namespace the function namespace
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
	 * @param class-string<Throwable> $throwable
	 * @param callable $fn
	 * @return void
	 * @throws ErrorException
	 */
	protected function expectCatchException(string $throwable, callable $fn): void {
		if (!is_subclass_of( $throwable, Throwable::class)) {
			throw new InvalidArgumentException("Class $throwable, is not a subclass of " . Throwable::class);
		}
		try {
			call_user_func($fn);
		} catch (Throwable $e) {
			$this->assertSame($throwable, get_class($e));
		}
		if (!isset($e)) {
			throw new ErrorException("Expected to throw $throwable, but no exceptions were thrown.");
		}
	}
}
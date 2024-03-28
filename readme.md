# Mock Utility tools for PHPUnit TestCase

![tests](https://github.com/ernestmarcinko/mockutils/actions/workflows/tests.yml/badge.svg)

This package provides ome utility functions for PHPUnit TestCase class (and descendants) via the `MockUtils` trait:
- [Global Mocks](#global-mocks) - `setGlobalMocks()` & `unsetGlobalMocks()` methods to set global function mocks within a namespace
- [Exception assertion](#exception-assertion) - via `expectCatchException()` - Method similar to expectException(), but without terminating the test execution

## Installation & Inclusion
To install the package:
```shell
composer require ernestmarcinko/mockutils --dev
```

### Including in your tests
There are two ways, one is using a trait to extend your TestCase functionality (recommended):
```php
use ErnestMarcinko\MockUtils\MockUtils;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase {
	use MockUtils;
	...
}
```

..or use the MockUtilsTestCase class instead of TestCase:

```php
use ErnestMarcinko\MockUtils\MockUtilsTestCase;

class MyTest extends MockUtilsTestCase {
	...
}
```
`MockUtilsTestCase` is only an empty class extending `TestCase` and using `MockUtils`.

## Global Mocks

The trait adds two utility functions to `setGlobalMocks()` and `unsetGlobalMocks()` to set and unset global mocks.

### setGlobalMocks

```php
protected function setGlobalMocks(array $global_mocks, ?string $namespace): void
```

Sets global mocks to the given namespace.

#### Parameters

| Param           | Type                            | Description                                                       | Required | Extra info                                                       |
|-----------------|---------------------------------|-------------------------------------------------------------------|----------|------------------------------------------------------------------|
| `$global_mocks` | `array<string,mixed\|callable>` | Array of global function name and return value or callable        | true     |                                                                  |
| `$namespace`    | `string`                        | The namespace of the code where the global function should be set | false    | Make sure to use the code namespace, not the test code namespace |

#### Example

Say you have a service, which uses `curl_exec` to get a response from an API. During testing you want to mock it
and avoid actual connection to the API and instead test with pre-defined responses.

```php
namespace MyNamespace\MySubNamespace;

class MyClass {
	public function handler() {
		//....
		
		$response = curl_exec($curl); // you want to mock this
		
		//You do something with $response below
	}
}
```
In the test we need the curl_exec to return 'response' for the mock, to do that:

```php
namespace MyTestNamespace;

class TestMyClass {
	public function testHandler() {
		$this->setGlobalMocks(
			[
				'curl_exec' => 'response'
			], 
			'MyNamespace\\MySubNamespace'
		)
	
		$o = new MyClass();
		$this->assertSame( 'expected output', $o->handler() );
	}
}
```

It's also possible to define a callable instead of a static response:

```php
namespace MyTestNamespace;

class TestMyClass {
	public function testHandler() {
		$this->setGlobalMocks(
			[
				'curl_exec' => function($curl) {
					return 'response';
				}
			], 
			'MyNamespace\\MySubNamespace'
		)
	
		$o = new MyClass();
		$this->assertSame( 'expected output', $o->handler() );
	}
}
```

### unsetGlobalMocks

```php
protected function unsetGlobalMocks(?array $global_mocks=null): void
```

Unsets the given global mocks or all global mocks previously defined.

#### Parameters

| Param           | Type            | Description                    | Required | Extra info                                                       |
|-----------------|-----------------|--------------------------------|----------|------------------------------------------------------------------|
| `$global_mocks` | `array<string>` | Array of global function names | false    |                                                                  |

#### Examples

```php
namespace MyTestNamespace;

class TestMyClass {
	public function testHandler() {
		$this->setGlobalMocks(
			[
				'time' => fn()=>time()-3600,
				'json_decode' => array(),
				'strval' => fn($v)=>$v,
			], 
			'MyNamespace\\MySubNamespace'
		)
		$o = new MyClass();
		$this->assertSame( 'expected output 1', $o->handler() );
		
		$this->unsetGlobalMocks(array('time')); // unset just the time mock
		$this->assertSame( 'expected output 2', $o->handler() );
		
		$this->unsetGlobalMocks(); // unset all remaining mocks
		$this->assertSame( 'expected output 3', $o->handler() );
	}
}
```

## Exception Assertion

### expectCatchException

Checks if the exception was thrown **without** execution termination.

```php
protected function expectCatchException(callable $fn, string $throwable, ?string $message = null): void 
```

Compared to PHPUnit core `TestCase::expectException` this function will not terminate the test execution.
The function to test must be however passed in as a closure, ex.: `expectCatchException(fn()=>$o->myMethod(), ...)`

#### Parameters

| Param        | Type                      | Description                   | Required | Extra info                                                                  |
|--------------|---------------------------|-------------------------------|----------|-----------------------------------------------------------------------------|
| `$fn`        | `callable`                | Function to test              | true     | Use closure `fn()=>{$myObj->myMethod();}` to pass in any method             |
| `$throwable` | `class-string<Throwable>` | Expected exception class name | true     |                                                                             |
| `$message`   | `string`                  | The expected error message    | false    | (optional) If set, then the function will also check the Exception message. |

#### Return values
The function is void, does not return anything, however:

> `expectCatchException` will trigger `TestCase::fail()` if no exception was thrown, or any of the criteria was not met.

#### Example

```php
namespace MyTestNamespace;

class TestMyClass {
	public function testHandler() {
		$this->expectCatchException(function(){
			throw new Exception('hey!');
		}, Exception::class);
		
		// Execution continues
		
		$this->expectCatchException(function(){
			throw new Exception('hey!');
		}, Exception::class, 'hey!');
		
		$o = new MyClass();
		$this->expectCatchException(fn()=>$o->handle(), Exception::class);
		
		$this->expectCatchException(
			fn()=>$o->handle(), 
			Exception::class,
			'Exception message!'
		);
	}
}
```
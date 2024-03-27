<?php

namespace ErnestMarcinko\MockUtilsTests;

use ErnestMarcinko\MockUtils\MockUtilsTestCase;
use ErrorException;
use Exception;
use InvalidArgumentException;

class TestMockUtilsTestCase extends MockUtilsTestCase {
	public function testSetGlobalMocks(): void {
		$this->assertSame('', strval(''));
		$this->assertSame('1', strval('1'));
		$this->assertNotSame(1, strval(1));
		$this->assertNotSame(true, strval(true));
		$this->assertNotSame(null, strval(null));

		$this->setGlobalMocks(
			[
				'strval' => fn($x)=>$x,
				'json_encode' => function( int $n ){
					return $n * 2;
				},
				'intval' => 1,
			],
			__NAMESPACE__
		);
		$this->assertSame('', strval(''));
		$this->assertSame('1', strval('1'));
		$this->assertSame(1, strval(1));
		$this->assertSame(true, strval(true));
		$this->assertSame(null, strval(null));

		$this->assertSame(0, json_encode(0));
		$this->assertSame(4, json_encode(2));
		$this->assertSame(-4, json_encode(-2));

		$this->assertSame(1, intval(-2));
		$this->assertSame(1, intval(false));
		$this->assertSame(1, intval(null));

		$this->unsetGlobalMocks(array('intval')); // Unset intval only
		$this->assertNotSame(1, intval(null));
		$this->assertSame(4, json_encode(2));
		$this->assertSame(true, strval(true));

		$this->unsetGlobalMocks(); // Unset all
		$this->assertSame(1, intval(1));
		$this->assertSame('2', json_encode(2));
		$this->assertSame('1', strval(true));

		// Different namespace
		$this->setGlobalMocks(
			[
				'strval' => fn($x)=>$x,
				'json_encode' => function( int $n ){
					return $n * 2;
				},
				'intval' => 1,
			],
			'ErnestMarcinko\\MockUtils'
		);
		$this->assertNotSame(1, strval(1)); // Current namespace
		$this->assertSame(1, \ErnestMarcinko\MockUtils\strval(1)); // Defined in other namespace
	}

	/**
	 * @throws ErrorException
	 */
	public function testExpectCatchException():void {
		$this->expectCatchException(Exception::class, function(){
			throw new Exception('hey!');
		});
		$this->expectCatchException(ErrorException::class, function(){
			throw new ErrorException('hey you!');
		});

		// Use itself to test itself for missing exception in function
		$this->expectCatchException(
			ErrorException::class,
			// Will throw ErrorException
			fn()=>$this->expectCatchException(InvalidArgumentException::class, function(){})
		);

		// Use itself to test itself for bad class
		$this->expectCatchException(
			InvalidArgumentException::class,
			// Will throw InvalidArgumentException
			fn()=>$this->expectCatchException('RandomClass', function(){}) // @phpstan-ignore-line
		);
	}
}
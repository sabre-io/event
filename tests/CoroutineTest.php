<?php declare (strict_types=1);

namespace Sabre\Event;

use Exception;

class CoroutineTest extends \PHPUnit\Framework\TestCase {

    /**
     * @expectedException \InvalidArgumentException
     */
    function testNonGenerator() {

        coroutine(function() {});

    }

    function testBasicCoroutine() {

        $start = 0;

        coroutine(function() use (&$start) {

            $start += 1;
            yield;

        });

        $this->assertEquals(1, $start);

    }

    function testFulfilledPromise() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $fulfill(2);
        });

        coroutine(function() use (&$start, $promise) {

            $start += 1;
            $start += yield $promise;

        });

        Loop\run();
        $this->assertEquals(3, $start);

    }

    function testRejectedPromise() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $reject(new Exception("2"));
        });

        coroutine(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += yield $promise;
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\Exception $e) {
                $start += $e->getMessage();
            }

        });

        Loop\run();
        $this->assertEquals(3, $start);

    }

    function testRejectedPromiseException() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $reject(new \LogicException('2'));
        });

        coroutine(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += yield $promise;
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\LogicException $e) {
                $start += $e->getMessage();
            }

        });

        Loop\run();
        $this->assertEquals(3, $start);

    }

    function testFulfilledPromiseAsync() {

        $start = 0;
        $promise = new Promise();
        coroutine(function() use (&$start, $promise) {

            $start += 1;
            $start += yield $promise;

        });
        Loop\run();

        $this->assertEquals(1, $start);

        $promise->fulfill(2);
        Loop\run();

        $this->assertEquals(3, $start);

    }

    function testRejectedPromiseAsync() {

        $start = 0;
        $promise = new Promise();
        coroutine(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += yield $promise;
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\Exception $e) {
                $start += $e->getMessage();
            }

        });

        $this->assertEquals(1, $start);

        $promise->reject(new \Exception((string)2));
        Loop\run();

        $this->assertEquals(3, $start);

    }

    function testCoroutineException() {

        $start = 0;
        coroutine(function() use (&$start) {

            $start += 1;
            $start += yield 2;

            throw new \Exception('4');

        })->otherwise(function($e) use (&$start) {

            $start += $e->getMessage();

        });
        Loop\run();

        $this->assertEquals(7, $start);

    }

    function testDeepException() {

        $start = 0;
        $promise = new Promise();
        coroutine(function() use (&$start, $promise) {

            $start += 1;
            $start += yield $promise;

        })->otherwise(function($e) use (&$start) {

            $start += $e->getMessage();

        });

        $this->assertEquals(1, $start);

        $promise->reject(new \Exception((string)2));
        Loop\run();

        $this->assertEquals(3, $start);

    }

    function testReturn() {

        $ok = false;
        coroutine(function() {

            yield 1;
            yield 2;
            $hello = 'hi';
            return 3;

        })->then(function($value) use (&$ok) {
            $this->assertEquals(3, $value);
            $ok = true;
        })->otherwise(function($reason) {
            $this->fail($reason);
        });
        Loop\run();

        $this->assertTrue($ok);

    }

    function testReturnPromise() {

        $ok = false;

        $promise = new Promise();

        coroutine(function() use ($promise) {

            yield 'fail';
            return $promise;

        })->then(function($value) use (&$ok) {
            $ok = $value;
        })->otherwise(function($reason) {
            $this->fail($reason);
        });

        $promise->fulfill('omg it worked');
        Loop\run();

        $this->assertEquals('omg it worked', $ok);

    }

}

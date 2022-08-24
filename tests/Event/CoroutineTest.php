<?php

declare(strict_types=1);

namespace Sabre\Event;

use Exception;

class CoroutineTest extends \PHPUnit\Framework\TestCase
{
    public function testNonGenerator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        coroutine(function () {});
    }

    public function testBasicCoroutine(): void
    {
        $start = 0;

        coroutine(function () use (&$start) {
            ++$start;
            yield;
        });

        $this->assertEquals(1, $start);
    }

    public function testFulfilledPromise(): void
    {
        $start = 0;
        $promise = new Promise(function ($fulfill, $reject) {
            $fulfill(2);
        });

        coroutine(function () use (&$start, $promise) {
            ++$start;
            $start += yield $promise;
        });

        Loop\run();
        $this->assertEquals(3, $start);
    }

    public function testRejectedPromise(): void
    {
        $start = 0;
        $promise = new Promise(function ($fulfill, $reject) {
            $reject(new Exception('2'));
        });

        coroutine(function () use (&$start, $promise) {
            ++$start;
            try {
                $start += yield $promise;
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\Exception $e) {
                $start += (int) $e->getMessage();
            }
        });

        Loop\run();
        $this->assertEquals(3, $start);
    }

    public function testRejectedPromiseException(): void
    {
        $start = 0;
        $promise = new Promise(function ($fulfill, $reject) {
            $reject(new \LogicException('2'));
        });

        coroutine(function () use (&$start, $promise) {
            ++$start;
            try {
                $start += yield $promise;
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\LogicException $e) {
                $start += (int) $e->getMessage();
            }
        });

        Loop\run();
        $this->assertEquals(3, $start);
    }

    public function testFulfilledPromiseAsync(): void
    {
        $start = 0;
        $promise = new Promise();
        coroutine(function () use (&$start, $promise) {
            ++$start;
            $start += yield $promise;
        });
        Loop\run();

        $this->assertEquals(1, $start);

        $promise->fulfill(2);
        Loop\run();

        $this->assertEquals(3, $start);
    }

    public function testRejectedPromiseAsync(): void
    {
        $start = 0;
        $promise = new Promise();
        coroutine(function () use (&$start, $promise) {
            ++$start;
            try {
                $start += yield $promise;
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\Exception $e) {
                $start += (int) $e->getMessage();
            }
        });

        $this->assertEquals(1, $start);

        $promise->reject(new \Exception('2'));
        Loop\run();

        $this->assertEquals(3, $start);
    }

    public function testCoroutineException(): void
    {
        $start = 0;
        coroutine(function () use (&$start) {
            ++$start;
            $start += yield 2;

            throw new \Exception('4');
        })->otherwise(function ($e) use (&$start) {
            $start += $e->getMessage();
        });
        Loop\run();

        $this->assertEquals(7, $start);
    }

    public function testDeepException(): void
    {
        $start = 0;
        $promise = new Promise();
        coroutine(function () use (&$start, $promise) {
            ++$start;
            $start += yield $promise;
        })->otherwise(function ($e) use (&$start) {
            $start += $e->getMessage();
        });

        $this->assertEquals(1, $start);

        $promise->reject(new \Exception('2'));
        Loop\run();

        $this->assertEquals(3, $start);
    }

    public function testReturn(): void
    {
        $ok = false;
        coroutine(function () {
            yield 1;
            yield 2;
            $hello = 'hi';

            return 3;
        })->then(function ($value) use (&$ok) {
            $this->assertEquals(3, $value);
            $ok = true;
        })->otherwise(function ($reason) {
            $this->fail($reason);
        });
        Loop\run();

        $this->assertTrue($ok);
    }

    public function testReturnPromise(): void
    {
        $ok = false;

        $promise = new Promise();

        coroutine(function () use ($promise) {
            yield 'fail';

            return $promise;
        })->then(function ($value) use (&$ok) {
            $ok = $value;
        })->otherwise(function ($reason) {
            $this->fail($reason);
        });

        $promise->fulfill('omg it worked');
        Loop\run();

        $this->assertEquals('omg it worked', $ok);
    }
}

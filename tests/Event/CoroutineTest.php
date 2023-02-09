<?php

declare(strict_types=1);

namespace Sabre\Event;

class CoroutineTest extends \PHPUnit\Framework\TestCase
{
    public function testNonGenerator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        coroutine(function () {}); /* @phpstan-ignore-line */
    }

    public function testBasicCoroutine(): void
    {
        $start = 0;

        coroutine(function () use (&$start) {
            ++$start;
            yield;
        });

        self::assertEquals(1, $start);
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
        self::assertEquals(3, $start);
    }

    public function testRejectedPromise(): void
    {
        $start = 0;
        $promise = new Promise(function ($fulfill, $reject) {
            $reject(new \Exception('2'));
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
        self::assertEquals(3, $start);
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
        self::assertEquals(3, $start);
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

        self::assertEquals(1, $start);

        $promise->fulfill(2);
        Loop\run();

        self::assertEquals(3, $start);
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

        self::assertEquals(1, $start);

        $promise->reject(new \Exception('2'));
        Loop\run();

        self::assertEquals(3, $start);
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

        self::assertEquals(7, $start);
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

        self::assertEquals(1, $start);

        $promise->reject(new \Exception('2'));
        Loop\run();

        self::assertEquals(3, $start);
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
            self::assertEquals(3, $value);
            $ok = true;
        })->otherwise(function ($reason) {
            self::fail($reason);
        });
        Loop\run();

        self::assertTrue($ok);
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
            self::fail($reason);
        });

        $promise->fulfill('omg it worked');
        Loop\run();

        self::assertEquals('omg it worked', $ok);
    }
}

<?php

declare(strict_types=1);

namespace Sabre\Event\Promise;

use Exception;
use Sabre\Event\Loop;
use Sabre\Event\Promise;
use Sabre\Event\PromiseAlreadyResolvedException;

class PromiseTest extends \PHPUnit\Framework\TestCase
{
    public function testSuccess(): void
    {
        $finalValue = 0;
        $promise = new Promise();
        $promise->fulfill(1);

        $promise->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 2;
        });
        Loop\run();

        $this->assertEquals(3, $finalValue);
    }

    public function testFail(): void
    {
        $finalValue = 0;
        $promise = new Promise();
        $promise->reject(new Exception('1'));

        $promise->then(null, function ($value) use (&$finalValue) {
            $finalValue = $value->getMessage() + 2;
        });
        Loop\run();

        $this->assertEquals(3, $finalValue);
    }

    public function testChain(): void
    {
        $finalValue = 0;
        $promise = new Promise();
        $promise->fulfill(1);

        $promise->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 2;

            return $finalValue;
        })->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 4;

            return $finalValue;
        });
        Loop\run();

        $this->assertEquals(7, $finalValue);
    }

    public function testChainPromise(): void
    {
        $finalValue = 0;
        $promise = new Promise();
        $promise->fulfill(1);

        $subPromise = new Promise();

        $promise->then(function ($value) use ($subPromise) {
            return $subPromise;
        })->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 4;

            return $finalValue;
        });

        $subPromise->fulfill(2);
        Loop\run();

        $this->assertEquals(6, $finalValue);
    }

    public function testPendingResult(): void
    {
        $finalValue = 0;
        $promise = new Promise();

        $promise->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 2;
        });

        $promise->fulfill(4);
        Loop\run();

        $this->assertEquals(6, $finalValue);
    }

    public function testPendingFail(): void
    {
        $finalValue = 0;
        $promise = new Promise();

        $promise->otherwise(function ($value) use (&$finalValue) {
            $finalValue = $value->getMessage() + 2;
        });

        $promise->reject(new Exception('4'));
        Loop\run();

        $this->assertEquals(6, $finalValue);
    }

    public function testExecutorSuccess(): void
    {
        $promise = (new Promise(function ($success, $fail) {
            $success('hi');
        }))->then(function ($result) use (&$realResult) {
            $realResult = $result;
        });
        Loop\run();

        $this->assertEquals('hi', $realResult);
    }

    public function testExecutorFail(): void
    {
        $promise = (new Promise(function ($success, $fail) {
            $fail(new Exception('hi'));
        }))->then(function ($result) use (&$realResult) {
            $realResult = 'incorrect';
        })->otherwise(function ($reason) use (&$realResult) {
            $realResult = $reason->getMessage();
        });
        Loop\run();

        $this->assertEquals('hi', $realResult);
    }

    public function testFulfillTwice(): void
    {
        $this->expectException(PromiseAlreadyResolvedException::class);
        $promise = new Promise();
        $promise->fulfill(1);
        $promise->fulfill(1);
    }

    public function testRejectTwice(): void
    {
        $this->expectException(PromiseAlreadyResolvedException::class);
        $promise = new Promise();
        $promise->reject(new Exception('1'));
        $promise->reject(new Exception('1'));
    }

    public function testFromFailureHandler(): void
    {
        $ok = 0;
        $promise = new Promise();
        $promise->otherwise(function ($reason) {
            $this->assertEquals('foo', $reason);
            throw new \Exception('hi');
        })->then(function () use (&$ok) {
            $ok = -1;
        }, function () use (&$ok) {
            $ok = 1;
        });

        $this->assertEquals(0, $ok);
        $promise->reject(new Exception('foo'));
        Loop\run();

        $this->assertEquals(1, $ok);
    }

    public function testWaitResolve(): void
    {
        $promise = new Promise();
        Loop\nextTick(function () use ($promise) {
            $promise->fulfill(1);
        });
        $this->assertEquals(
            1,
            $promise->wait()
        );
    }

    public function testWaitWillNeverResolve(): void
    {
        $this->expectException(\LogicException::class);
        $promise = new Promise();
        $promise->wait();
    }

    public function testWaitRejectedException(): void
    {
        $promise = new Promise();
        Loop\nextTick(function () use ($promise) {
            $promise->reject(new \OutOfBoundsException('foo'));
        });
        try {
            $promise->wait();
            $this->fail('We did not get the expected exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf('OutOfBoundsException', $e);
            $this->assertEquals('foo', $e->getMessage());
        }
    }

    public function testWaitRejectedScalar(): void
    {
        $promise = new Promise();
        Loop\nextTick(function () use ($promise) {
            $promise->reject(new Exception('foo'));
        });
        try {
            $promise->wait();
            $this->fail('We did not get the expected exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('foo', $e->getMessage());
        }
    }
}

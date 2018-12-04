<?php

declare(strict_types=1);

namespace Sabre\Event\Promise;

use Exception;
use Sabre\Event\Loop;
use Sabre\Event\Promise;

class PromiseTest extends \PHPUnit\Framework\TestCase
{
    public function testSuccess()
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

    public function testFail()
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

    public function testChain()
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

    public function testChainPromise()
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

    public function testPendingResult()
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

    public function testPendingFail()
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

    public function testExecutorSuccess()
    {
        $promise = (new Promise(function ($success, $fail) {
            $success('hi');
        }))->then(function ($result) use (&$realResult) {
            $realResult = $result;
        });
        Loop\run();

        $this->assertEquals('hi', $realResult);
    }

    public function testExecutorFail()
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

    /**
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     */
    public function testFulfillTwice()
    {
        $promise = new Promise();
        $promise->fulfill(1);
        $promise->fulfill(1);
    }

    /**
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     */
    public function testRejectTwice()
    {
        $promise = new Promise();
        $promise->reject(new Exception('1'));
        $promise->reject(new Exception('1'));
    }

    public function testFromFailureHandler()
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

    public function testWaitResolve()
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

    /**
     * @expectedException \LogicException
     */
    public function testWaitWillNeverResolve()
    {
        $promise = new Promise();
        $promise->wait();
    }

    public function testWaitRejectedException()
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

    public function testWaitRejectedScalar()
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
	
	//////////////////////////////////
    public function testForwardsFulfilledDownChainBetweenGaps()
    {
        $p = new Promise();
        $r = $r2 = null;
        $p->then(null, null)
            ->then(function ($v) use (&$r) {$r = $v; return $v . '2'; })
            ->then(function ($v) use (&$r2) { $r2 = $v; });
        $p->fulfill('foo');
        Loop\run();
        $this->assertEquals('foo', $r);
        $this->assertEquals('foo2', $r2);
    }
	
    public function testForwardsHandlersToNextPromise()
    {		
        $p = new Promise();
        $p2 = new Promise();
        $resolved = null;
        $p
            ->then(function ($v) use ($p2) { return $p2; })
            ->then(function ($value) use (&$resolved) { $resolved = $value; });
        $p->fulfill('a');
        $p2->fulfill('b');
        Loop\run();
        $this->assertEquals('b', $resolved);
    }
	
    public function testForwardsHandlersWhenFulfilledPromiseIsReturned()
    {		
        $res = [];
        $p = new Promise();
        $p2 = new Promise();
        $p2->fulfill('foo');
        $p2->then(function ($v) use (&$res) { $res[] = 'A:' . $v; });
        // $res is A:foo
        $p
            ->then(function () use ($p2, &$res) { $res[] = 'B'; return $p2; })
            ->then(function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->fulfill('a');
        $p->then(function ($v) use (&$res) { $res[] = 'D:' . $v; });
        Loop\run();
        $this->assertEquals(['A:foo', 'B', 'D:a', 'C:foo'], $res);
    }	
}

<?php

namespace Sabre\Event\Promise;
//namespace GuzzleHttp\Promise\Tests;
//namespace React\Promise;
//namespace Async\Tests

use Exception;
use Sabre\Event\Loop;
use Sabre\Event\Promise;
use Sabre\Event\RejectionException;
use Sabre\Event\CancellationException;
use Sabre\Event\PromiseAlreadyResolvedException;
//use GuzzleHttp\Promise\Promise;
//use GuzzleHttp\Promise\TaskQueue;
//use GuzzleHttp\Promise\PromiseInterface;
//use GuzzleHttp\Promise\RejectionException;
//use GuzzleHttp\Promise\CancellationException;
//use Async\Loop\Loop;
//use Async\Promise\Promise;
//use Async\Promise\PromiseInterface;
//use Async\Promise\RejectionException;
//use Async\Promise\CancellationException;
//use React\Promise;
//use React\EventLoop\Factory;
//use React\Promise\Internal\Queue;
use PHPUnit\Framework\TestCase;

class InteroperabilityPromiseTest extends TestCase
{			
	const PENDING = Promise::PENDING;
	const REJECTED = Promise::REJECTED;
	const FULFILLED = Promise::FULFILLED;	
	//const PENDING = PromiseInterface::PENDING;
	//const REJECTED = PromiseInterface::REJECTED;
	//const FULFILLED = PromiseInterface::FULFILLED;	
	//const PENDING = PromiseInterface::STATE_PENDING;
	//const REJECTED = PromiseInterface::STATE_REJECTED;
	//const FULFILLED = PromiseInterface::STATE_RESOLVED;	
	//const PENDING = 'pending';
	//const REJECTED = 'rejected';	
	//const FULFILLED = 'fulfilled';

	private $loop = null;
	
	protected function setUp()
    {
		$this->loop = Loop\instance();
		//$this->loop = new TaskQueue();
		//Loop::clearInstance();
		//$this->loop = Promise::getLoop(true);
		//$this->loop = Factory::create();
		//$this->loop = new Queue();
    }
	
    public function testSuccess()
    {
        $finalValue = 0;
        $promise = new Promise();
        $promise->resolve(1);

        $promise->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 2;
        });
        $this->loop->run();

        $this->assertEquals(3, $finalValue);
    }

    public function testFailure()
    {
        $finalValue = 0;
        $promise = new Promise();
        $promise->reject(new Exception('1'));

        $promise->then(null, function ($value) use (&$finalValue) {
            $finalValue = $value->getMessage() + 2;
        });
        $this->loop->run();

        $this->assertEquals(3, $finalValue);
    }

    public function testChaining()
    {
        $finalValue = 0;
        $promise = new Promise();
        $promise->resolve(1);

        $promise->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 2;

            return $finalValue;
        })->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 4;

            return $finalValue;
        });
        $this->loop->run();

        $this->assertEquals(7, $finalValue);
    }
	
    public function testPendingResult()
    {
        $finalValue = 0;
        $promise = new Promise();

        $promise->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 2;
        });

        $promise->resolve(4);
        $this->loop->run();

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
        $this->loop->run();

        $this->assertEquals(6, $finalValue);
    }
	
    public function testChainingPromises()
    {
        $finalValue = 0;
        $promise = new Promise();
        $promise->resolve(1);

        $subPromise = new Promise();

        $promise->then(function ($value) use ($subPromise) {
            return $subPromise;
        })->then(function ($value) use (&$finalValue) {
            $finalValue = $value + 4;

            return $finalValue;
        });

        $subPromise->resolve(2);
        $this->loop->run();

        $this->assertEquals(6, $finalValue);
    }

	/**
	 * /expected Risky - No Tests Performed!
     * /or  Exception
     * @expectedException \Exception
     */
    public function testResolveTwice()
    {
        $promise = new Promise();
        $promise->resolve(1);
        $promise->resolve(1);
    }
	
	/**
	 * /expected Risky - No Tests Performed!
     * /or  Exception
     * @expectedException \Exception
     */
    public function testRejectTwice()
    {
        $promise = new Promise();
        $promise->reject(new Exception('1'));
        $promise->reject(new Exception('1'));
    }
	
    public function testConstructorCallResolve()
    {
        $promise = (new Promise(function ($resolve, $reject) {
            $resolve('hi');
        }))->then(function ($result) use (&$realResult) {
            $realResult = $result;
        });
        $this->loop->run();

        $this->assertEquals('hi', $realResult);
    }

    public function testConstructorCallReject()
    {
        $promise = (new Promise(function ($resolve, $reject) {
            $reject(new Exception('hi'));
        }))->then(function ($result) use (&$realResult) {
            $realResult = 'incorrect';
        })->otherwise(function ($reason) use (&$realResult) {
            $realResult = $reason->getMessage();
        });
        $this->loop->run();

        $this->assertEquals('hi', $realResult);
    }

    public function testWaitResolve()
    {
        $promise = new Promise();
        $this->loop->nextTick(function () use ($promise) {
        //$this->loop->addTick(function () use ($promise) {
        //$this->loop->enqueue(function () use ($promise) {
        //$this->loop->add(function () use ($promise) {
        //$this->loop->futureTick(function () use ($promise) {
            $promise->resolve(1);
        });
        $this->assertEquals(
            1,
            $promise->wait()
        );
    }
	
    public function testFailureHandler()
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
        $this->loop->run();

        $this->assertEquals(1, $ok);
    }
	
    public function testWaitRejectedException()
    {
        $promise = new Promise();
        $this->loop->nextTick(function () use ($promise) {
        //$this->loop->addTick(function () use ($promise) {
        //$this->loop->enqueue(function () use ($promise) {
        //$this->loop->add(function () use ($promise) {
        //$this->loop->futureTick(function () use ($promise) {
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
        $this->loop->nextTick(function () use ($promise) {
        //$this->loop->addTick(function () use ($promise) {
        //$this->loop->enqueue(function () use ($promise) {
        //$this->loop->add(function () use ($promise) {
        //$this->loop->futureTick(function () use ($promise) {
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
	
    public function testForwardsRejectedPromisesDownChainBetweenGaps()
    {
        $p = new Promise();
        $r = $r2 = null;
        $p->then(null, null)
            ->then(null, function ($v) use (&$r) { $r = $v; return $v . '2'; })
            ->then(function ($v) use (&$r2) { $r2 = $v; });
        $p->reject('foo');
        $this->loop->run();
        $this->assertEquals('foo', $r);
        $this->assertEquals('foo2', $r2);
    }
	
    public function testForwardsThrownPromisesDownChainBetweenGaps()
    {
        $e = new \Exception();
        $p = new Promise();
        $r = $r2 = null;
        $p->then(null, null)
            ->then(null, function ($v) use (&$r, $e) { 
                $r = $v;
                throw $e;
            })
            ->then(
                null,
                function ($v) use (&$r2) { $r2 = $v; }
            );
        $p->reject('foo');
        $this->loop->run();
        $this->assertEquals('foo', $r);
        $this->assertSame($e, $r2);
    }
	
    public function testForwardsHandlersWhenRejectedPromiseIsReturned()
    {
        $res = [];
        $p = new Promise();
        $p2 = new Promise();
        $p2->reject('foo');
        $p2->then(null, function ($v) use (&$res) { $res[] = 'A:' . $v; });
        $p->then(null, function () use ($p2, &$res) { $res[] = 'B'; return $p2; })
            ->then(null, function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->reject('a');
        $p->then(null, function ($v) use (&$res) { $res[] = 'D:' . $v; });
        $this->loop->run();
        $this->assertEquals(['A:foo', 'B', 'D:a', 'C:foo'], $res);
    }
	
    public function testForwardsFulfilledDownChainBetweenGaps()
    {
        $p = new Promise();
        $r = $r2 = null;
        $p->then(null, null)
            ->then(function ($v) use (&$r) {$r = $v; return $v . '2'; })
            ->then(function ($v) use (&$r2) { $r2 = $v; });
        $p->resolve('foo');
        $this->loop->run();
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
        $p->resolve('a');
        $p2->resolve('b');
        $this->loop->run();
        $this->assertEquals('b', $resolved);
    }
	
    public function testForwardsHandlersWhenFulfilledPromiseIsReturned()
    {		
        $res = [];
        $p = new Promise();
        $p2 = new Promise();
        $p2->resolve('foo');
        $p2->then(function ($v) use (&$res) { $res[] = 'A:' . $v; });
        // $res is A:foo
        $p
            ->then(function () use ($p2, &$res) { $res[] = 'B'; return $p2; })
            ->then(function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->resolve('a');
        $p->then(function ($v) use (&$res) { $res[] = 'D:' . $v; });
        $this->loop->run();
        $this->assertEquals(['A:foo', 'B', 'D:a', 'C:foo'], $res);
    }	
	
    public function testDoesNotForwardRejectedPromise()
    {
        $res = [];
        $p = new Promise();
        $p2 = new Promise();
        $p2->cancel();
        $p2->then(function ($v) use (&$res) { $res[] = "B:$v"; return $v; });
        $p->then(function ($v) use ($p2, &$res) { $res[] = "B:$v"; return $p2; })
            ->then(function ($v) use (&$res) { $res[] = 'C:' . $v; });
        $p->resolve('a');
        $p->then(function ($v) use (&$res) { $res[] = 'D:' . $v; });
        $this->loop->run();
        $this->assertEquals(['B:a', 'D:a'], $res);
    }
		
    /**
     * /expectedException \LogicException
	 * /expectedExceptionMessage The promise is already fulfilled
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     * @expectedExceptionMessage This promise is already resolved, and you're not allowed to resolve a promise more than once
     */
    public function testCannotResolveNonPendingPromise()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->resolve('bar');
        $this->assertEquals('foo', $p->wait());
    }
	
    /**
	 * /expected Risky - No Tests Performed!
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     * @expectedExceptionMessage This promise is already resolved, and you're not allowed to resolve a promise more than once
     */
    public function testCanResolveWithSameValue()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->resolve('foo');
    }
	
    /**
     * /expectedException \LogicException
     * /expectedExceptionMessage Cannot change a fulfilled promise to rejected
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     * @expectedExceptionMessage This promise is already resolved, and you're not allowed to resolve a promise more than once
     */
    public function testCannotRejectNonPendingPromise()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->reject('bar');
        $this->assertEquals('foo', $p->wait());
    }
	
    /**
	 * /expected Risky - No Tests Performed!
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     * @expectedExceptionMessage This promise is already resolved, and you're not allowed to resolve a promise more than once
     */
    public function testCanRejectWithSameValue()
    {
        $p = new Promise();
        $p->reject('foo');
        $p->reject('foo');
    }
	
    /**
     * /expectedException \LogicException
     * /expectedExceptionMessage Cannot change a fulfilled promise to rejected
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     * @expectedExceptionMessage This promise is already resolved, and you're not allowed to resolve a promise more than once
     */
    public function testCannotRejectResolveWithSameValue()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->reject('foo');
    }
	
	/**
     * /expectedException \Async\Promise\RejectionException
     * @expectedException \LogicException
     * @expectedExceptionMessage There were no more events in the loop. This promise will never be fulfilled.
     */
    public function testRejectsAndThrowsWhenWaitFailsToResolve()
    {
        $p = new Promise(function () {});
        $p->wait();
    }
	
    /**
     * /expectedException \Async\Promise\RejectionException
     * @expectedException \Exception
     */
    public function testThrowsWhenWaitingOnPromiseWithNoWaitFunction()
    {
        $p = new Promise();
        $p->wait();
    }	
	
    public function testCannotCancelNonPending()
    {
        $p = new Promise();
        $p->resolve('foo');
        $p->cancel();
        $this->assertEquals(self::FULFILLED, $p->getState());
    }
	
    /**
     * /expectedException \Async\Promise\CancellationException
     * @expectedException \Exception
     */
    public function testCancelsPromiseWhenNoCancelFunction()
    {
        $p = new Promise();
        $p->cancel();
        $this->assertEquals(self::REJECTED, $p->getState());
        $p->wait();
    }
	
    public function testCancelsPromiseWithCancelFunction()
    {
        $called = false;
        $p = new Promise(null, function () use (&$called) { $called = true; });
        $p->cancel();
        $this->assertEquals(self::REJECTED, $p->getState());
        $this->assertTrue($called);
    }
	
    public function testCancelsChildPromises()
    {
        $called1 = $called2 = $called3 = false;
        $p1 = new Promise(null, function () use (&$called1) { $called1 = true; });
        $p2 = new Promise(null, function () use (&$called2) { $called2 = true; });
        $p3 = new Promise(null, function () use (&$called3) { $called3 = true; });
        $p4 = $p2->then(function () use ($p3) { return $p3; });
        $p5 = $p4->then(function () { $this->fail(); });
        $p4->cancel();
        $this->assertEquals(self::PENDING, $p1->getState());
        $this->assertEquals(self::REJECTED, $p2->getState());
        $this->assertEquals(self::REJECTED, $p4->getState());
        $this->assertEquals(self::PENDING, $p5->getState());
        $this->assertFalse($called1);
        $this->assertTrue($called2);
        $this->assertFalse($called3);
    }
	
    public function testRejectsPromiseWhenCancelFails()
    {
        $called = false;
        $p = new Promise(null, function () use (&$called) {
            $called = true;
            throw new \Exception('e');
        });
        $p->cancel();
        $this->assertEquals(self::REJECTED, $p->getState());
        $this->assertTrue($called);
        try {
            $p->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('e', $e->getMessage());
        }
    }	
	
    public function testCreatesPromiseWhenRejectedWithNoCallback()
    {
        $p = new Promise();
        $p->reject('foo');
        $p2 = $p->then();
        $this->assertNotSame($p, $p2);
        $this->assertInstanceOf(Promise::class, $p2);
    }
	
    /**
     * /expectedException \Async\Promise\RejectionException
     * /expectedExceptionMessage The promise was rejected with reason: foo
     * @expectedException \Exception
     * @expectedExceptionMessage foo
     */
    public function testThrowsWhenUnwrapIsRejectedWithNonException()
    {
        $p = new Promise(function () use (&$p) { $p->reject('foo'); });
        $p->wait();
    }
	
    /**
     * /expectedException \Async\Promise\RejectionException
     * /expectedExceptionMessage The promise was rejected with reason: foo
     * @expectedException \Exception
     * @expectedExceptionMessage foo
     */
    public function testThrowsWhenUnwrapIsRejectedWithException()
    {
        $e = new \Exception('foo');
        $p = new Promise(function () use (&$p, $e) { $p->reject($e); });
        $p->wait();
    }
	
    public function testInvokesWaitFunction()
    {
        $p = new Promise(function () use (&$p) { $p->resolve('10'); });
        $this->assertEquals('10', $p->wait());
    }	
	
    public function testDoesNotUnwrapExceptionsWhenDisabled()
    {
        $p = new Promise(function () use (&$p) { $p->reject('foo'); });
        $this->assertEquals(self::PENDING, $p->getState());
        $p->wait(false);
        $this->assertEquals(self::REJECTED, $p->getState());
    }
	
    public function testRejectsSelfWhenWaitThrows()
    {
        $e = new \Exception('foo');
        $p = new Promise(function () use ($e) { throw $e; });
        try {
            $p->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals(self::REJECTED, $p->getState());
        }
    }
	
    public function testThrowsWaitExceptionAfterPromiseIsResolved()
    {
        $p = new Promise(function () use (&$p) {
            $p->reject('Foo!');
            throw new \Exception('Bar?');
        });
        try {
            $p->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('Bar?', $e->getMessage());
        }
    }
	
    public function testRemovesReferenceFromChildWhenParentWaitedUpon()
    {
        $r = null;
        $p = new Promise(function () use (&$p) { $p->resolve('a'); });
        $p2 = new Promise(function () use (&$p2) { $p2->resolve('b'); });
        $pb = $p->then(
            function ($v) use ($p2, &$r) {
                $r = $v;
                return $p2;
            })
            ->then(function ($v) { return $v . '.'; });
        $this->assertEquals('a', $p->wait());
        $this->assertEquals('b', $p2->wait());
        $this->assertEquals('b.', $pb->wait());
        $this->assertEquals('a', $r);
    }
	
    public function testWaitsOnNestedPromises()
    {		
        $p = new Promise(function () use (&$p) { $p->resolve('_'); });
        $p2 = new Promise(function () use (&$p2) { $p2->resolve('foo'); });
        $p3 = $p->then(function () use ($p2) { return $p2; });
        $this->assertSame('foo', $p3->wait());
    }
	
	
    public function testInvokesWaitFnsForThens()
    {
        $p = new Promise(function () use (&$p) { $p->resolve('a'); });
        $p2 = $p
            ->then(function ($v) { return $v . '-1-'; })
            ->then(function ($v) { return $v . '2'; });
        $this->assertEquals('a-1-2', $p2->wait());
    }
	
    public function testStacksThenWaitFunctions()
    {
        $p1 = new Promise(function () use (&$p1) { $p1->resolve('a'); });
        $p2 = new Promise(function () use (&$p2) { $p2->resolve('b'); });
        $p3 = new Promise(function () use (&$p3) { $p3->resolve('c'); });
        $p4 = $p1
            ->then(function () use ($p2) { return $p2; })
            ->then(function () use ($p3) { return $p3; });
        $this->assertEquals('c', $p4->wait());
    }
	
    public function testWaitsOnAPromiseChainEvenWhenNotUnwrapped()
    {		
        $p2 = new Promise(function () use (&$p2) {
            $p2->reject('Fail');
        });
        $p = new Promise(function () use ($p2, &$p) {
            $p->resolve($p2);
        });
        $p->wait(false);
        $this->assertSame(self::REJECTED, $p2->getState());
    }	
}

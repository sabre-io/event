<?php

namespace Sabre\Event\Promise;

use Exception;
use Sabre\Event\Loop;
use Sabre\Event\Promise;
use Sabre\Event\RejectionException;
use Sabre\Event\CancellationException;
use Sabre\Event\PromiseAlreadyResolvedException;
use PHPUnit\Framework\TestCase;

class NotWorkingPromiseTest extends TestCase
{		
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage foo
     */
    public function testThrowsWhenUnwrapIsRejectedWithNonException()
    {
        $p = new Promise(function () use (&$p) { $p->reject('foo'); });
        $p->wait();
    }
	
    /**
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
        $this->assertEquals(Promise::PENDING, $p->getState());
        $p->wait(false);
        $this->assertEquals(Promise::REJECTED, $p->getState());
    }
	
    public function testRejectsSelfWhenWaitThrows()
    {
        $e = new \Exception('foo');
        $p = new Promise(function () use ($e) { throw $e; });
        try {
            $p->wait();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals(Promise::REJECTED, $p->getState());
        }
    }
	
    public function testWaitsOnNestedPromises()
    {
        $p = new Promise(function () use (&$p) { $p->resolve('_'); });
        $p2 = new Promise(function () use (&$p2) { $p2->resolve('foo'); });
        $p3 = $p->then(function () use ($p2) { return $p2; });
        $this->assertSame('foo', $p3->wait());
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
		
    public function testWaitBehaviorIsBasedOnLastPromiseInChain()
    {
        $p3 = new Promise(function () use (&$p3) { $p3->resolve('Whoop'); });
        $p2 = new Promise(function () use (&$p2, $p3) { $p2->reject($p3); });
        $p = new Promise(function () use (&$p, $p2) { $p->reject($p2); });
        $this->assertEquals('Whoop', $p->wait());
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
        $this->assertSame(Promise::REJECTED, $p2->getState());
    }
	
    public function testCancelsUppermostPendingPromise()
    {
        $called = false;
        $p1 = new Promise(null, function () use (&$called) { $called = true; });
        $p2 = $p1->then(function () {});
        $p3 = $p2->then(function () {});
        $p4 = $p3->then(function () {});
        $p3->cancel();
        $this->assertEquals(Promise::REJECTED, $p1->getState());
        $this->assertEquals(Promise::REJECTED, $p2->getState());
        $this->assertEquals(Promise::REJECTED, $p3->getState());
        $this->assertEquals(Promise::PENDING, $p4->getState());
        $this->assertTrue($called);
        try {
            $p3->wait();
            $this->fail();
        } catch (CancellationException $e) {
            $this->assertContains('cancelled', $e->getMessage());
        }
        try {
            $p4->wait();
            $this->fail();
        } catch (CancellationException $e) {
            $this->assertContains('cancelled', $e->getMessage());
        }
        $this->assertEquals(Promise::REJECTED, $p4->getState());
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
	
    public function testDoesNotBlowStackWhenWaitingOnNestedThens()
    {
        $inner = new Promise(function () use (&$inner) { $inner->resolve(0); });
        $prev = $inner;
        for ($i = 1; $i < 100; $i++) {
            $prev = $prev->then(function ($i) { return $i + 1; });
        }
        $parent = new Promise(function () use (&$parent, $prev) {
            $parent->resolve($prev);
        });
        $this->assertEquals(99, $parent->wait());
    }
	
}

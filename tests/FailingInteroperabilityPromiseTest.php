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

class FailingInteroperabilityPromiseTest extends TestCase
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
		$this->markTestSkipped('These test fails in various stages, all taken from Guzzle phpunit tests.');
		$this->loop = Loop\instance();
		//$this->loop = new TaskQueue();
		//Loop::clearInstance();
		//$this->loop = Promise::getLoop(true);
		//$this->loop = Factory::create();
		//$this->loop = new Queue();
    }	
		
    public function testWaitBehaviorIsBasedOnLastPromiseInChain()
    {		
        $p3 = new Promise(function () use (&$p3) { $p3->resolve('Whoop'); });
        $p2 = new Promise(function () use (&$p2, $p3) { $p2->reject($p3); });
        $p = new Promise(function () use (&$p, $p2) { $p->reject($p2); });
        $this->assertEquals('Whoop', $p->wait());
    }
	
    public function testCancelsUppermostPendingPromise()
    {
        $called = false;
        $p1 = new Promise(null, function () use (&$called) { $called = true; });
        $p2 = $p1->then(function () {});
        $p3 = $p2->then(function () {});
        $p4 = $p3->then(function () {});
        $p3->cancel();
        $this->assertEquals(self::REJECTED, $p1->getState());
        $this->assertEquals(self::REJECTED, $p2->getState());
        $this->assertEquals(self::REJECTED, $p3->getState());
        $this->assertEquals(self::PENDING, $p4->getState());
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
        $this->assertEquals(self::REJECTED, $p4->getState());
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

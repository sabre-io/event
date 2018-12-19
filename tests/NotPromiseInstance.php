<?php

namespace Sabre\Tests;
//namespace GuzzleHttp\Promise\Tests;
//namespace React\Promise;
//namespace Async\Tests

use Sabre\Event\Promise;
use Sabre\Tests\Thennable;
//use GuzzleHttp\Promise\Promise;
//use GuzzleHttp\Promise\PromiseInterface;
//use Async\Promise\Promise;
//use Async\Promise\PromiseInterface;
//use React\Promise;
//use React\Promise\PromiseInterface;

class NotPromiseInstance extends Thennable // implements PromiseInterface
{
    private $nextPromise = null;

    public function __construct()
    {
        $this->nextPromise = new Promise();
    }

    public function then($done = null, $fail = null)
    {
        return $this->nextPromise->then($done, $fail);
    }

    public function done($done = null)
    {
        return $this->nextPromise->done($done);
    }
	
    public function fail($fail = null)
    {
        return $this->nextPromise->fail($fail);
    }
	
    public function otherwise($fail = null)
    {
        return $this->nextPromise->then(null, $fail);
    }

    public function resolve($value)
    {
        return $this->nextPromise->resolve($value);
    }

    public function reject($reason)
    {
        return $this->nextPromise->reject($reason);
    }

    public function wait($unwrap = true)
    {
        return $this->nextPromise->wait($unwrap);
    }

    public function progress($progress = null)
    {
        return $this->nextPromise->progress($progress);
    }
	
    public function always($always = null)
    {
        return $this->nextPromise->always($always);
    }
	
    public function cancel()
    {
        return $this->nextPromise->cancel();
    }

    public function getState()
    {
        return $this->nextPromise->getState();
    }
}

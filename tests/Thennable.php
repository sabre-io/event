<?php

namespace Sabre\Tests;
//namespace GuzzleHttp\Promise\Tests;
//namespace React\Promise;
//namespace Async\Tests

use Sabre\Event\Promise;
//use GuzzleHttp\Promise\Promise;
//use Async\Promise\Promise;
//use React\Promise;

class Thennable
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

    public function resolve($value)
    {
        return $this->nextPromise->resolve($value);
    }
}

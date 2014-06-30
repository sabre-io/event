<?php

namespace Sabre\Event;

class FlowTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \InvalidArgumentException
     */
    function testNonGenerator() {

        flow(function() {});

    }

    function testBasicFlow() {

        $start = 0;

        flow(function() use (&$start) {

            $start+=1;
            yield;

        });

        $this->assertEquals(1, $start);

    }

    function testFulfilledPromise() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $fulfill(2);
        });

        flow(function() use (&$start, $promise) {

            $start += 1;
            $start += (yield $promise);

        });

        $this->assertEquals(3, $start);

    }

    function testRejectedPromise() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $reject(2);
        });

        flow(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += (yield $promise);
                // This line is unreachable, but it's our control
                $start += 4;
            } catch(\Exception $e) {
                $start += $e->getMessage();
            }

        });

        $this->assertEquals(3, $start);

    }

    function testRejectedPromiseException() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $reject(new \LogicException('2'));
        });

        flow(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += (yield $promise);
                // This line is unreachable, but it's our control
                $start += 4;
            } catch(\LogicException $e) {
                $start += $e->getMessage();
            }

        });

        $this->assertEquals(3, $start);

    }

    function testRejectedPromiseArray() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $reject(array());
        });

        flow(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += (yield $promise);
                // This line is unreachable, but it's our control
                $start += 4;
            } catch(\Exception $e) {
                $this->assertTrue(strpos($e->getMessage(),'Promise was rejected with')===0);
                $start += 2;
            }

        });

        $this->assertEquals(3, $start);

    }

    function testFulfilledPromiseAsync() {

        $start = 0;
        $promise = new Promise();
        flow(function() use (&$start, $promise) {

            $start += 1;
            $start += (yield $promise);

        });

        $this->assertEquals(1, $start);

        $promise->fulfill(2);
        $this->assertEquals(3, $start);

    }

    function testRejectedPromiseAsync() {

        $start = 0;
        $promise = new Promise();
        flow(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += (yield $promise);
                // This line is unreachable, but it's our control
                $start += 4;
            } catch(\Exception $e) {
                $start += $e->getMessage();
            }

        });

        $this->assertEquals(1, $start);

        $promise->reject(new \Exception(2));
        $this->assertEquals(3, $start);

    }

    function testFlowException() {

        $start = 0;
        flow(function() use (&$start) {

            $start += 1;
            $start += (yield 2);

            throw new \Exception('4');

        })->error(function($e) use (&$start) {

            $start += $e->getMessage();

        });

        $this->assertEquals(7, $start);

    }

    function testDeepException() {

        $start = 0;
        $promise = new Promise();
        flow(function() use (&$start, $promise) {

            $start += 1;
            $start += (yield $promise);

        })->error(function($e) use (&$start) {

            $start += $e->getMessage();

        });

        $this->assertEquals(1, $start);

        $promise->reject(new \Exception(2));
        $this->assertEquals(3, $start);

    }

}

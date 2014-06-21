<?php

namespace Sabre\Event;

class PromiseTest extends \PHPUnit_Framework_TestCase {

    function testSuccess() {

        $finalValue = 0;
        $promise = new Promise();
        $promise->fulfill(1);

        $promise->then(function($value) use (&$finalValue) {
            $finalValue=$value + 2;
        });

        $this->assertEquals(3, $finalValue);

    }

    function testFail() {

        $finalValue = 0;
        $promise = new Promise();
        $promise->reject(1);

        $promise->then(null, function($value) use (&$finalValue) {
            $finalValue=$value + 2;
        });

        $this->assertEquals(3, $finalValue);

    }

    function testChain() {

        $finalValue = 0;
        $promise = new Promise();
        $promise->fulfill(1);

        $promise->then(function($value) use (&$finalValue) {
            $finalValue=$value + 2;
            return $finalValue;
        })->then(function($value) use (&$finalValue) {
            $finalValue = $value + 4;
            return $finalValue;
        });

        $this->assertEquals(7, $finalValue);


    }

    function testPendingResult() {

        $finalValue = 0;
        $promise = new Promise();


        $promise->then(function($value) use (&$finalValue) {
            $finalValue=$value + 2;
        });

        $promise->fulfill(4);
        $this->assertEquals(6, $finalValue);

    }

    public function testPendingFail() {

        $finalValue = 0;
        $promise = new Promise();


        $promise->then(null, function($value) use (&$finalValue) {
            $finalValue=$value + 2;
        });

        $promise->reject(4);
        $this->assertEquals(6, $finalValue);

    }

    public function testAll() {

        $promise1 = new Promise();
        $promise2 = new Promise();

        $finalValue = 0;
        Promise::all([$promise1, $promise2])->then(function($value) use (&$finalValue) {

            $finalValue = $value;

        });

        $promise1->fulfill(1);
        $this->assertEquals(0, $finalValue);
        $promise2->fulfill(2);
        $this->assertEquals([1,2], $finalValue);

    }

    public function testAllReject() {

        $promise1 = new Promise();
        $promise2 = new Promise();

        $finalValue = 0;
        Promise::all([$promise1, $promise2])->then(
            function($value) use (&$finalValue) {
                $finalValue = 'foo';
            },
            function($value) use (&$finalValue) {
                $finalValue = $value;
            }
        )->then(function($e) {
            echo "Horrible failure\n";
        }, function ($e) {
            echo "Foo\n";
        });

        $promise1->reject(1);
        $this->assertEquals(1, $finalValue);
        $promise2->reject(2);
        $this->assertEquals(1, $finalValue);

    }

}

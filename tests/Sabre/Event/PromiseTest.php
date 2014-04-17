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

    function testPendingFail() {

        $finalValue = 0;
        $promise = new Promise();


        $promise->then(null, function($value) use (&$finalValue) {
            $finalValue=$value + 2;
        });

        $promise->reject(4);
        $this->assertEquals(6, $finalValue);

    }
}

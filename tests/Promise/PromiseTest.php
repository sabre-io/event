<?php declare (strict_types=1);

namespace Sabre\Event\Promise;

use Exception;
use Sabre\Event\Loop;
use Sabre\Event\Promise;

class PromiseTest extends \PHPUnit\Framework\TestCase {

    function testSuccess() {

        $finalValue = 0;
        $promise = new Promise();
        $promise->fulfill(1);

        $promise->then(function($value) use (&$finalValue) {
            $finalValue = $value + 2;
        });
        Loop\run();

        $this->assertEquals(3, $finalValue);

    }

    function testFail() {

        $finalValue = 0;
        $promise = new Promise();
        $promise->reject(new Exception("1"));

        $promise->then(null, function($value) use (&$finalValue) {
            $finalValue = $value->getMessage() + 2;
        });
        Loop\run();

        $this->assertEquals(3, $finalValue);

    }

    function testChain() {

        $finalValue = 0;
        $promise = new Promise();
        $promise->fulfill(1);

        $promise->then(function($value) use (&$finalValue) {
            $finalValue = $value + 2;
            return $finalValue;
        })->then(function($value) use (&$finalValue) {
            $finalValue = $value + 4;
            return $finalValue;
        });
        Loop\run();

        $this->assertEquals(7, $finalValue);

    }
    function testChainPromise() {

        $finalValue = 0;
        $promise = new Promise();
        $promise->fulfill(1);

        $subPromise = new Promise();

        $promise->then(function($value) use ($subPromise) {
            return $subPromise;
        })->then(function($value) use (&$finalValue) {
            $finalValue = $value + 4;
            return $finalValue;
        });

        $subPromise->fulfill(2);
        Loop\run();

        $this->assertEquals(6, $finalValue);

    }

    function testPendingResult() {

        $finalValue = 0;
        $promise = new Promise();

        $promise->then(function($value) use (&$finalValue) {
            $finalValue = $value + 2;
        });

        $promise->fulfill(4);
        Loop\run();

        $this->assertEquals(6, $finalValue);

    }

    function testPendingFail() {

        $finalValue = 0;
        $promise = new Promise();

        $promise->otherwise(function($value) use (&$finalValue) {
            $finalValue = $value->getMessage() + 2;
        });

        $promise->reject(new Exception("4"));
        Loop\run();

        $this->assertEquals(6, $finalValue);

    }

    function testExecutorSuccess() {

        $promise = (new Promise(function($success, $fail) {

            $success('hi');

        }))->then(function($result) use (&$realResult) {

            $realResult = $result;

        });
        Loop\run();

        $this->assertEquals('hi', $realResult);

    }

    function testExecutorFail() {

        $promise = (new Promise(function($success, $fail) {

            $fail(new Exception('hi'));

        }))->then(function($result) use (&$realResult) {

            $realResult = 'incorrect';

        })->otherwise(function($reason) use (&$realResult) {

            $realResult = $reason->getMessage();

        });
        Loop\run();

        $this->assertEquals('hi', $realResult);

    }

    /**
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     */
    function testFulfillTwice() {

        $promise = new Promise();
        $promise->fulfill(1);
        $promise->fulfill(1);

    }

    /**
     * @expectedException \Sabre\Event\PromiseAlreadyResolvedException
     */
    function testRejectTwice() {

        $promise = new Promise();
        $promise->reject(new Exception("1"));
        $promise->reject(new Exception("1"));

    }

    function testFromFailureHandler() {

        $ok = 0;
        $promise = new Promise();
        $promise->otherwise(function($reason) {

            $this->assertEquals('foo', $reason);
            throw new \Exception('hi');

        })->then(function() use (&$ok) {

            $ok = -1;

        }, function() use (&$ok) {

            $ok = 1;

        });

        $this->assertEquals(0, $ok);
        $promise->reject(new Exception('foo'));
        Loop\run();

        $this->assertEquals(1, $ok);

    }

    function testWaitResolve() {

        $promise = new Promise();
        Loop\nextTick(function() use ($promise) {
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
    function testWaitWillNeverResolve() {

        $promise = new Promise();
        $promise->wait();

    }

    function testWaitRejectedException() {

        $promise = new Promise();
        Loop\nextTick(function() use ($promise) {
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

    function testWaitRejectedScalar() {

        $promise = new Promise();
        Loop\nextTick(function() use ($promise) {
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

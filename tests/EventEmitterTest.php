<?php

namespace Sabre\Event;

class EventEmitterTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $ee = new EventEmitter();
        $this->assertInstanceOf('Sabre\\Event\\EventEmitter', $ee);

    }

    function testListeners() {

        $ee = new EventEmitter();

        $callback1 = function() { };
        $callback2 = function() { };
        $ee->on('foo', $callback1, 200);
        $ee->on('foo', $callback2, 100);

        $this->assertEquals([$callback2, $callback1], $ee->listeners('foo'));

    }

    /**
     * @depends testInit
     */
    function testHandleEvent() {

        $argResult = null;

        $ee = new EventEmitter();
        $ee->on('foo', function($arg) use (&$argResult) {

            $argResult = $arg;

        });

        $this->assertTrue(
            $ee->emit('foo', ['bar'])
        );

        $this->assertEquals('bar', $argResult);

    }

    /**
     * @depends testHandleEvent
     */
    function testCancelEvent() {

        $argResult = 0;

        $ee = new EventEmitter();
        $ee->on('foo', function($arg) use (&$argResult) {

            $argResult = 1;
            return false;

        });
        $ee->on('foo', function($arg) use (&$argResult) {

            $argResult = 2;

        });

        $this->assertFalse(
            $ee->emit('foo', ['bar'])
        );

        $this->assertEquals(1, $argResult);

    }

    /**
     * @depends testCancelEvent
     */
    function testPriority() {

        $argResult = 0;

        $ee = new EventEmitter();
        $ee->on('foo', function($arg) use (&$argResult) {

            $argResult = 1;
            return false;

        });
        $ee->on('foo', function($arg) use (&$argResult) {

            $argResult = 2;
            return false;

        }, 1);

        $this->assertFalse(
            $ee->emit('foo', ['bar'])
        );

        $this->assertEquals(2, $argResult);

    }

    /**
     * @depends testPriority
     */
    function testPriority2() {

        $result = [];
        $ee = new EventEmitter();

        $ee->on('foo', function() use (&$result) {

            $result[] = 'a';

        }, 200);
        $ee->on('foo', function() use (&$result) {

            $result[] = 'b';

        }, 50);
        $ee->on('foo', function() use (&$result) {

            $result[] = 'c';

        }, 300);
        $ee->on('foo', function() use (&$result) {

            $result[] = 'd';

        });

        $ee->emit('foo');
        $this->assertEquals(['b', 'd', 'a', 'c'], $result);

    }

    function testRemoveListener() {

        $result = false;

        $callBack = function() use (&$result) {

            $result = true;

        };

        $ee = new EventEmitter();

        $ee->on('foo', $callBack);

        $ee->emit('foo');
        $this->assertTrue($result);
        $result = false;

        $this->assertTrue(
            $ee->removeListener('foo', $callBack)
        );

        $ee->emit('foo');
        $this->assertFalse($result);

    }

    function testRemoveUnknownListener() {

        $result = false;

        $callBack = function() use (&$result) {

            $result = true;

        };

        $ee = new EventEmitter();

        $ee->on('foo', $callBack);

        $ee->emit('foo');
        $this->assertTrue($result);
        $result = false;

        $this->assertFalse($ee->removeListener('bar', $callBack));

        $ee->emit('foo');
        $this->assertTrue($result);

    }

    function testRemoveListenerTwice() {

        $result = false;

        $callBack = function() use (&$result) {

            $result = true;

        };

        $ee = new EventEmitter();

        $ee->on('foo', $callBack);

        $ee->emit('foo');
        $this->assertTrue($result);
        $result = false;

        $this->assertTrue(
            $ee->removeListener('foo', $callBack)
        );
        $this->assertFalse(
            $ee->removeListener('foo', $callBack)
        );

        $ee->emit('foo');
        $this->assertFalse($result);

    }

    function testRemoveAllListeners() {

        $result = false;
        $callBack = function() use (&$result) {

            $result = true;

        };

        $ee = new EventEmitter();
        $ee->on('foo', $callBack);

        $ee->emit('foo');
        $this->assertTrue($result);
        $result = false;

        $ee->removeAllListeners('foo');

        $ee->emit('foo');
        $this->assertFalse($result);

    }

    function testRemoveAllListenersNoArg() {

        $result = false;

        $callBack = function() use (&$result) {

            $result = true;

        };


        $ee = new EventEmitter();
        $ee->on('foo', $callBack);

        $ee->emit('foo');
        $this->assertTrue($result);
        $result = false;

        $ee->removeAllListeners();

        $ee->emit('foo');
        $this->assertFalse($result);

    }

    function testOnce() {

        $result = 0;

        $callBack = function() use (&$result) {

            $result++;

        };

        $ee = new EventEmitter();
        $ee->once('foo', $callBack);

        $ee->emit('foo');
        $ee->emit('foo');

        $this->assertEquals(1, $result);

    }

    /**
     * @depends testCancelEvent
     */
    function testPriorityOnce() {

        $argResult = 0;

        $ee = new EventEmitter();
        $ee->once('foo', function($arg) use (&$argResult) {

            $argResult = 1;
            return false;

        });
        $ee->once('foo', function($arg) use (&$argResult) {

            $argResult = 2;
            return false;

        }, 1);

        $this->assertFalse(
            $ee->emit('foo', ['bar'])
        );

        $this->assertEquals(2, $argResult);

    }

    function testRegisterSameListenerTwice() {

        $argResult = 0;

        $callback = function() use (&$argResult) {
            $argResult++;
        };

        $ee = new EventEmitter();

        $ee->on('foo', $callback);
        $ee->on('foo', $callback);

        $ee->emit('foo');
        $this->assertEquals(2, $argResult);

    }

    function testWildcardListeners() {

        $ee = new EventEmitter();

        $callback1 = function() {};
        $callback2 = function() {};
        $callback3 = function() {};

        $ee->on('foo.*', $callback1);
        $ee->on('foo.bar', $callback2);
        $ee->on('foo.qux', $callback3);

        $this->assertEquals([$callback1, $callback2], $ee->listeners("foo.bar"));

    }

    function testWildcardCalls() {

        $argResult = 0;

        $ee = new EventEmitter();

        $ee->on('foo.*', function() use (&$argResult) {
            $argResult++;
        });

        $ee->on('foo.bar', function() use (&$argResult) {
            $argResult++;
        });

        $ee->emit('foo.bar');
        $ee->emit('foo.bar');
        $ee->emit('foo.qux');

        $this->assertEquals(5, $argResult);

    }

    function testWildcardListenersRespectPriority() {

        $result = [];
        $ee = new EventEmitter();

        $ee->on('foo.*', function() use (&$result) {
            $result[] = 'a';
        }, 30);

        $ee->on('foo.bar', function() use (&$result) {
            $result[] = 'b';
        }, 10);

        $ee->on('foo.bar', function() use (&$result) {
            $result[] = 'c';
        }, 20);

        $ee->emit('foo.bar');
        $this->assertEquals(['b', 'c', 'a'], $result);

    }

    function testGlobalWildcard() {

        $result = false;

        $ee = new EventEmitter();
        $ee->on('*', function() use (&$result) {
            $result = true;
        });

        $ee->emit('foo');

        $this->assertTrue($result);

    }

    function testUseWildcardToRegisterGlobalListener() {

        $fooSpy = 0;
        $barSpy = 0;
        $quxSpy = 0;

        $ee = new EventEmitter();

        $ee->on('*', function() use (&$fooSpy, &$barSpy, &$quxSpy) {
            $fooSpy++;
            $barSpy++;
            $quxSpy++;
        });

        $ee->on('foo', function() use (&$fooSpy) {
            $fooSpy++;
        });

        $ee->on('bar', function() use (&$barSpy) {
            $barSpy++;
        });

        $ee->emit('foo');
        $ee->emit('bar');
        $ee->emit('qux');

        $this->assertEquals(4, $fooSpy);
        $this->assertEquals(4, $barSpy);
        $this->assertEquals(3, $quxSpy);

    }

    function testRegisterSameListenerForMultipleEvents() {

        $argResult = 0;
        $ee = new EventEmitter();

        $ee->on(['foo', 'bar'], function() use (&$argResult) {
            $argResult++;
        });

        $ee->emit('foo');
        $ee->emit('bar');
        $ee->emit('qux');

        $this->assertEquals(2, $argResult);

    }

    function testUnregisterMultipleEvents() {

        $argResult = 0;

        $callback = function() use (&$argResult) {
            $argResult++;
        };

        $ee = new EventEmitter();

        $ee->on(['foo', 'bar', 'qux'], $callback);

        $ee->removeListener(['foo', 'bar'], $callback);

        $ee->emit('foo');
        $ee->emit('bar');
        $ee->emit('qux');

        $this->assertEquals(1, $argResult);

    }

    function testUnregisterAllListenersForMultipleEvents() {

        $a = 0;
        $b = 0;

        $ee = new EventEmitter();

        $ee->on(['foo', 'bar', 'qux'], function() use (&$a) {
            $a++;
        });

        $ee->on(['bar', 'qux'], function() use (&$b) {
            $b++;
        });

        $ee->removeAllListeners(['foo', 'bar']);

        $ee->emit('foo');
        $ee->emit('bar');
        $ee->emit('qux');

        $this->assertEquals(1, $a);
        $this->assertEquals(1, $b);

    }

}

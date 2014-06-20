<?php

namespace Sabre\Event;

class EventEmitterTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $ee = new EventEmitter();
        $this->assertInstanceOf('Sabre\\Event\\EventEmitter', $ee);

    }

    public function testListeners() {

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
        $this->assertEquals(['b','d','a','c'], $result);

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

        $ee->removeListener('foo', $callBack);

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

    function testContinueCallBack() {

        $ee = new EventEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo',[],function() use (&$continueCounter) {
            $continueCounter++;
            return true;
        });
        $this->assertTrue($r);
        $this->assertEquals(3, $handlerCounter);
        $this->assertEquals(2, $continueCounter);

    }

    function testContinueCallBackBreak() {

        $ee = new EventEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo',[],function() use (&$continueCounter) {
            $continueCounter++;
            return false;
        });
        $this->assertTrue($r);
        $this->assertEquals(1, $handlerCounter);
        $this->assertEquals(1, $continueCounter);

    }

    function testContinueCallBackBreakByHandler() {

        $ee = new EventEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
            return false;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo',[],function() use (&$continueCounter) {
            $continueCounter++;
            return false;
        });
        $this->assertFalse($r);
        $this->assertEquals(1, $handlerCounter);
        $this->assertEquals(0, $continueCounter);

    }
}

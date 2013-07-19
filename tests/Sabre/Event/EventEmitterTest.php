<?php

namespace Sabre\Event;

class EventEmitterTest extends \PHPUnit_Framework_TestCase {

    function testInit() {

        $ee = new EventEmitter();
        $this->assertInstanceOf('Sabre\\Event\\EventEmitter', $ee);

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

}

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
}

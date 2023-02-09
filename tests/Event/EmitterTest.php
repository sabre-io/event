<?php

declare(strict_types=1);

namespace Sabre\Event;

class EmitterTest extends \PHPUnit\Framework\TestCase
{
    public function testInit(): void
    {
        $ee = new Emitter();
        self::assertInstanceOf('Sabre\\Event\\Emitter', $ee);
    }

    public function testListeners(): void
    {
        $ee = new Emitter();

        $callback1 = function () { };
        $callback2 = function () { };
        $ee->on('foo', $callback1, 200);
        $ee->on('foo', $callback2, 100);

        self::assertEquals([$callback2, $callback1], $ee->listeners('foo'));
    }

    /**
     * @depends testInit
     */
    public function testHandleEvent(): void
    {
        $argResult = null;

        $ee = new Emitter();
        $ee->on('foo', function ($arg) use (&$argResult) {
            $argResult = $arg;
        });

        self::assertTrue(
            $ee->emit('foo', ['bar'])
        );

        self::assertEquals('bar', $argResult);
    }

    /**
     * @depends testHandleEvent
     */
    public function testCancelEvent(): void
    {
        $argResult = 0;

        $ee = new Emitter();
        $ee->on('foo', function ($arg) use (&$argResult) {
            $argResult = 1;

            return false;
        });
        $ee->on('foo', function ($arg) use (&$argResult) {
            $argResult = 2;
        });

        self::assertFalse(
            $ee->emit('foo', ['bar'])
        );

        self::assertEquals(1, $argResult);
    }

    /**
     * @depends testCancelEvent
     */
    public function testPriority(): void
    {
        $argResult = 0;

        $ee = new Emitter();
        $ee->on('foo', function ($arg) use (&$argResult) {
            $argResult = 1;

            return false;
        });
        $ee->on('foo', function ($arg) use (&$argResult) {
            $argResult = 2;

            return false;
        }, 1);

        self::assertFalse(
            $ee->emit('foo', ['bar'])
        );

        self::assertEquals(2, $argResult);
    }

    /**
     * @depends testPriority
     */
    public function testPriority2(): void
    {
        $result = [];
        $ee = new Emitter();

        $ee->on('foo', function () use (&$result) {
            $result[] = 'a';
        }, 200);
        $ee->on('foo', function () use (&$result) {
            $result[] = 'b';
        }, 50);
        $ee->on('foo', function () use (&$result) {
            $result[] = 'c';
        }, 300);
        $ee->on('foo', function () use (&$result) {
            $result[] = 'd';
        });

        $ee->emit('foo');
        self::assertEquals(['b', 'd', 'a', 'c'], $result);
    }

    public function testRemoveListener(): void
    {
        $result = false;

        $callBack = function () use (&$result) {
            $result = true;
        };

        $ee = new Emitter();

        $ee->on('foo', $callBack);

        $ee->emit('foo');
        self::assertTrue($result);
        $result = false;

        self::assertTrue(
            $ee->removeListener('foo', $callBack)
        );

        $ee->emit('foo');
        self::assertFalse($result);
    }

    public function testRemoveUnknownListener(): void
    {
        $result = false;

        $callBack = function () use (&$result) {
            $result = true;
        };

        $ee = new Emitter();

        $ee->on('foo', $callBack);

        $ee->emit('foo');
        self::assertTrue($result);
        $result = false;

        self::assertFalse($ee->removeListener('bar', $callBack));

        $ee->emit('foo');
        self::assertTrue($result);
    }

    public function testRemoveListenerTwice(): void
    {
        $result = false;

        $callBack = function () use (&$result) {
            $result = true;
        };

        $ee = new Emitter();

        $ee->on('foo', $callBack);

        $ee->emit('foo');
        self::assertTrue($result);
        $result = false;

        self::assertTrue(
            $ee->removeListener('foo', $callBack)
        );
        self::assertFalse(
            $ee->removeListener('foo', $callBack)
        );

        $ee->emit('foo');
        self::assertFalse($result);
    }

    public function testRemoveAllListeners(): void
    {
        $result = false;
        $callBack = function () use (&$result) {
            $result = true;
        };

        $ee = new Emitter();
        $ee->on('foo', $callBack);

        $ee->emit('foo');
        self::assertTrue($result);
        $result = false;

        $ee->removeAllListeners('foo');

        $ee->emit('foo');
        self::assertFalse($result);
    }

    public function testRemoveAllListenersNoArg(): void
    {
        $result = false;

        $callBack = function () use (&$result) {
            $result = true;
        };

        $ee = new Emitter();
        $ee->on('foo', $callBack);

        $ee->emit('foo');
        self::assertTrue($result);
        $result = false;

        $ee->removeAllListeners();

        $ee->emit('foo');
        self::assertFalse($result);
    }

    public function testOnce(): void
    {
        $result = 0;

        $callBack = function () use (&$result) {
            ++$result;
        };

        $ee = new Emitter();
        $ee->once('foo', $callBack);

        $ee->emit('foo');
        $ee->emit('foo');

        self::assertEquals(1, $result);
    }

    /**
     * @depends testCancelEvent
     */
    public function testPriorityOnce(): void
    {
        $argResult = 0;

        $ee = new Emitter();
        $ee->once('foo', function ($arg) use (&$argResult) {
            $argResult = 1;

            return false;
        });
        $ee->once('foo', function ($arg) use (&$argResult) {
            $argResult = 2;

            return false;
        }, 1);

        self::assertFalse(
            $ee->emit('foo', ['bar'])
        );

        self::assertEquals(2, $argResult);
    }
}

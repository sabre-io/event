<?php declare (strict_types=1);

namespace Sabre\Event;

class WildcardEmitterTest extends \PHPUnit\Framework\TestCase {

    function testInit() {

        $ee = new WildcardEmitter();
        $this->assertInstanceOf('Sabre\\Event\\WildcardEmitter', $ee);

    }

    function testListeners() {

        $ee = new WildcardEmitter();

        $callback1 = function() { };
        $callback2 = function() { };
        $ee->on('foo', $callback1, 200);
        $ee->on('foo', $callback2, 100);

        $this->assertEquals([$callback2, $callback1], $ee->listeners('foo'));

    }

    function testWildcardListeners() {

        $ee = new WildcardEmitter();

        $callback1 = function() { };
        $callback2 = function() { };
        $ee->on('foo:*', $callback1, 200);
        $ee->on('foo:bar', $callback2, 100);

        $this->assertEquals([$callback2, $callback1], $ee->listeners('foo:bar'));
        $this->assertEquals([$callback1], $ee->listeners('foo:baz'));

    }

    /**
     * @depends testInit
     */
    function testHandleEvent() {

        $argResult = null;

        $ee = new WildcardEmitter();
        $ee->on('foo:*', function($arg) use (&$argResult) {

            $argResult = $arg;

        });

        $this->assertTrue(
            $ee->emit('foo:BAR', ['bar'])
        );

        $this->assertEquals('bar', $argResult);

    }

    /**
     * @depends testHandleEvent
     */
    function testCancelEvent() {

        $argResult = 0;

        $ee = new WildcardEmitter();
        $ee->on('foo:BAR', function($arg) use (&$argResult) {

            $argResult = 1;
            return false;

        }, 10);
        $ee->on('foo:*', function($arg) use (&$argResult) {

            $argResult = 2;

        }, 20);

        $this->assertFalse(
            $ee->emit('foo:BAR', ['bar'])
        );

        $this->assertEquals(1, $argResult);

        $argResult = 0;
        $this->assertTrue(
            $ee->emit('foo:NOTBAR', ['bar'])
        );
        $this->assertEquals(2, $argResult);

    }

    /**
     * @depends testCancelEvent
     */
    function testPriority() {

        $argResult = 0;

        $ee = new WildcardEmitter();
        $ee->on('foo:bar:*', function($arg) use (&$argResult) {

            $argResult = 1;
            return false;

        });
        $ee->on('foo:bar:baz', function($arg) use (&$argResult) {

            $argResult = 2;
            return false;

        }, 1);

        $this->assertFalse(
            $ee->emit('foo:bar:baz', ['bar'])
        );

        $this->assertEquals(2, $argResult);

    }

    /**
     * @depends testPriority
     */
    function testPriority2() {

        $result = [];
        $ee = new WildcardEmitter();

        $ee->on('foo:bar:baz', function() use (&$result) {

            $result[] = 'a';

        }, 200);
        $ee->on('foo:bar:*', function() use (&$result) {

            $result[] = 'b';

        }, 50);
        $ee->on('foo:bar:baz', function() use (&$result) {

            $result[] = 'c';

        }, 300);
        $ee->on('foo:bar:*', function() use (&$result) {

            $result[] = 'd';

        });

        $ee->emit('foo:bar:baz');
        $this->assertEquals(['b', 'd', 'a', 'c'], $result);

    }

    function testRemoveListener() {

        $result = false;

        $callBack = function() use (&$result) {

            $result = true;

        };

        $ee = new WildcardEmitter();

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

        $ee = new WildcardEmitter();

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

        $ee = new WildcardEmitter();

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

        $ee = new WildcardEmitter();
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


        $ee = new WildcardEmitter();
        $ee->on('foo', $callBack);

        $ee->emit('foo');
        $this->assertTrue($result);
        $result = false;

        $ee->removeAllListeners();

        $ee->emit('foo');
        $this->assertFalse($result);

    }

    function testRemoveAllListenersWildcard() {

        $result = false;
        $callBack = function() use (&$result) {

            $result = true;

        };

        $ee = new WildcardEmitter();
        $ee->on('foo:*', $callBack);

        $ee->emit('foo:bar');
        $this->assertTrue($result);
        $result = false;

        $ee->removeAllListeners('foo:*');

        $ee->emit('foo:bar');
        $this->assertFalse($result);

    }

    function testOnce() {

        $result = 0;

        $callBack = function() use (&$result) {

            $result++;

        };

        $ee = new WildcardEmitter();
        $ee->once('foo:*', $callBack);

        $ee->emit('foo:bar');
        $ee->emit('foo:baz');

        $this->assertEquals(1, $result);

    }

    /**
     * @depends testCancelEvent
     */
    function testPriorityOnce() {

        $argResult = 0;

        $ee = new WildcardEmitter();
        $ee->once('foo:*', function($arg) use (&$argResult) {

            $argResult = 1;
            return false;

        });
        $ee->once('foo:bar', function($arg) use (&$argResult) {

            $argResult = 2;
            return false;

        }, 1);

        $this->assertFalse(
            $ee->emit('foo:bar', ['bar'])
        );

        $this->assertEquals(2, $argResult);

    }

    function testContinueCallBack() {

        $ee = new WildcardEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function() use (&$continueCounter) {
            $continueCounter++;
            return true;
        });
        $this->assertTrue($r);
        $this->assertEquals(3, $handlerCounter);
        $this->assertEquals(2, $continueCounter);

    }

    function testContinueCallBackBreak() {

        $ee = new WildcardEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function() use (&$continueCounter) {
            $continueCounter++;
            return false;
        });
        $this->assertTrue($r);
        $this->assertEquals(1, $handlerCounter);
        $this->assertEquals(1, $continueCounter);

    }

    function testContinueCallBackBreakByHandler() {

        $ee = new WildcardEmitter();

        $handlerCounter = 0;
        $bla = function() use (&$handlerCounter) {
            $handlerCounter++;
            return false;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function() use (&$continueCounter) {
            $continueCounter++;
            return false;
        });
        $this->assertFalse($r);
        $this->assertEquals(1, $handlerCounter);
        $this->assertEquals(0, $continueCounter);

    }

}

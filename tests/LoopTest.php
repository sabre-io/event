<?php

namespace Sabre\Event;

class LoopTest extends \PHPUnit_Framework_TestCase {

    function testNextTick() {

        $loop = new Loop();
        $check  = 0;
        $loop->nextTick(function() use (&$check) {

            $check++;

        });

        $loop->run();

        $this->assertEquals(1, $check);

    }

    function testTimeout() {

        $loop = new Loop();
        $check  = 0;
        $loop->setTimeout(function() use (&$check) {

            $check++;

        }, 20);

        $loop->run();

        $this->assertEquals(1, $check);

    }

    function testTimeoutOrder() {

        $loop = new Loop();
        $check  = [];
        $loop->setTimeout(function() use (&$check) {

            $check[] = 'a';

        }, 2000);
        $loop->setTimeout(function() use (&$check) {

            $check[] = 'b';

        }, 1000);

        $loop->run();

        $this->assertEquals(['b', 'a'], $check);

    }

    function testSetInterval() {

        $loop = new Loop();
        $check = 0;
        $intervalId = null;
        $intervalId = $loop->setInterval(function() use (&$check, &$intervalId, $loop) {

            $check++;
            if ($check > 5) {
                $loop->clearInterval($intervalId);
            }

        }, 20);

        $loop->run();

        $this->assertEquals(6, $check);

    }

}

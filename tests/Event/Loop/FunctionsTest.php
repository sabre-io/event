<?php

declare(strict_types=1);

namespace Sabre\Event\Loop;

class FunctionsTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        // Always creating a fresh loop object.
        instance(new Loop());
    }

    public function tearDown(): void
    {
        // Removing the global loop object.
        instance(null);
    }

    public function testNextTick(): void
    {
        $check = 0;
        nextTick(function () use (&$check) {
            ++$check;
        });

        run();

        $this->assertEquals(1, $check);
    }

    public function testTimeout(): void
    {
        $check = 0;
        setTimeout(function () use (&$check) {
            ++$check;
        }, 0.02);

        run();

        $this->assertEquals(1, $check);
    }

    public function testTimeoutOrder(): void
    {
        $check = [];
        setTimeout(function () use (&$check) {
            $check[] = 'a';
        }, 0.2);
        setTimeout(function () use (&$check) {
            $check[] = 'b';
        }, 0.1);
        setTimeout(function () use (&$check) {
            $check[] = 'c';
        }, 0.3);

        run();

        $this->assertEquals(['b', 'a', 'c'], $check);
    }

    public function testSetInterval(): void
    {
        $check = 0;
        $intervalId = null;
        $intervalId = setInterval(function () use (&$check, &$intervalId) {
            ++$check;
            if ($check > 5) {
                clearInterval($intervalId);
            }
        }, 0.02);

        run();
        $this->assertEquals(6, $check);
    }

    public function testAddWriteStream(): void
    {
        $h = fopen('php://temp', 'r+');
        addWriteStream($h, function () use ($h) {
            fwrite($h, 'hello world');
            removeWriteStream($h);
        });
        run();
        rewind($h);
        $this->assertEquals('hello world', stream_get_contents($h));
    }

    public function testAddReadStream(): void
    {
        $h = fopen('php://temp', 'r+');
        fwrite($h, 'hello world');
        rewind($h);

        $result = null;

        addReadStream($h, function () use ($h, &$result) {
            $result = fgets($h);
            removeReadStream($h);
        });
        run();
        $this->assertEquals('hello world', $result);
    }

    public function testStop(): void
    {
        $check = 0;
        setTimeout(function () use (&$check) {
            ++$check;
        }, 200);

        nextTick(function () {
            stop();
        });
        run();

        $this->assertEquals(0, $check);
    }

    public function testTick(): void
    {
        $check = 0;
        setTimeout(function () use (&$check) {
            ++$check;
        }, 1);

        nextTick(function () use (&$check) {
            ++$check;
        });
        tick();

        $this->assertEquals(1, $check);
    }
}

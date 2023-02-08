<?php

declare(strict_types=1);

namespace Sabre\Event;

class ContinueCallbackTest extends \PHPUnit\Framework\TestCase
{
    public function testContinueCallBack(): void
    {
        $ee = new Emitter();

        $handlerCounter = 0;
        $bla = function () use (&$handlerCounter) {
            ++$handlerCounter;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function () use (&$continueCounter) {
            ++$continueCounter;

            return true;
        });
        self::assertTrue($r);
        self::assertEquals(3, $handlerCounter);
        self::assertEquals(2, $continueCounter);
    }

    public function testContinueCallBackBreak(): void
    {
        $ee = new Emitter();

        $handlerCounter = 0;
        $bla = function () use (&$handlerCounter) {
            ++$handlerCounter;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function () use (&$continueCounter) {
            ++$continueCounter;

            return false;
        });
        self::assertTrue($r);
        self::assertEquals(1, $handlerCounter);
        self::assertEquals(1, $continueCounter);
    }

    public function testContinueCallBackBreakByHandler(): void
    {
        $ee = new Emitter();

        $handlerCounter = 0;
        $bla = function () use (&$handlerCounter) {
            ++$handlerCounter;

            return false;
        };
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);
        $ee->on('foo', $bla);

        $continueCounter = 0;
        $r = $ee->emit('foo', [], function () use (&$continueCounter) {
            ++$continueCounter;

            return false;
        });
        self::assertFalse($r);
        self::assertEquals(1, $handlerCounter);
        self::assertEquals(0, $continueCounter);
    }
}

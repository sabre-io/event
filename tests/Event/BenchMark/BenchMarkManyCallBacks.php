<?php

declare(strict_types=1);

namespace Sabre\Event\BenchMark;

use Sabre\Event\Emitter;

include __DIR__.'/../../../vendor/autoload.php';

class BenchMarkManyCallBacks extends BenchMark
{
    protected $emitter;

    public function setUp(): void
    {
        $this->emitter = new Emitter();
        for ($i = 0; $i < 100; ++$i) {
            $this->emitter->on('foo', function () {
                // NOOP
            });
        }
    }

    public function test()
    {
        for ($i = 0; $i < $this->iterations; ++$i) {
            $this->emitter->emit('foo', []);
        }
    }
}

<?php

declare(strict_types=1);

namespace Sabre\Event\BenchMark;

use Sabre\Event\WildcardEmitter;

include __DIR__.'/../../../vendor/autoload.php';

class BenchWildcardManyCallBacks extends BenchWildcard
{
    protected $emitter;

    public function setUp(): void
    {
        $this->emitter = new WildcardEmitter();
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

<?php

declare(strict_types=1);

namespace Sabre\Event\BenchMark;

use Sabre\Event\WildcardEmitter;

include __DIR__.'/../../../vendor/autoload.php';

class BenchWildcardOneCallBack extends BenchWildcard
{
    protected WildcardEmitter $emitter;
    protected int $iterations = 100000;

    public function setUp(): void
    {
        $this->emitter = new WildcardEmitter();
        $this->emitter->on('foo', function () {
            // NOOP
        });
    }

    public function test(): void
    {
        for ($i = 0; $i < $this->iterations; ++$i) {
            $this->emitter->emit('foo', []);
        }
    }
}

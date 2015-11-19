<?php

/**
 * This script is intended to measure the speed of sabre/event.
 *
 * Currently it just benchmarks EventEmitter.
 */

use Hoa\Bench\Bench;
use Sabre\Event\EventEmitter;

require __DIR__ . '/../vendor/autoload.php';

/**
 * This test is what we consider a 'typical' use-case for sabre/event, just
 * exaggerated quite a bit.
 *
 * Generally the assumption is that many listeners are created during a set-up
 * phase.
 *
 * Then during the execution phase some of those events will be emitted.
 * Sometimes many over.
 *
 * During the execution phase very few changes are made to listeners.
 */

// Arbitrary number. Increase this to change the memory/cpu footprint of this
// test.
$testSize = 10000;

$em = new EventEmitter();

$bench = new Bench();
$bench->setup->start();

for($i = 0; $i < $testSize; $i++) {

    if ($i % 10 === 0) {
        // One out of 10 uses a different priority. 
        $em->on('event' . $i, function() { }, 90);
    } elseif ($i % 10 === 1) {
        // One out of 10 events have a lot of subscribers.
        for($j = 0; $j < 10; $j++) {
            $em->on('event' . $i, function() { });
        }
    } else {
        // 8 out of 10 events has just one subscriber.
        $em->on('event' . $i, function() { });
    }

}

$bench->setup->stop();

$bench->execution->start();

$emitCount = round($testSize / 10);
// We're only executing 10% of the defined events.
for($i = 0; $i < $emitCount; $i++) {

    if ($i % 8 === 0) {
        // 1 out of 8 events get emitted 2000 times. 
        for($j = 0; $j < 2000; $j++) {
            $em->emit('event' . $i, ['arg1', 'arg2']);
        }
    } elseif ($i % 8 === 1) {
        // Emit event
        $em->emit('event' . $i, ['arg1', 'arg2']);
        // Add a new listener.
        $handler = function() { };
        $em->on('event' . $i, $handler);
        // Emit again
        $em->emit('event' . $i, ['arg1', 'arg2']);
        // Remove listener again
        $em->removeListener('event' . $i, $handler);       
    } else {
        // 6 out of 8 events only get emitted once.
        $em->emit('event' . $i, ['arg1', 'arg2']);
    }
}

$bench->execution->stop();

echo $bench;

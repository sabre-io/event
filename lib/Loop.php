<?php

namespace Sabre\Event;

class Loop {

    protected $timers = []; 
    protected $nextTick = [];

    protected $readStreams = [];
    protected $writeStreams = [];
    protected $readCallbacks = [];
    protected $writeCallbacks = [];

    function setTimeout(callable $cb, $ms) {

        $time = microtime(true) + ($ms / 1000);

        if (!$this->timers) {
            // Special case when the timers array was empty.
            $this->timers[] = [$time, $cb];
            return;
        }

        // We need to insert these values in the timers array, but the timers
        // array must be in reverse-order of trigger times.
        //
        // So here we search the array for the insertion point.
        $index = count($this->timers)-1;
        while(true) { 
            if ($time < $this->timers[$index][0]) {
                array_splice(
                    $this->timers,
                    $index+1,
                    0,
                    [[$time, $cb]]
                );
                break;
            } elseif ($index===0) {
                array_unshift($this->timers, [$time, $cb]);
                break;
            } 
            $index--;

        }

    }

    function setInterval(callable $cb, $ms) {

        $keepGoing = true;
        $f = null;

        $f = function() use ($cb, &$f, $ms, &$keepGoing) {
            if ($keepGoing) {
                $cb();
                $this->setTimeout($f, $ms);
            }
        };
        $this->setTimeout($f, $ms);

        return [&$keepGoing];

    }

    function clearInterval($intervalId) {

        $intervalId = false;

    }

    function nextTick(callable $cb) {

        $this->nextTick[] = $cb;

    }

    function runTimers() {

        $now = microtime(true);
        while(($timer = array_pop($this->timers)) && $timer[0] < $now) {
            $timer[1]();
        }
        // Add the last timer back to the array.
        if ($timer) {
            $this->timers[] = $timer;
            return $timer[0]-microtime(true);
        }

    }

    function addReadStream($stream, callable $cb) {

        $this->readStreams[(int)$stream] = $stream;
        $this->readCallbacks[(int)$stream] = $cb;

    }

    function addWriteStream($stream, callable $cb) {

        $this->writeStreams[(int)$stream] = $stream;
        $this->writeCallbacks[(int)$stream] = $cb;

    }

    function run() {

        while(true) {

            $nextTick = $this->nextTick;
            $this->nextTick = [];

            foreach($nextTick as $cb) {
                $cb();
            }

            $nextTimeout = $this->runTimers();
            $pollTimeout = $this->nextTick ? 0 : $nextTimeout;

            if ($this->readStreams || $this->writeStreams) {

                $read = $this->readStreams;
                $write = $this->writeStreams;
                $except = null;
                if(stream_select($read, $write, $except, 0, $pollTimeout)) {
                    foreach($read as $readStream) {
                        $this->readCallbacks[(int)$readStream]();
                    }
                    foreach($write as $writeStream) {
                        $this->writeCallbacks[(int)$writeStream]();
                    }

                }

            } elseif ($this->nextTick || $this->timers) {
                usleep($pollTimeout !== null ? $pollTimeout : 200000); 
            } else {
                break;
            }

        }

    }

}

<?php

namespace Sabre\Event\Loop;

/**
 * Executes a function after x seconds.
 *
 * @param callable $cb
 * @param float $timeout timeout in seconds
 * @return void
 */
function setTimeout(callable $cb, $timeout) {

    Loop::getInstance()->setTimeout($cb, $timeout);

}

/**
 * Executes a function every x seconds.
 *
 * The value this function returns can be used to stop the interval with
 * clearInterval.
 *
 * @param callable $cb
 * @param float $timeout
 * @return array
 */
function setInterval(callable $cb, $timeout) {

    return Loop::getInstance()->setInterval($cb, $timeout);

}

/**
 * Stops a running internval.
 *
 * @param array $intervalId
 * @return void
 */
function clearInterval($intervalId) {

    Loop::getInstance()->setInterval($intervalId);

}

/**
 * Runs a function immediately at the next iteration of the loop.
 *
 * @param callable $cb
 * @return void
 */
function nextTick(callable $cb) {

    Loop::getInstance()->nextTick($cb);

}


/**
 * Adds a read stream.
 *
 * The callback will be called as soon as there is something to read from
 * the stream.
 *
 * You MUST call removeReadStream after you are done with the stream, to
 * prevent the eventloop from never stopping.
 *
 * @param resource $stream
 * @param callable $cb
 * @return void
 */
function addReadStream($stream, callable $cb) {

    Loop::getInstance()->addReadStream($stream, $cb);

}

/**
 * Adds a write stream.
 *
 * The callback will be called as soon as the system reports it's ready to
 * receive writes on the stream.
 *
 * You MUST call removeWriteStream after you are done with the stream, to
 * prevent the eventloop from never stopping.
 *
 * @param resource $stream
 * @param callable $cb
 * @return void
 */
function addWriteStream($stream, callable $cb) {

    Loop::getInstance()->addWriteStream($stream, $cb);

}

/**
 * Stop watching a stream for reads.
 *
 * @param resource $stream
 * @return void
 */
function removeReadStream($stream) {

    Loop::getInstance()->removeReadStream($stream, $cb);

}

/**
 * Stop watching a stream for writes.
 *
 * @param resource $stream
 * @return void
 */
function removeWriteStream($stream) {

    Loop::getInstance()->removeWriteStream($stream, $cb);

}


/**
 * Runs the loop.
 *
 * This function will run continiously, until there's no more events to
 * handle.
 *
 * @return void
 */
function run() {

    Loop::getInstance()->run();

}

/**
 * Executes all pending events, and immediately exists if there were no
 * pending events.
 *
 * @return void
 */
function runOnce() {

    Loop::getInstance()->runOnce();

}

/**
 * Stops a running eventloop
 *
 * @return void
 */
function stop() {

    Loop::getInstance()->stop();

}

<?php

namespace Sabre\Event;

use Generator;
use Exception;

/**
 * Turn asynchronous promise-based code into something that looks synchronous
 * again, through the use of generators.
 *
 * Example without flow:
 *
 * $promise = $httpClient->request('GET', '/foo');
 * $promise->then(function($value) {
 *
 *   return $httpClient->request('DELETE','/foo');
 *
 * })-then(function($value) {
 *
 *   return $httpClient->request('PUT', '/foo');
 *
 * })->error(function($reason) {
 *
 *   echo "Failed because: $reason\n";
 *
 * });
 *
 * Example with flow:
 *
 * flow(function() {
 *
 *   try {
 *     yield $httpClient->request('GET', '/foo');
 *     yield $httpClient->request('DELETE', /foo');
 *     yield $httpClient->request('PUT', '/foo');
 *   } catch(\Exception $reason) {
 *     echo "Failed because: $reason\n";
 *   }
 *
 * });
 *
 * @copyright Copyright (C) 2013-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
function flow(callable $gen) {

    $generator = $gen();
    if (!$generator instanceof Generator) {
        throw new \InvalidArgumentException('You must pass a generator function');
    }

    // This is the value we're returning.
    $promise = new Promise();

    /**
     * So tempted to use the mythical y-combinator here, but it's not needed in
     * PHP.
     */
    $advanceGenerator = function() use (&$advanceGenerator, $generator, $promise) {

        if (!$generator->valid()) {
            $promise->fulfill();
            return;
        }

        do {

            $recurImmediately = false;

            $yieldedValue = $generator->current();

            if ($yieldedValue instanceof Promise) {
                $yieldedValue->then(
                    function($value) use ($generator, $advanceGenerator) {
                        $generator->send($value);
                        $advanceGenerator();
                    },
                    function($reason) use ($generator, $advanceGenerator) {
                        if ($reason instanceof Exception) {
                            $generator->throw($reason);
                        } elseif (is_scalar($reason)) {
                            $generator->throw(new Exception($reason));
                        } else {
                            $type = is_object($reason)?get_class($reason):gettype($reason);
                            $generator->throw(new Exception('Promise was rejected with reason of type: ' . $type));
                        }
                        $advanceGenerator();
                    }
                )->error(function($reason) use ($promise) {
                    // This error handler would be called, if something in the
                    // generator throws an exception, and it's not caught
                    // locally.
                    $promise->reject($reason);
                });
                // We need to break out of the loop, because $advanceGenerator
                // will be called asynchronously when the promise has a result.
                break;
            } else {
                // If the value was not a promise, we'll just let it pass through.
                $generator->send($yieldedValue);
            }

        } while ($generator->valid());


    };

    try {
        $advanceGenerator();
    } catch(Exception $e) {
        $promise->reject($e);
    }

    return $promise;

}

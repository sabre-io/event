<?php declare (strict_types=1);

namespace Sabre\Event;

use Exception;
use Generator;

/**
 * Turn asynchronous promise-based code into something that looks synchronous
 * again, through the use of generators.
 *
 * Example without coroutines:
 *
 * $promise = $httpClient->request('GET', '/foo');
 * $promise->then(function($value) {
 *
 *   return $httpClient->request('DELETE','/foo');
 *
 * })->then(function($value) {
 *
 *   return $httpClient->request('PUT', '/foo');
 *
 * })->error(function($reason) {
 *
 *   echo "Failed because: $reason\n";
 *
 * });
 *
 * Example with coroutines:
 *
 * coroutine(function() {
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
 * @return Sabre\Event\Promise
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
function coroutine(callable $gen) : Promise {

    $generator = $gen();
    if (!$generator instanceof Generator) {
        throw new \InvalidArgumentException('You must pass a generator function');
    }

    // This is the value we're returning.
    $promise = new Promise();

    $lastYieldResult = null;

    /**
     * So tempted to use the mythical y-combinator here, but it's not needed in
     * PHP.
     */
    $advanceGenerator = function() use (&$advanceGenerator, $generator, $promise, &$lastYieldResult) {

        while ($generator->valid()) {

            $yieldedValue = $generator->current();
            if ($yieldedValue instanceof Promise) {
                $yieldedValue->then(
                    function($value) use ($generator, &$advanceGenerator, &$lastYieldResult) {
                        $lastYieldResult = $value;
                        $generator->send($value);
                        $advanceGenerator();
                    },
                    function($reason) use ($generator, $advanceGenerator) {
                        if ($reason instanceof Exception) {
                            $generator->throw($reason);
                        } elseif (is_scalar($reason)) {
                            $generator->throw(new Exception((string)$reason));
                        } else {
                            $type = is_object($reason) ? get_class($reason) : gettype($reason);
                            $generator->throw(new Exception('Promise was rejected with reason of type: ' . $type));
                        }
                        $advanceGenerator();
                    }
                )->otherwise(function($reason) use ($promise) {
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
                $lastYieldResult = $yieldedValue;
                $generator->send($yieldedValue);
            }

        }

        // If the generator is at the end, and we didn't run into an exception,
        // we can fullfill the promise with the last thing that was yielded to
        // us.
        if (!$generator->valid() && $promise->state === Promise::PENDING) {
            $promise->fulfill($lastYieldResult);
        }

    };

    try {
        $advanceGenerator();
    } catch (Exception $e) {
        $promise->reject($e);
    }

    return $promise;

}

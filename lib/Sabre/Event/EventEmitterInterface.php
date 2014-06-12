<?php

namespace Sabre\Event;

/**
 * Event Emitter Interface
 *
 * Anything that accepts listeners and emits events should implement this
 * interface.
 *
 * @copyright Copyright (C) 2013-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/
 */
interface EventEmitterInterface {

    /**
     * Subscribe to an event.
     *
     * @param string $eventName
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    public function on($eventName, callable $callBack, $priority = 100);

    /**
     * Subscribe to an event exactly once.
     *
     * @param string $eventName
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    public function once($eventName, callable $callBack, $priority = 100);

    /**
     * Emits an event.
     *
     * This method will return true if 0 or more listeners were succesfully
     * handled. false is returned if one of the events broke the event chain.
     *
     * @param string $eventName
     * @param array $arguments
     * @return bool
     */
    public function emit($eventName, array $arguments = []);


    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array. Every item is another array with 2
     * elements: priority and the callback.
     *
     * The array is returned by reference, and can therefore be used to
     * manipulate the list of events.
     *
     * @param string $eventName
     * @return array
     */
    public function & listeners($eventName);

    /**
     * Removes a specific listener from an event.
     *
     * @param string $eventName
     * @param callable $listener
     * @return void
     */
    public function removeListener($eventName, callable $listener);

    /**
     * Removes all listeners from the specified event.
     *
     * @param string $eventName
     * @return void
     */
    public function removeAllListeners($eventName);
}

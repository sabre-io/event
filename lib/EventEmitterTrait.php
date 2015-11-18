<?php

namespace Sabre\Event;

/**
 * Event Emitter Trait
 *
 * This trait contains all the basic functions to implement an
 * EventEmitterInterface.
 *
 * Using the trait + interface allows you to add EventEmitter capabilities
 * without having to change your base-class.
 *
 * @copyright Copyright (C) 2013-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait EventEmitterTrait {

    /**
     * The list of listeners
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * Subscribe to an event.
     *
     * @param string|string[] $eventNames
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    function on($eventNames, callable $callBack, $priority = 100) {

        if (!is_array($eventNames)) {
            $eventNames = [$eventNames];
        }

        foreach ($eventNames as $eventName) {
            if (!isset($this->listeners[$eventName])) {
                $this->listeners[$eventName] = [
                    true,  // If there's only one item, it's sorted
                    [$priority],
                    [$callBack]
                ];
            } else {
                $this->listeners[$eventName][0] = false; // marked as unsorted
                $this->listeners[$eventName][1][] = $priority;
                $this->listeners[$eventName][2][] = $callBack;
            }
        }

    }

    /**
     * Subscribe to an event exactly once.
     *
     * @param string $eventName
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    function once($eventName, callable $callBack, $priority = 100) {

        $wrapper = null;
        $wrapper = function() use ($eventName, $callBack, &$wrapper) {

            $this->removeListener($eventName, $wrapper);
            return call_user_func_array($callBack, func_get_args());

        };

        $this->on($eventName, $wrapper, $priority);

    }

    /**
     * Emits an event.
     *
     * This method will return true if 0 or more listeners were succesfully
     * handled. false is returned if one of the events broke the event chain.
     *
     * If the continueCallBack is specified, this callback will be called every
     * time before the next event handler is called.
     *
     * If the continueCallback returns false, event propagation stops. This
     * allows you to use the eventEmitter as a means for listeners to implement
     * functionality in your application, and break the event loop as soon as
     * some condition is fulfilled.
     *
     * Note that returning false from an event subscriber breaks propagation
     * and returns false, but if the continue-callback stops propagation, this
     * is still considered a 'successful' operation and returns true.
     *
     * Lastly, if there are 5 event handlers for an event. The continueCallback
     * will be called at most 4 times.
     *
     * @param string $eventName
     * @param array $arguments
     * @param callback $continueCallBack
     * @return bool
     */
    function emit($eventName, array $arguments = [], callable $continueCallBack = null) {

        if (is_null($continueCallBack)) {

            foreach ($this->listeners($eventName) as $listener) {

                $result = call_user_func_array($listener, $arguments);
                if ($result === false) {
                    return false;
                }
            }

        } else {

            $listeners = $this->listeners($eventName);
            $counter = count($listeners);

            foreach ($listeners as $listener) {

                $counter--;
                $result = call_user_func_array($listener, $arguments);
                if ($result === false) {
                    return false;
                }

                if ($counter > 0) {
                    if (!$continueCallBack()) break;
                }

            }

        }

        return true;

    }

    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array, and the list of events are sorted by
     * their priority.
     *
     * If any wildcard listeners match the event, they are also returned. For example,
     * "foo.bar.baz" is matched by "foo.bar.baz", "foo.bar.*", "foo.*" and "*"
     *
     * @param string $eventName
     * @return callable[]
     */
    function listeners($eventName) {

        $priorities = [];
        $callbacks = [];

        $parts = explode(".", $eventName);

        while (true) {
            if (isset($this->listeners[$eventName])) {
                $priorities = array_merge($priorities, $this->listeners[$eventName][1]);
                $callbacks = array_merge($callbacks, $this->listeners[$eventName][2]);
            }

            if (!array_pop($parts)) {
                break;
            }

            if (count($parts)) {
                $eventName = implode('.', $parts) . '.*';
            } else {
                $eventName = '*';
            }
        }

        array_multisort($priorities, SORT_NUMERIC, $callbacks);

        return $callbacks;

    }

    /**
     * Removes a specific listener from an event.
     *
     * If the listener could not be found, this method will return false. If it
     * was removed it will return true.
     *
     * When unregistering multiple events at once, true will be returned if at least
     * one of the events was associated with the listener
     *
     * @param string|string[] $eventNames
     * @param callable $listener
     * @return bool
     */
    function removeListener($eventNames, callable $listener) {

        if (!is_array($eventNames)) {
            $eventNames = [$eventNames];
        }

        $return = false;

        foreach ($eventNames as $eventName) {
            if (!isset($this->listeners[$eventName])) {
                continue;
            }

            foreach ($this->listeners[$eventName][2] as $index => $check) {
                if ($check === $listener) {
                    unset($this->listeners[$eventName][1][$index]);
                    unset($this->listeners[$eventName][2][$index]);

                    $return = true;
                    continue; // continue only this foreach
                }
            }
        }

        return $return;

    }

    /**
     * Removes all listeners.
     *
     * If the eventName argument is specified, all listeners for that event are
     * removed. If it is not specified, every listener for every event is
     * removed.
     *
     * @param string $eventName
     * @return void
     */
    function removeAllListeners($eventName = null) {

        if (!is_null($eventName)) {
            unset($this->listeners[$eventName]);
        } else {
            $this->listeners = [];
        }

    }

}

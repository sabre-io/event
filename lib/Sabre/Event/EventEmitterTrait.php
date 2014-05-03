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
 * @copyright Copyright (C) 2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license https://raw.github.com/fruux/sabre-event/master/LICENSE
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
     * @param string $eventName
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    public function on($eventName, callable $callBack, $priority = 100) {

        $listeners =& $this->listeners($eventName);
        $listeners[] = [$priority, $callBack];
        usort($listeners, function($a, $b) {

            return $a[0]-$b[0];

        });

    }

    /**
     * Subscribe to an event exactly once.
     *
     * @param string $eventName
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    public function once($eventName, callable $callBack, $priority = 100) {

        $wrapper = null;
        $wrapper = function() use ($eventName, $callBack, &$wrapper) {

            $this->removeListener($eventName, $wrapper);
            $result = call_user_func_array($callBack, func_get_args());

        };

        $this->on($eventName, $wrapper);

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
    public function emit($eventName, array $arguments = [], callable $continueCallBack = null) {

        if (is_null($continueCallBack)) {

            foreach($this->listeners($eventName) as $listener) {

                $result = call_user_func_array($listener[1], $arguments);
                if ($result === false) {
                    return false;
                }
            }

        } else {

            $listeners = $this->listeners($eventName);
            $counter = count($listeners);

            foreach($listeners as $listener) {

                $counter--;
                $result = call_user_func_array($listener[1], $arguments);
                if ($result === false) {
                    return false;
                }

                if ($counter>0) {
                    if (!$continueCallBack()) break;
                }

            }

        }

        return true;

    }

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
    public function &listeners($eventName) {

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        return $this->listeners[$eventName];

    }

    /**
     * Removes a specific listener from an event.
     *
     * @param string $eventName
     * @param callable $listener
     * @return void
     */
    public function removeListener($eventName, callable $listener) {

        $listeners =& $this->listeners($eventName);
        foreach($listeners as $index => $check) {
            if ($check[1]===$listener) {
                unset($listeners[$index]);
                break;
            }
        }

    }

    /**
     * Removes all listeners from the specified event.
     *
     * @param string $eventName
     * @return void
     */
    public function removeAllListeners($eventName) {

        $listeners =& $this->listeners($eventName);
        $listeners = [];

    }

}

<?php

namespace Sabre\Event;

/**
 * Event Emitter Trait
 *
 * This traits contains all the basic functions to implement an
 * EventEmitterInterface.
 *
 * Using the trait + interface allows you to add EventEmitter capabilities
 * without having to change your base-class.
 *
 * @copyright Copyright (C) 2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
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

        $listeners = $this->listeners($eventName);
        $listeners[] = [$priorty, $callBack];
        usort($listeners, function($a, $b) {

            return $a[0]-$b[0];

        });

    }

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
    public function emit($eventName, array $arguments = []) {

        foreach($this->listeners($eventName) as $listener) {

            $result = call_user_func_array($listener[1], $arguments);
            if ($result === false) {
                return false;
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

}

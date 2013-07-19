<?php

namespace Sabre\Event;

/**
 * EventEmitter object.
 *
 * Instantiate this class, or subclass it for easily creating event emitters. 
 * 
 * @copyright Copyright (C) 2013 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/) 
 * @license https://raw.github.com/fruux/sabre-event/master/LICENSE
 */
class EventEmitter implements EventEmitterInterface {

    use EventEmitterTrait;

}

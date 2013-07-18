sabre/event
===========

A lightweight library for event management in PHP.

It's design is inspired by Node.js's [EventEmitter][1]. sabre/event requires
PHP 5.4.

It's distinct from [Événement][2], because I needed a couple of features that
were in conflict with it's design goals. Namely: prioritization, and the
ability to stop the event chain, like javascript's `preventDefault`.

Installation
------------

Make sure you have [composer][3] installed. In your project directory, create,
or edit a `composer.json` file, and make sure it contains something like this:


```json
{
    "require" : {
        "sabre/event" : "~0.0.1@alpha"
    }
}
```

After that, just hit `composer install` and you should be rolling.

Usage
-----

In an event system there are emitters, and listeners. Emitters trigger an
event, at which point a listener is notified.

Example:

```php
use Sabre\Event\EventEmitter;

include 'vendor/autoload.php';

$eventEmitter = new EventEmitter();

// subscribing
$eventEmitter->on('create', function() {

    echo "Something got created, apparently\n"

});

$eventEmitter->emit('create');
```

The name of the event (`create`) can be any free-form string.

### Priorities

By supplying a priority, you can make sure that subscribers are handled in a
specific order. The default priority is 100. Anything below that will be
triggered earlier, anything higher later.

If there's two subscribers with the same priority, they will execute in an
undefined, but deterministic order.

```php
$eventEmitter->on('create', function() {

// This event will be handled first.

}, 50);
```

### Callbacks

All default PHP callbacks are supported, so closures are not required.

```php
$eventEmitter->on('create', 'myFunction');
$eventEmitter->on('create', ['myClass', 'myMethod']);
$eventEmitter->on('create', [$myInstance, 'myMethod']);
```

### Canceling the event handler.

If a callback returns `false` the event chain is stopped immidiately.

A usecase is to use a listener to check if a user has permission to perform
a certain action, and stop execution if they don't.

```php
$eventEmitter->on('create', function() {

    if (!checkPermission()) {
        return false;
    }

}, 10);
```

`EventEmitter::emit()` will return `false` if the event was cancelled, and
true if it wasn't.

SabreDAV uses this feature heavily as well. When a HTTP request is received
various plugins see if they are capable of handling the request. If they
do, they can return false so other plugins will not also attempt to handle
the request.

Throwing an exception will also stop the chain.

### Passing arguments

Arguments can be passed as an array.

```php
$eventEmitter->on('create', function($entityId) {

    echo "An entity with id ", $entityId, " just got created.\n";

});

$entityId = 5;
$eventEmitter->emit('create', [$entityId]);
```

Because you cannot really do anything with the return value of a listener,
you can pass arguments by reference to communicate between listeners and
back to the emitter.

```php
$eventEmitter->on('create', function($entityId, &$warnings) {

    echo "An entity with id ", $entityId, " just got created.\n";

    $warnings[] = "Something bad may or may not have happened.\n";

});


$warnings = [];
$eventEmitter->emit('create', [$entityId, &$warnings]);

print_r($warnings);
```


### Integration into other objects.

To add `EventEmitter` capabilities to any class, you can simply extend it.

If you cannot extend, because the class is already part of an existing class
hierarchy you can use the supplied trait.

```php
use Sabre\Event;


class MyNotUneventfulApplication
    extends AppController
    implements Event\EventEmitterInterface
{

    use Event\EventEmitterTrait();

}
```

In the preceeding example, `MyNotUneventfulApplication` has all the
capabilities of `EventEmitter`.

Questions?
----------

Head over to the [sabre/dav mailinglist][4], or you can also just open a ticket
on [GitHub][5].

Made at fruux
-------------

This library is being developed by [fruux](https://fruux.com/). Drop us a line for commercial services or enterprise support.

[1]: http://nodejs.org/api/events.html
[2]: https://github.com/igorw/evenement
[3]: http://getcomposer.org/
[4]: http://groups.google.com/group/sabredav-discuss
[5]: https://github.com/fruux/sabre-event/issues/

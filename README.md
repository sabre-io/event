sabre/event
===========

A lightweight library for event-based development in PHP.

This library provides two patterns:

1. EventEmitter
2. Promises

Full documentation can be found on [the website][1].

Installation
------------

Make sure you have [composer][3] installed, and then run:

    composer require sabre/event "~2.0.0"

For legacy reasons, we also provide a unsupported [PHP 5.3 compatible version][6].
We recommend that you update your servers and use the regular version instead, though.

Build status
------------

| branch | status |
| ------ | ------ |
| master | [![Build Status](https://travis-ci.org/fruux/sabre-event.svg?branch=master)](https://travis-ci.org/fruux/sabre-event) |
| 2.0    | [![Build Status](https://travis-ci.org/fruux/sabre-event.svg?branch=2.0)](https://travis-ci.org/fruux/sabre-event) |
| 1.0    | [![Build Status](https://travis-ci.org/fruux/sabre-event.svg?branch=1.0)](https://travis-ci.org/fruux/sabre-event) |
| php53  | [![Build Status](https://travis-ci.org/fruux/sabre-event.svg?branch=php53)](https://travis-ci.org/fruux/sabre-event) |


Questions?
----------

Head over to the [sabre/dav mailinglist][4], or you can also just open a ticket
on [GitHub][5].

Made at fruux
-------------

This library is being developed by [fruux](https://fruux.com/). Drop us a line for commercial services or enterprise support.

[1]: http://sabre.io/event/
[3]: http://getcomposer.org/
[4]: http://groups.google.com/group/sabredav-discuss
[5]: https://github.com/fruux/sabre-event/issues/
[6]: https://github.com/fruux/sabre-event/tree/php53

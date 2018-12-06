<?php

namespace Sabre\Event;

use Sabre\Event\RejectionException;

/**
 * Exception that is set as the reason for a promise that has been cancelled.
 */
class CancellationException extends RejectionException
{
}

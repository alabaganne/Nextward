<?php

namespace App\Importing;

use RuntimeException;

// Thrown while reading a listing that should not be imported; the message is the reason shown in the review queue.
// With $later the listing is not rejected but read again in a later run.
class ListingRejected extends RuntimeException
{
    public function __construct(string $message, public bool $later = false)
    {
        parent::__construct($message);
    }
}

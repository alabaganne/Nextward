<?php

namespace App\Importing;

use RuntimeException;

class SourceRequestException extends RuntimeException
{
    public function __construct(string $message, public int $status = 0, public int $retryAfter = 0)
    {
        parent::__construct($message);
    }
}

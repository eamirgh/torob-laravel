<?php

namespace Torob\Laravel\Exceptions;

use Illuminate\Http\Client\Response;

class TorobApiException extends TorobException
{
    public function __construct(
        string $message,
        public int $statusCode = 0,
        public ?Response $response = null
    ) {
        parent::__construct($message, $statusCode);
    }
}

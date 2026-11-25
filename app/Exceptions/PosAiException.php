<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class PosAiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 502,
        public readonly string $errorCode = 'IA_ERROR_PROVEEDOR',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}

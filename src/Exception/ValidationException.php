<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidationException extends HttpException
{
    public function __construct(string $message = 'Validation failed', \Exception $previous = null, int $code = 0)
    {
        parent::__construct(400, $message, $previous, [], $code);
    }
}

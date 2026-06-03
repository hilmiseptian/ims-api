<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use RuntimeException;

class ConflictException extends RuntimeException
{
  public function __construct(string $message = 'Conflict')
  {
    parent::__construct($message, 409);
  }
}

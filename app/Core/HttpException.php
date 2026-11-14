<?php

declare(strict_types=1);

namespace ChallengeChain\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(private readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'HTTP ' . $status);
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}

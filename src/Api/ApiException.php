<?php

declare(strict_types=1);

namespace App\Api;

use App\Enum\ApiError;
use Symfony\Component\HttpFoundation\Response;

final class ApiException extends \Exception
{
    public function __construct(
        private readonly ApiError $error,
        ?int $httpStatus = null,
        private readonly array $details = [],
    ) {
        parent::__construct($error->message());
    }

    public function getError(): ApiError
    {
        return $this->error;
    }

    public function getHttpStatus(): int
    {
        return $this->error->httpStatus();
    }

    /** @return array<string, mixed> */
    public function getDetails(): array
    {
        return $this->details;
    }
}

<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Client;

/**
 * Failures that are safe and beneficial to retry: 5xx, network errors,
 * timeouts, clock-drift related 401s. Messenger will re-deliver up to
 * max_retries with exp backoff.
 */
final class StankoffTransientException extends \RuntimeException implements StankoffApiException
{
    public function __construct(
        string $message,
        private readonly ?int $httpStatus = null,
        private readonly ?string $errorCode = null,
        private readonly ?string $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}

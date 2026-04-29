<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Client;

use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

/**
 * Failures that retrying will not fix: invalid signature, malformed body,
 * unknown integration id, body too large.
 *
 * Extends UnrecoverableMessageHandlingException so that throwing this from a
 * Messenger handler immediately halts retry without us having to add a stamp.
 */
final class StankoffPermanentException extends UnrecoverableMessageHandlingException implements StankoffApiException
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

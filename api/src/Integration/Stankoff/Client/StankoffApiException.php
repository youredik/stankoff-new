<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Client;

/**
 * Marker for any Stankoff API failure. Implemented by:
 *  - StankoffTransientException (extends RuntimeException) — retryable
 *  - StankoffPermanentException  (extends UnrecoverableMessageHandlingException) — halts retry
 *
 * Used as an interface (not abstract class) because Permanent must inherit from
 * Symfony's UnrecoverableMessageHandlingException to halt Messenger retry, while
 * Transient is just a RuntimeException — they cannot share a class parent.
 */
interface StankoffApiException extends \Throwable
{
    public function getHttpStatus(): ?int;

    public function getErrorCode(): ?string;

    public function getResponseBody(): ?string;
}

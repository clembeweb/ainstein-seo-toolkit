<?php

declare(strict_types=1);

namespace Ainstein\Editorial\Http;

/**
 * Eccezione che mappa direttamente su una risposta JSON di errore.
 * I messaggi sono in italiano e user-friendly (mai stacktrace).
 */
class HttpException extends \RuntimeException
{
    public int $status;
    /** @var array<string,mixed> */
    public array $extra;

    /** @param array<string,mixed> $extra */
    public function __construct(int $status, string $message, array $extra = [])
    {
        parent::__construct($message);
        $this->status = $status;
        $this->extra  = $extra;
    }

    /** @return array<string,mixed> */
    public function toPayload(): array
    {
        return array_merge(['error' => $this->getMessage()], $this->extra);
    }
}

<?php

namespace LaraGram\Log\Logger;

use LaraGram\Http\BaseRequest as Request;

interface DebugLoggerInterface
{
    /**
     * Returns an array of logs.
     *
     * @return array<array{
     *     channel: ?string,
     *     context: array<string, mixed>,
     *     message: string,
     *     priority: int,
     *     priorityName: string,
     *     timestamp: int,
     *     timestamp_rfc3339: string,
     * }>
     */
    public function getLogs(?Request $request = null): array;

    /**
     * Returns the number of errors.
     */
    public function countErrors(?Request $request = null): int;

    /**
     * Removes all log records.
     */
    public function clear(): void;
}

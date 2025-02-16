<?php

namespace LaraGram\Database\Eloquent\Concerns;

trait HasUuids
{
    use HasUniqueStringIds;

    /**
     * Generate a new unique key for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        $time = microtime(true);
        $timeParts = explode('.', $time);

        $randomBytes = bin2hex(random_bytes(6));

        $uuid = strtoupper(sprintf('%08x-%04x-%04x-%04x-%012s',
            $timeParts[0],           // Time in seconds
            $timeParts[1],           // Microseconds
            mt_rand(0, 65535),       // Random part
            mt_rand(0, 65535),       // Random part
            $randomBytes
        ));

        return $uuid;
    }

    /**
     * Determine if given key is valid.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isValidUniqueId($value): bool
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $value) === 1;
    }
}

<?php

namespace LaraGram\Database\Eloquent\Concerns;

trait HasVersion7Uuids
{
    use HasUuids;

    /**
     * Generate a new UUID (version 7) for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        $time = round(microtime(true) * 1000);

        $randomBytes = bin2hex(random_bytes(6));

        $uuid = strtoupper(sprintf('%08x-%04x-%04x-7%03x-%012s',
            $time >> 32,                 // Time high (32 bits)
            $time & 0xFFFFFFFF,         // Time low (32 bits)
            mt_rand(0, 65535),          // Random part (16 bits)
            mt_rand(0, 65535),          // Random part (16 bits)
            $randomBytes                // Random part (12 characters)
        ));

        return $uuid;
    }
}

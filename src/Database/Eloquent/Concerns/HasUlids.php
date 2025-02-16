<?php

namespace LaraGram\Database\Eloquent\Concerns;

trait HasUlids
{
    use HasUniqueStringIds;

    /**
     * Generate a new unique key for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        $randomBytes = random_bytes(10);
        $time = (int)(microtime(true) * 1000);
        $ulid = sprintf('%08x-%06x-%06x-%06x-%06x',
            $time,
            unpack('N', $randomBytes)[1],
            unpack('N', $randomBytes)[1] & 0xFFFFFFF);
        return strtolower($ulid);
    }

    /**
     * Determine if given key is valid.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isValidUniqueId($value): bool
    {
        return preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value) === 1;
    }
}

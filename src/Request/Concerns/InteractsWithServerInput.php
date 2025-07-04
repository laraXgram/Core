<?php

namespace LaraGram\Request\Concerns;

trait InteractsWithServerInput
{
    /**
     * Retrieve a server variable from the request.
     *
     * @param  string|null  $key
     * @param  string|array|null  $default
     * @return string|array|null
     */
    public function server($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->server->all();
        }

        return $this->server->get($key, $default);
    }

    /**
     * Determine if a header is set on the request.
     *
     * @param  string  $key
     * @return bool
     */
    public function serverHas($key)
    {
        return ! is_null($this->server($key));
    }

    /**
     * Get the secret token from the request headers.
     *
     * @return string|null
     */
    public function secretToken()
    {
        return $this->server('HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN', '');
    }

    /**
     * Get the keys for all of the input and files.
     *
     * @return array
     */
    public function serverKeys()
    {
        return $this->server->keys();
    }
}

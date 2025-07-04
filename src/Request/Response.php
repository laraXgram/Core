<?php

namespace LaraGram\Request;

use ArrayObject;
use JsonSerializable;
use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Contracts\Support\Jsonable;
use LaraGram\Support\Traits\Macroable;

class Response
{
    use ResponseTrait, Macroable {
        Macroable::__call as macroCall;
    }

    protected string $content;

    /**
     * Create a new response.
     *
     * @param  mixed  $content
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($content = '')
    {
        $this->setContent($content);
    }

    /**
     * Get the response content.
     */
    public function getContent(): string|false
    {
        return transform($this->content, fn ($content) => $content, '');
    }

    /**
     * Set the content on the response.
     *
     * @param  mixed  $content
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setContent(mixed $content): static
    {
        $this->original = $content;

        $this->content = $content ?? '';

        return $this;
    }

    /**
     * Determine if the given content should be turned into JSON.
     *
     * @param  mixed  $content
     * @return bool
     */
    protected function shouldBeJson($content)
    {
        return $content instanceof Arrayable ||
            $content instanceof Jsonable ||
            $content instanceof ArrayObject ||
            $content instanceof JsonSerializable ||
            is_array($content);
    }

    /**
     * Morph the given content into JSON.
     *
     * @param  mixed  $content
     * @return string|false
     */
    protected function morphToJson($content)
    {
        if ($content instanceof Jsonable) {
            return $content->toJson();
        } elseif ($content instanceof Arrayable) {
            return json_encode($content->toArray());
        }

        return json_encode($content);
    }

    /**
     * Sends HTTP headers and content.
     *
     * @param bool $flush Whether output buffers should be flushed
     *
     * @return $this
     */
    public function send(bool $flush = true): static
    {
        if (!$flush) {
            return $this;
        }

        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (\function_exists('litespeed_finish_request')) {
            \litespeed_finish_request();
        } elseif (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true)) {
            static::closeOutputBuffers(0, true);
            flush();
        }

        return $this;
    }

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * @final
     */
    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level = \count($status);
        $flags = \PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? \PHP_OUTPUT_HANDLER_FLUSHABLE : \PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }
}

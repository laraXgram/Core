<?php

namespace LaraGram\Request;

use LaraGram\Contracts\Support\Arrayable;
use LaraGram\Contracts\Support\Jsonable;
use LaraGram\Support\Traits\Macroable;
use InvalidArgumentException;
use JsonSerializable;

class JsonResponse extends Response
{
    use ResponseTrait, Macroable {
        Macroable::__call as macroCall;
    }

    protected mixed $data;
    protected ?string $callback = null;

    public const DEFAULT_ENCODING_OPTIONS = 15;

    protected int $encodingOptions = self::DEFAULT_ENCODING_OPTIONS;

    /**
     * Create a new JSON response instance.
     *
     * @param  mixed  $data
     * @param  int  $options
     * @param  bool  $json
     * @return void
     */
    public function __construct($data = null, $options = 0, $json = false)
    {
        parent::__construct('');

        if ($json && !\is_string($data) && !is_numeric($data) && !$data instanceof \Stringable) {
            throw new \TypeError(\sprintf('"%s": If $json is set to true, argument $data must be a string or object implementing __toString(), "%s" given.', __METHOD__, get_debug_type($data)));
        }

        $data ??= new \ArrayObject();

        $json ? $this->setJson($data) : $this->setData($data);

        $this->encodingOptions = $options;
    }

    /**
     * Sets a raw string containing a JSON document to be sent.
     *
     * @return $this
     */
    public function setJson(string $json): static
    {
        $this->data = $json;

        return $this->update();
    }

    /**
     * Updates the content and headers according to the JSON data and callback.
     *
     * @return $this
     */
    protected function update(): static
    {
        if (null !== $this->callback) {
            return $this->setContent(\sprintf('/**/%s(%s);', $this->callback, $this->data));
        }

        return $this->setContent($this->data);
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    #[\Override]
    public static function fromJsonString(?string $data = null): static
    {
        return new static($data, 0, true);
    }

    /**
     * Sets the JSONP callback.
     *
     * @param  string|null  $callback
     * @return $this
     */
    public function withCallback($callback = null)
    {
        return $this->setCallback($callback);
    }

    /**
     * Get the json_decoded data from the response.
     *
     * @param  bool  $assoc
     * @param  int  $depth
     * @return mixed
     */
    public function getData($assoc = false, $depth = 512)
    {
        return json_decode($this->data, $assoc, $depth);
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    #[\Override]
    public function setData($data = []): static
    {
        $this->original = $data;

        // Ensure json_last_error() is cleared...
        json_decode('[]');

        $this->data = match (true) {
            $data instanceof Jsonable => $data->toJson($this->encodingOptions),
            $data instanceof JsonSerializable => json_encode($data->jsonSerialize(), $this->encodingOptions),
            $data instanceof Arrayable => json_encode($data->toArray(), $this->encodingOptions),
            default => json_encode($data, $this->encodingOptions),
        };

        if (! $this->hasValidJson(json_last_error())) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        return $this->update();
    }

    /**
     * Determine if an error occurred during JSON encoding.
     *
     * @param  int  $jsonError
     * @return bool
     */
    protected function hasValidJson($jsonError)
    {
        if ($jsonError === JSON_ERROR_NONE) {
            return true;
        }

        return $this->hasEncodingOption(JSON_PARTIAL_OUTPUT_ON_ERROR) &&
                    in_array($jsonError, [
                        JSON_ERROR_RECURSION,
                        JSON_ERROR_INF_OR_NAN,
                        JSON_ERROR_UNSUPPORTED_TYPE,
                    ]);
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function setEncodingOptions($options): static
    {
        $this->encodingOptions = (int) $options;

        return $this->setData($this->getData());
    }

    /**
     * Determine if a JSON encoding option is set.
     *
     * @param  int  $option
     * @return bool
     */
    public function hasEncodingOption($option)
    {
        return (bool) ($this->encodingOptions & $option);
    }
}

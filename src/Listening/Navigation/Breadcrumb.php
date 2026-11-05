<?php

namespace LaraGram\Listening\Navigation;

class Breadcrumb
{
    /**
     * Create a new breadcrumb instance.
     *
     * @param  string|null  $name  The name it can be returned to by.
     * @param  string|null  $title  The text it is shown with in a trail.
     * @param  string  $fingerprint  The listen and parameters that produced it.
     * @param  array  $update  The update that produced it.
     * @param  mixed  $state  The Step Manager step the user was on.
     * @param  int  $time  The time it was recorded.
     * @return void
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $title,
        public readonly string $fingerprint,
        public readonly array $update,
        public readonly mixed $state = null,
        public readonly int $time = 0,
    ) {
    }

    /**
     * Create a breadcrumb from its stored form.
     *
     * @param  array  $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            $data['name'] ?? null,
            $data['title'] ?? null,
            $data['fingerprint'] ?? '',
            $data['update'] ?? [],
            $data['state'] ?? null,
            $data['time'] ?? 0,
        );
    }

    /**
     * Get the stored form of the breadcrumb.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'fingerprint' => $this->fingerprint,
            'update' => $this->update,
            'state' => $this->state,
            'time' => $this->time,
        ];
    }

    /**
     * Get the text the breadcrumb is shown with in a trail.
     *
     * @return string|null
     */
    public function label(): ?string
    {
        return $this->title ?? $this->name;
    }
}

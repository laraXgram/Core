<?php

namespace LaraGram\Template;

class AnonymousComponent extends Component
{
    /**
     * The component template.
     *
     * @var string
     */
    protected $template;

    /**
     * The component data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create a new anonymous component instance.
     *
     * @param  string  $template
     * @param  array  $data
     * @return void
     */
    public function __construct($template, $data)
    {
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * Get the template / template contents that represent the component.
     *
     * @return string
     */
    public function render()
    {
        return $this->template;
    }

    /**
     * Get the data that should be supplied to the template.
     *
     * @return array
     */
    public function data()
    {
        $this->attributes = $this->attributes ?: $this->newAttributeBag();

        return array_merge(
            ($this->data['attributes'] ?? null)?->getAttributes() ?: [],
            $this->attributes->getAttributes(),
            $this->data,
            ['attributes' => $this->attributes]
        );
    }
}

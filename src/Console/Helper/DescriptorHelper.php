<?php

namespace LaraGram\Console\Helper;

use LaraGram\Console\Descriptor\DescriptorInterface;
use LaraGram\Console\Descriptor\JsonDescriptor;
use LaraGram\Console\Descriptor\MarkdownDescriptor;
use LaraGram\Console\Descriptor\ReStructuredTextDescriptor;
use LaraGram\Console\Descriptor\TextDescriptor;
use LaraGram\Console\Descriptor\XmlDescriptor;
use LaraGram\Console\Exception\InvalidArgumentException;
use LaraGram\Console\Output\OutputInterface;

class DescriptorHelper extends Helper
{
    /**
     * @var DescriptorInterface[]
     */
    private array $descriptors = [];

    public function __construct()
    {
        $this
            ->register('txt', new TextDescriptor())
            ->register('xml', new XmlDescriptor())
            ->register('json', new JsonDescriptor())
            ->register('md', new MarkdownDescriptor())
            ->register('rst', new ReStructuredTextDescriptor())
        ;
    }

    /**
     * Describes an object if supported.
     *
     * Available options are:
     * * format: string, the output format name
     * * raw_text: boolean, sets output type as raw
     *
     * @throws InvalidArgumentException when the given format is not supported
     */
    public function describe(OutputInterface $output, ?object $object, array $options = []): void
    {
        $options = array_merge([
            'raw_text' => false,
            'format' => 'txt',
        ], $options);

        if (!isset($this->descriptors[$options['format']])) {
            throw new InvalidArgumentException(\sprintf('Unsupported format "%s".', $options['format']));
        }

        $descriptor = $this->descriptors[$options['format']];
        $descriptor->describe($output, $object, $options);
    }

    /**
     * Registers a descriptor.
     *
     * @return $this
     */
    public function register(string $format, DescriptorInterface $descriptor): static
    {
        $this->descriptors[$format] = $descriptor;

        return $this;
    }

    public function getName(): string
    {
        return 'descriptor';
    }

    public function getFormats(): array
    {
        return array_keys($this->descriptors);
    }
}

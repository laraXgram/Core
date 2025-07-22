<?php

namespace LaraGram\Console\Descriptor;

use LaraGram\Console\Command\Command;
use LaraGram\Console\Exception\InvalidArgumentException;
use LaraGram\Console\ExtendedApplication;
use LaraGram\Console\Input\InputArgument;
use LaraGram\Console\Input\InputDefinition;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Output\OutputInterface;

abstract class Descriptor implements DescriptorInterface
{
    protected OutputInterface $output;

    public function describe(OutputInterface $output, object $object, array $options = []): void
    {
        $this->output = $output;

        match (true) {
            $object instanceof InputArgument => $this->describeInputArgument($object, $options),
            $object instanceof InputOption => $this->describeInputOption($object, $options),
            $object instanceof InputDefinition => $this->describeInputDefinition($object, $options),
            $object instanceof Command => $this->describeCommand($object, $options),
            $object instanceof ExtendedApplication => $this->describeApplication($object, $options),
            default => throw new InvalidArgumentException(\sprintf('Object of type "%s" is not describable.', get_debug_type($object))),
        };
    }

    protected function write(string $content, bool $decorated = false): void
    {
        $this->output->write($content, false, $decorated ? OutputInterface::OUTPUT_NORMAL : OutputInterface::OUTPUT_RAW);
    }

    /**
     * Describes an InputArgument instance.
     */
    abstract protected function describeInputArgument(InputArgument $argument, array $options = []): void;

    /**
     * Describes an InputOption instance.
     */
    abstract protected function describeInputOption(InputOption $option, array $options = []): void;

    /**
     * Describes an InputDefinition instance.
     */
    abstract protected function describeInputDefinition(InputDefinition $definition, array $options = []): void;

    /**
     * Describes a Command instance.
     */
    abstract protected function describeCommand(Command $command, array $options = []): void;

    /**
     * Describes an Application instance.
     */
    abstract protected function describeApplication($application, array $options = []): void;
}

<?php

namespace LaraGram\Console\Descriptor;

use LaraGram\Console\Output\OutputInterface;

interface DescriptorInterface
{
    public function describe(OutputInterface $output, object $object, array $options = []): void;
}

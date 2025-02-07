<?php

namespace LaraGram\Console\Output;

interface ConsoleOutputInterface extends OutputInterface
{
    /**
     * Gets the OutputInterface for errors.
     */
    public function getErrorOutput(): OutputInterface;

    public function setErrorOutput(OutputInterface $error): void;

    public function section(): ConsoleSectionOutput;
}

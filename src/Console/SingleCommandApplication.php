<?php

namespace LaraGram\Console;

use LaraGram\Console\Command\Command;
use LaraGram\Console\Input\InputInterface;
use LaraGram\Console\Output\OutputInterface;
use LaraGram\Console\ExtendedApplication as Application;

class SingleCommandApplication extends Command
{
    private string $version = 'UNKNOWN';
    private bool $autoExit = true;
    private bool $running = false;

    /**
     * @return $this
     */
    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @final
     *
     * @return $this
     */
    public function setAutoExit(bool $autoExit): static
    {
        $this->autoExit = $autoExit;

        return $this;
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        if ($this->running) {
            return parent::run($input, $output);
        }

        // We use the command name as the application name
        $application = new Application($this->getName() ?: 'UNKNOWN', $this->version);
        $application->setAutoExit($this->autoExit);
        // Fix the usage of the command displayed with "--help"
        $this->setName($_SERVER['argv'][0]);
        $application->add($this);
        $application->setDefaultCommand($this->getName(), true);

        $this->running = true;
        try {
            $ret = $application->run($input, $output);
        } finally {
            $this->running = false;
        }

        return $ret;
    }
}

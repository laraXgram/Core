<?php

namespace LaraGram\Console\EventListener;

use LaraGram\Console\ConsoleEvents;
use LaraGram\Console\Events\ConsoleErrorEvent;
use LaraGram\Console\Events\ConsoleEvent;
use LaraGram\Console\Events\ConsoleTerminateEvent;

class ErrorListener
{
    public function __construct(
        private $logger = null,
    ) {
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        if (null === $this->logger) {
            return;
        }

        $error = $event->getError();

        if (!$inputString = self::getInputString($event)) {
            $this->logger->critical('An error occurred while using the console. Message: "{message}"', ['exception' => $error, 'message' => $error->getMessage()]);

            return;
        }

        $this->logger->critical('Error thrown while running command "{command}". Message: "{message}"', ['exception' => $error, 'command' => $inputString, 'message' => $error->getMessage()]);
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if (null === $this->logger) {
            return;
        }

        $exitCode = $event->getExitCode();

        if (0 === $exitCode) {
            return;
        }

        if (!$inputString = self::getInputString($event)) {
            $this->logger->debug('The console exited with code "{code}"', ['code' => $exitCode]);

            return;
        }

        $this->logger->debug('Command "{command}" exited with code "{code}"', ['command' => $inputString, 'code' => $exitCode]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => ['onConsoleError', -128],
            ConsoleEvents::TERMINATE => ['onConsoleTerminate', -128],
        ];
    }

    private static function getInputString(ConsoleEvent $event): string
    {
        $commandName = $event->getCommand()?->getName();
        $inputString = (string) $event->getInput();

        if ($commandName) {
            return str_replace(["'$commandName'", "\"$commandName\""], $commandName, $inputString);
        }

        return $inputString;
    }
}

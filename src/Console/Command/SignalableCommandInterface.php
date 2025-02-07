<?php

namespace LaraGram\Console\Command;

interface SignalableCommandInterface
{
    /**
     * Returns the list of signals to subscribe.
     */
    public function getSubscribedSignals(): array;

    /**
     * The method will be called when the application is signaled.
     *
     * @return int|false The exit code to return or false to continue the normal execution
     */
    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false;
}

<?php

namespace LaraGram\Console;

use LaraGram\Console\Events\ConsoleCommandEvent;
use LaraGram\Console\Events\ConsoleErrorEvent;
use LaraGram\Console\Events\ConsoleSignalEvent;
use LaraGram\Console\Events\ConsoleTerminateEvent;

final class ConsoleEvents
{
    /**
     * The COMMAND event allows you to attach listeners before any command is
     * executed by the console. It also allows you to modify the command, input and output
     * before they are handed to the command.
     *
     * @Event("LaraGram\Console\Event\ConsoleCommandEvent")
     */
    public const COMMAND = 'console.command';

    /**
     * The SIGNAL event allows you to perform some actions
     * after the command execution was interrupted.
     *
     * @Event("LaraGram\Console\Event\ConsoleSignalEvent")
     */
    public const SIGNAL = 'console.signal';

    /**
     * The TERMINATE event allows you to attach listeners after a command is
     * executed by the console.
     *
     * @Event("LaraGram\Console\Event\ConsoleTerminateEvent")
     */
    public const TERMINATE = 'console.terminate';

    /**
     * The ERROR event occurs when an uncaught exception or error appears.
     *
     * This event allows you to deal with the exception/error or
     * to modify the thrown exception.
     *
     * @Event("LaraGram\Console\Event\ConsoleErrorEvent")
     */
    public const ERROR = 'console.error';

    /**
     * Event aliases.
     *
     * These aliases can be consumed by RegisterListenersPass.
     */
    public const ALIASES = [
        ConsoleCommandEvent::class => self::COMMAND,
        ConsoleErrorEvent::class => self::ERROR,
        ConsoleSignalEvent::class => self::SIGNAL,
        ConsoleTerminateEvent::class => self::TERMINATE,
    ];
}

<?php

namespace LaraGram\Console;

use Closure;
use LaraGram\Console\Command\Command as LaraGramCommand;
use LaraGram\Console\Events\ArtisanStarting;
use LaraGram\Console\Exception\CommandNotFoundException;
use LaraGram\Console\Input\ArrayInput;
use LaraGram\Console\Input\InputDefinition;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Input\StringInput;
use LaraGram\Console\Output\BufferedOutput;
use LaraGram\Contracts\Console\Application as ApplicationContract;
use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Events\Dispatcher;
use LaraGram\Support\Process\PhpExecutableFinder;
use LaraGram\Support\ProcessUtils;

class Application extends ExtendedApplication implements ApplicationContract
{
    /**
     * The LaraGram application instance.
     *
     * @var \LaraGram\Contracts\Container\Container
     */
    protected $laragram;

    /**
     * The event dispatcher instance.
     *
     * @var \LaraGram\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The output from the previous command.
     *
     * @var \LaraGram\Console\Output\BufferedOutput
     */
    protected $lastOutput;

    /**
     * The console application bootstrappers.
     *
     * @var array<array-key, \Closure($this): void>
     */
    protected static $bootstrappers = [];

    /**
     * A map of command names to classes.
     *
     * @var array
     */
    protected $commandMap = [];

    /**
     * Create a new Artisan console application.
     *
     * @param  \LaraGram\Contracts\Container\Container  $laragram
     * @param  \LaraGram\Contracts\Events\Dispatcher  $events
     * @param  string  $version
     * @return void
     */
    public function __construct(Container $laragram, Dispatcher $events, $version)
    {
        parent::__construct('LaraGram Framework', $version);

        $this->laragram = $laragram;
        $this->events = $events;
        $this->setAutoExit(false);
        $this->setCatchExceptions(false);

        $this->events->dispatch(new ArtisanStarting($this));

        $this->bootstrap();
    }

    /**
     * Determine the proper PHP executable.
     *
     * @return string
     */
    public static function phpBinary()
    {
        return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false) ?: 'php');
    }

    /**
     * Determine the proper Commander executable.
     *
     * @return string
     */
    public static function commanderBinary()
    {
        return ProcessUtils::escapeArgument('commander');
    }

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param  string  $string
     * @return string
     */
    public static function formatCommandString($string)
    {
        return sprintf('%s %s %s', static::phpBinary(), static::commanderBinary(), $string);
    }

    /**
     * Register a console "starting" bootstrapper.
     *
     * @param  \Closure($this): void  $callback
     * @return void
     */
    public static function starting(Closure $callback)
    {
        static::$bootstrappers[] = $callback;
    }

    /**
     * Bootstrap the console application.
     *
     * @return void
     */
    protected function bootstrap()
    {
        foreach (static::$bootstrappers as $bootstrapper) {
            $bootstrapper($this);
        }
    }

    /**
     * Clear the console application bootstrappers.
     *
     * @return void
     */
    public static function forgetBootstrappers()
    {
        static::$bootstrappers = [];
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \LaraGram\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     *
     * @throws \LaraGram\Console\Exception\CommandNotFoundException
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        [$command, $input] = $this->parseCommand($command, $parameters);

        if (! $this->has($command)) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $command));
        }

        return $this->run(
            $input, $this->lastOutput = $outputBuffer ?: new BufferedOutput
        );
    }

    /**
     * Parse the incoming Artisan command and its input.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return array
     */
    protected function parseCommand($command, $parameters)
    {
        if (is_subclass_of($command, LaraGramCommand::class)) {
            $callingClass = true;

            $command = $this->laragram->make($command)->getName();
        }

        if (! isset($callingClass) && empty($parameters)) {
            $command = $this->getCommandName($input = new StringInput($command));
        } else {
            array_unshift($parameters, $command);

            $input = new ArrayInput($parameters);
        }

        return [$command, $input];
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        return $this->lastOutput && method_exists($this->lastOutput, 'fetch')
                        ? $this->lastOutput->fetch()
                        : '';
    }

    /**
     * Add a command to the console.
     *
     * @param  \LaraGram\Console\Command\Command  $command
     * @return \LaraGram\Console\Command\Command|null
     */
    #[\Override]
    public function add(LaraGramCommand $command): ?LaraGramCommand
    {
        if ($command instanceof Command) {
            $command->setLaraGram($this->laragram);
        }

        return $this->addToParent($command);
    }

    /**
     * Add the command to the parent instance.
     *
     * @param  \LaraGram\Console\Command\Command  $command
     * @return \LaraGram\Console\Command\Command
     */
    protected function addToParent(LaraGramCommand $command)
    {
        return parent::add($command);
    }

    /**
     * Add a command, resolving through the application.
     *
     * @param  \LaraGram\Console\Command|string  $command
     * @return \LaraGram\Console\Command\Command|null
     */
    public function resolve($command)
    {
        if (is_subclass_of($command, LaraGramCommand::class) && ($commandName = $command::getDefaultName())) {
            foreach (explode('|', $commandName) as $name) {
                $this->commandMap[$name] = $command;
            }

            return null;
        }

        if ($command instanceof Command) {
            return $this->add($command);
        }

        return $this->add($this->laragram->make($command));
    }

    /**
     * Resolve an array of commands through the application.
     *
     * @param  array|mixed  $commands
     * @return $this
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            $this->resolve($command);
        }

        return $this;
    }

    /**
     * Set the container command loader for lazy resolution.
     *
     * @return $this
     */
    public function setContainerCommandLoader()
    {
        $this->setCommandLoader(new ContainerCommandLoader($this->laragram, $this->commandMap));

        return $this;
    }

    /**
     * Get the default input definition for the application.
     *
     * This is used to add the --env option to every available command.
     *
     * @return \LaraGram\Console\Input\InputDefinition
     */
    #[\Override]
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption($this->getEnvironmentOption());

        return $definition;
    }

    /**
     * Get the global environment option for the definition.
     *
     * @return \LaraGram\Console\Input\InputOption
     */
    protected function getEnvironmentOption()
    {
        $message = 'The environment the command should run under';

        return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message);
    }

    /**
     * Get the LaraGram application instance.
     *
     * @return \LaraGram\Contracts\Foundation\Application
     */
    public function getLaraGram()
    {
        return $this->laragram;
    }
}

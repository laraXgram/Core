<?php

namespace LaraGram\Foundation\Console;

use Closure;
use LaraGram\Console\Command;
use LaraGram\Listening\Listen;
use LaraGram\Listening\Listener;
use LaraGram\Listening\TEmplateController;
use LaraGram\Support\Arr;
use LaraGram\Support\Collection;
use LaraGram\Support\Str;
use LaraGram\Support\Stringable;
use ReflectionClass;
use ReflectionFunction;
use LaraGram\Console\Attribute\AsCommand;
use LaraGram\Console\Input\InputOption;
use LaraGram\Console\Terminal;

#[AsCommand(name: 'listen:list')]
class ListenListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'listen:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered listens';

    /**
     * The listener instance.
     *
     * @var \LaraGram\Listening\Listener
     */
    protected $listener;

    /**
     * The table headers for the command.
     *
     * @var string[]
     */
    protected $headers = ['Method', 'Pattern', 'Name', 'Action', 'Middleware'];

    /**
     * The terminal width resolver callback.
     *
     * @var \Closure|null
     */
    protected static $terminalWidthResolver;

    /**
     * The verb colors for the command.
     *
     * @var array
     */
    protected $verbColors = [
        'ANY' => 'red',
        'TEXT' => 'blue',
        'COMMAND' => '#6C7280',
        'DICE' => '#6C7280',
        'MEDIA' => 'yellow',
        'UPDATE' => 'yellow',
        'MESSAGE' => 'yellow',
        'MESSAGE_TYPE' => 'red',
        'CALLBACK_DATA' => 'red',
        'REFERRAL' => 'red',
        'HASHTAG' => 'red',
        'CASHTAG' => 'red',
        'MENTION' => 'red',
        'ADD_MEMBER' => 'red',
        'JOIN_MEMBER' => 'red',
    ];

    /**
     * Create a new listen command instance.
     *
     * @param  \LaraGram\Listening\Listener  $listener
     * @return void
     */
    public function __construct(Listener $listener)
    {
        parent::__construct();

        $this->listener = $listener;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->output->isVeryVerbose()) {
            $this->listener->flushMiddlewareGroups();
        }

        if (! $this->listener->getListens()->count()) {
            return $this->components->error("Your application doesn't have any listens.");
        }

        if (empty($listens = $this->getListens())) {
            return $this->components->error("Your application doesn't have any listens matching the given criteria.");
        }

        $this->displayListens($listens);
    }

    /**
     * Compile the listens into a displayable format.
     *
     * @return array
     */
    protected function getListens()
    {
        $listens = (new Collection($this->listener->getListens()))->map(function ($listen) {
            return $this->getListenInformation($listen);
        })->filter()->all();

        if (($sort = $this->option('sort')) !== null) {
            $listens = $this->sortListens($sort, $listens);
        } else {
            $listens = $this->sortListens('pattern', $listens);
        }

        if ($this->option('reverse')) {
            $listens = array_reverse($listens);
        }

        return $this->pluckColumns($listens);
    }

    /**
     * Get the listen information for a given listen.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return array
     */
    protected function getListenInformation(Listen $listen)
    {
        return $this->filterListen([
            'method' => implode('|', $listen->methods()),
            'pattern' => $listen->pattern(),
            'name' => $listen->getName(),
            'action' => ltrim($listen->getActionName(), '\\'),
            'middleware' => $this->getMiddleware($listen),
            'vendor' => $this->isVendorListen($listen),
        ]);
    }

    /**
     * Sort the listens by a given element.
     *
     * @param  string  $sort
     * @param  array  $listens
     * @return array
     */
    protected function sortListens($sort, array $listens)
    {
        if ($sort === 'definition') {
            return $listens;
        }

        if (Str::contains($sort, ',')) {
            $sort = explode(',', $sort);
        }

        return (new Collection($listens))
            ->sortBy($sort)
            ->toArray();
    }

    /**
     * Remove unnecessary columns from the listens.
     *
     * @param  array  $listens
     * @return array
     */
    protected function pluckColumns(array $listens)
    {
        return array_map(function ($listen) {
            return Arr::only($listen, $this->getColumns());
        }, $listens);
    }

    /**
     * Display the listen information on the console.
     *
     * @param  array  $listens
     * @return void
     */
    protected function displayListens(array $listens)
    {
        $listens = new Collection($listens);

        $this->output->writeln(
            $this->option('json') ? $this->asJson($listens) : $this->forCli($listens)
        );
    }

    /**
     * Get the middleware for the listen.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return string
     */
    protected function getMiddleware($listen)
    {
        return (new Collection($this->listener->gatherListenMiddleware($listen)))->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode("\n");
    }

    /**
     * Determine if the listen has been defined outside of the application.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return bool
     */
    protected function isVendorListen(Listen $listen)
    {
        if ($listen->action['uses'] instanceof Closure) {
            $path = (new ReflectionFunction($listen->action['uses']))
                ->getFileName();
        } elseif (is_string($listen->action['uses']) &&
            str_contains($listen->action['uses'], 'SerializableClosure')) {
            return false;
        } elseif (is_string($listen->action['uses'])) {
            if ($this->isFrameworkController($listen)) {
                return false;
            }

            $path = (new ReflectionClass($listen->getControllerClass()))
                ->getFileName();
        } else {
            return false;
        }

        return str_starts_with($path, base_path('vendor'));
    }

    /**
     * Determine if the listen uses a framework controller.
     *
     * @param  \LaraGram\Listening\Listen  $listen
     * @return bool
     */
    protected function isFrameworkController(Listen $listen)
    {
        return in_array($listen->getControllerClass(), [
            '\LaraGram\Listening\RedirectController',
            '\LaraGram\Listening\ViewController',
        ], true);
    }

    /**
     * Filter the listen by URI and / or name.
     *
     * @param  array  $listen
     * @return array|null
     */
    protected function filterListen(array $listen)
    {
        if (($this->option('name') && ! Str::contains((string) $listen['name'], $this->option('name'))) ||
            ($this->option('action') && isset($listen['action']) && is_string($listen['action']) && ! Str::contains($listen['action'], $this->option('action'))) ||
            ($this->option('pattern') && ! Str::contains($listen['pattern'], $this->option('pattern'))) ||
            ($this->option('method') && ! Str::contains($listen['method'], strtoupper($this->option('method')))) ||
            ($this->option('except-vendor') && $listen['vendor']) ||
            ($this->option('only-vendor') && ! $listen['vendor'])) {
            return;
        }

        if ($this->option('except-path')) {
            foreach (explode(',', $this->option('except-path')) as $path) {
                if (str_contains($listen['pattern'], $path)) {
                    return;
                }
            }
        }

        return $listen;
    }

    /**
     * Get the table headers for the visible columns.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return Arr::only($this->headers, array_keys($this->getColumns()));
    }

    /**
     * Get the column names to show (lowercase table headers).
     *
     * @return array
     */
    protected function getColumns()
    {
        return array_map('strtolower', $this->headers);
    }

    /**
     * Parse the column list.
     *
     * @param  array  $columns
     * @return array
     */
    protected function parseColumns(array $columns)
    {
        $results = [];

        foreach ($columns as $column) {
            if (str_contains($column, ',')) {
                $results = array_merge($results, explode(',', $column));
            } else {
                $results[] = $column;
            }
        }

        return array_map('strtolower', $results);
    }

    /**
     * Convert the given listens to JSON.
     *
     * @param  \LaraGram\Support\Collection  $listens
     * @return string
     */
    protected function asJson($listens)
    {
        return $listens
            ->map(function ($listen) {
                $listen['middleware'] = empty($listen['middleware']) ? [] : explode("\n", $listen['middleware']);

                return $listen;
            })
            ->values()
            ->toJson();
    }

    /**
     * Convert the given listens to regular CLI output.
     *
     * @param  \LaraGram\Support\Collection  $listens
     * @return array
     */
    protected function forCli($listens)
    {
        $listens = $listens->map(
            fn ($listen) => array_merge($listen, [
                'action' => $this->formatActionForCli($listen),
                'method' => $listen['method'] == 'TEXT|COMMAND|DICE|MEDIA|UPDATE|MESSAGE|MESSAGE_TYPE|CALLBACK_DATA|REFERRAL|HASHTAG|CASHTAG|MENTION|ADD_MEMBER|JOIN_MEMBER' ? 'ANY' : $listen['method'],
                'pattern' => $listen['pattern'],
            ]),
        );

        $maxMethod = mb_strlen($listens->max('method'));

        $terminalWidth = $this->getTerminalWidth();

        $listenCount = $this->determineListenCountOutput($listens, $terminalWidth);

        return $listens->map(function ($listen) use ($maxMethod, $terminalWidth) {
            [
                'action' => $action,
                'method' => $method,
                'middleware' => $middleware,
                'pattern' => $pattern,
            ] = $listen;

            $middleware = (new Stringable($middleware))->explode("\n")->filter()->whenNotEmpty(
                fn ($collection) => $collection->map(
                    fn ($middleware) => sprintf('         %s⇂ %s', str_repeat(' ', $maxMethod), $middleware)
                )
            )->implode("\n");

            $spaces = str_repeat(' ', max($maxMethod + 6 - mb_strlen($method), 0));

            $dots = str_repeat('.', max(
                $terminalWidth - mb_strlen($method.$spaces.$pattern.$action) - 6 - ($action ? 1 : 0), 0
            ));

            $dots = empty($dots) ? $dots : " $dots";

            if ($action && ! $this->output->isVerbose() && mb_strlen($method.$spaces.$pattern.$action.$dots) > ($terminalWidth - 6)) {
                $action = substr($action, 0, $terminalWidth - 7 - mb_strlen($method.$spaces.$pattern.$dots)).'…';
            }

            $method = (new Stringable($method))->explode('|')->map(
                fn ($method) => sprintf('<fg=%s>%s</>', $this->verbColors[$method] ?? 'default', $method),
            )->implode('<fg=#6C7280>|</>');

            return [sprintf(
                '  <fg=white;options=bold>%s</> %s<fg=white>%s</><fg=#6C7280>%s %s</>',
                $method,
                $spaces,
                preg_replace('#({[^}]+})#', '<fg=yellow>$1</>', $pattern),
                $dots,
                str_replace('   ', ' › ', $action ?? ''),
            ), $this->output->isVerbose() && ! empty($middleware) ? "<fg=#6C7280>$middleware</>" : null];
        })
            ->flatten()
            ->filter()
            ->prepend('')
            ->push('')->push($listenCount)->push('')
            ->toArray();
    }

    /**
     * Get the formatted action for display on the CLI.
     *
     * @param  array  $listen
     * @return string
     */
    protected function formatActionForCli($listen)
    {
        ['action' => $action, 'name' => $name] = $listen;

        if ($action === 'Closure' || $action === TemplateController::class) {
            return $name;
        }

        $name = $name ? "$name   " : null;

        $rootControllerNamespace = ($this->laragram->getNamespace().'Http\\Controllers');

        if (str_starts_with($action, $rootControllerNamespace)) {
            return $name.substr($action, mb_strlen($rootControllerNamespace) + 1);
        }

        $actionClass = explode('@', $action)[0];

        if (class_exists($actionClass) && str_starts_with((new ReflectionClass($actionClass))->getFilename(), base_path('vendor'))) {
            $actionCollection = new Collection(explode('\\', $action));

            return $name.$actionCollection->take(2)->implode('\\').'   '.$actionCollection->last();
        }

        return $name.$action;
    }

    /**
     * Determine and return the output for displaying the number of listens in the CLI output.
     *
     * @param  \LaraGram\Support\Collection  $listens
     * @param  int  $terminalWidth
     * @return string
     */
    protected function determineListenCountOutput($listens, $terminalWidth)
    {
        $listenCountText = 'Showing ['.$listens->count().'] listens';

        $offset = $terminalWidth - mb_strlen($listenCountText) - 2;

        $spaces = str_repeat(' ', $offset);

        return $spaces.'<fg=blue;options=bold>Showing ['.$listens->count().'] listens</>';
    }

    /**
     * Get the terminal width.
     *
     * @return int
     */
    public static function getTerminalWidth()
    {
        return is_null(static::$terminalWidthResolver)
            ? (new Terminal)->getWidth()
            : call_user_func(static::$terminalWidthResolver);
    }

    /**
     * Set a callback that should be used when resolving the terminal width.
     *
     * @param  \Closure|null  $resolver
     * @return void
     */
    public static function resolveTerminalWidthUsing($resolver)
    {
        static::$terminalWidthResolver = $resolver;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['json', null, InputOption::VALUE_NONE, 'Output the listen list as JSON'],
            ['method', null, InputOption::VALUE_OPTIONAL, 'Filter the listens by method'],
            ['action', null, InputOption::VALUE_OPTIONAL, 'Filter the listens by action'],
            ['name', null, InputOption::VALUE_OPTIONAL, 'Filter the listens by name'],
            ['pattern', null, InputOption::VALUE_OPTIONAL, 'Only show listens matching the given pattern'],
            ['except-path', null, InputOption::VALUE_OPTIONAL, 'Do not display the listens matching the given path pattern'],
            ['reverse', 'r', InputOption::VALUE_NONE, 'Reverse the ordering of the listens'],
            ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (method, pattern, name, action, middleware, definition) to sort by', 'pattern'],
            ['except-vendor', null, InputOption::VALUE_NONE, 'Do not display listens defined by vendor packages'],
            ['only-vendor', null, InputOption::VALUE_NONE, 'Only display listens defined by vendor packages'],
        ];
    }
}

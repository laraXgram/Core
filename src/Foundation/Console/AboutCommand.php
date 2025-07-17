<?php

namespace LaraGram\Foundation\Console;

use Closure;
use LaraGram\Console\Command;
use LaraGram\Support\Collection;
use LaraGram\Support\Composer;
use LaraGram\Support\Str;
use LaraGram\Support\Stringable;
use LaraGram\Console\Attribute\AsCommand;

#[AsCommand(name: 'about')]
class AboutCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'about {--only= : The section to display}
                {--json : Output the information as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display basic information about your application';

    /**
     * The Composer instance.
     *
     * @var \LaraGram\Support\Composer
     */
    protected $composer;

    /**
     * The data to display.
     *
     * @var array
     */
    protected static $data = [];

    /**
     * The registered callables that add custom data to the command output.
     *
     * @var array
     */
    protected static $customDataResolvers = [];

    /**
     * Create a new command instance.
     *
     * @param  \LaraGram\Support\Composer  $composer
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->gatherApplicationInformation();

        (new Collection(static::$data))
            ->map(fn ($items) => (new Collection($items))
                ->map(function ($value) {
                    if (is_array($value)) {
                        return [$value];
                    }

                    if (is_string($value)) {
                        $value = $this->laragram->make($value);
                    }

                    return (new Collection($this->laragram->call($value)))
                        ->map(fn ($value, $key) => [$key, $value])
                        ->values()
                        ->all();
                })->flatten(1)
            )
            ->sortBy(function ($data, $key) {
                $index = array_search($key, ['Environment', 'Cache', 'Drivers']);

                return $index === false ? 99 : $index;
            })
            ->filter(function ($data, $key) {
                return $this->option('only') ? in_array($this->toSearchKeyword($key), $this->sections()) : true;
            })
            ->pipe(fn ($data) => $this->display($data));

        $this->newLine();

        return 0;
    }

    /**
     * Display the application information.
     *
     * @param  \LaraGram\Support\Collection  $data
     * @return void
     */
    protected function display($data)
    {
        $this->option('json') ? $this->displayJson($data) : $this->displayDetail($data);
    }

    /**
     * Display the application information as a detail view.
     *
     * @param  \LaraGram\Support\Collection  $data
     * @return void
     */
    protected function displayDetail($data)
    {
        $data->each(function ($data, $section) {
            $this->newLine();

            $this->components->twoColumnDetail('  <fg=green;options=bold>'.$section.'</>');

            $data->pipe(fn ($data) => $section !== 'Environment' ? $data->sort() : $data)->each(function ($detail) {
                [$label, $value] = $detail;

                $this->components->twoColumnDetail($label, value($value, false));
            });
        });
    }

    /**
     * Display the application information as JSON.
     *
     * @param  \LaraGram\Support\Collection  $data
     * @return void
     */
    protected function displayJson($data)
    {
        $output = $data->flatMap(function ($data, $section) {
            return [
                (new Stringable($section))->snake()->value() => $data->mapWithKeys(fn ($item, $key) => [
                    $this->toSearchKeyword($item[0]) => value($item[1], true),
                ]),
            ];
        });

        $this->output->writeln(strip_tags(json_encode($output)));
    }

    /**
     * Gather information about the application.
     *
     * @return void
     */
    protected function gatherApplicationInformation()
    {
        self::$data = [];

        $formatEnabledStatus = fn ($value) => $value ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF';
        $formatCachedStatus = fn ($value) => $value ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>';

        static::addToSection('Environment', fn () => [
            'Application Name' => config('app.name'),
            'LaraGram Version' => $this->laragram->version(),
            'PHP Version' => phpversion(),
            'Composer Version' => $this->composer->getVersion() ?? '<fg=yellow;options=bold>-</>',
            'Environment' => $this->laragram->environment(),
            'Debug Mode' => static::format(config('app.debug'), console: $formatEnabledStatus),
            'URL' => Str::of(config('app.url'))->replace(['http://', 'https://'], ''),
            'Timezone' => config('app.timezone'),
            'Locale' => config('app.locale'),
        ]);

        static::addToSection('Cache', fn () => [
            'Config' => static::format($this->laragram->configurationIsCached(), console: $formatCachedStatus),
            'Events' => static::format($this->laragram->eventsAreCached(), console: $formatCachedStatus),
            'Listens' => static::format($this->laragram->listensAreCached(), console: $formatCachedStatus),
            'Templates' => static::format($this->hasPhpFiles(config('template.compiled')), console: $formatCachedStatus),
        ]);

        static::addToSection('Drivers', fn () => array_filter([
//            'Broadcasting' => config('broadcasting.default'),
            'Cache' => config('cache.default'),
            'Database' => config('database.default'),
            'Logs' => function ($json) {
                $logChannel = config('logging.default');

                if (config('logging.channels.'.$logChannel.'.driver') === 'stack') {
                    $secondary = new Collection(config('logging.channels.'.$logChannel.'.channels'));

                    return value(static::format(
                        value: $logChannel,
                        console: fn ($value) => '<fg=yellow;options=bold>'.$value.'</> <fg=gray;options=bold>/</> '.$secondary->implode(', '),
                        json: fn () => $secondary->all(),
                    ), $json);
                } else {
                    $logs = $logChannel;
                }

                return $logs;
            },
            'Surge' => config('surge.server'),
            'Queue' => config('queue.default'),
        ]));

        (new Collection(static::$customDataResolvers))->each->__invoke();
    }

    /**
     * Determine whether the given directory has PHP files.
     *
     * @param  string  $path
     * @return bool
     */
    protected function hasPhpFiles(string $path): bool
    {
        return count(glob($path.'/*.php')) > 0;
    }

    /**
     * Add additional data to the output of the "about" command.
     *
     * @param  string  $section
     * @param  callable|string|array  $data
     * @param  string|null  $value
     * @return void
     */
    public static function add(string $section, $data, ?string $value = null)
    {
        static::$customDataResolvers[] = fn () => static::addToSection($section, $data, $value);
    }

    /**
     * Add additional data to the output of the "about" command.
     *
     * @param  string  $section
     * @param  callable|string|array  $data
     * @param  string|null  $value
     * @return void
     */
    protected static function addToSection(string $section, $data, ?string $value = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                self::$data[$section][] = [$key, $value];
            }
        } elseif (is_callable($data) || ($value === null && class_exists($data))) {
            self::$data[$section][] = $data;
        } else {
            self::$data[$section][] = [$data, $value];
        }
    }

    /**
     * Get the sections provided to the command.
     *
     * @return array
     */
    protected function sections()
    {
        return (new Collection(explode(',', $this->option('only') ?? '')))
            ->filter()
            ->map(fn ($only) => $this->toSearchKeyword($only))
            ->all();
    }

    /**
     * Materialize a function that formats a given value for CLI or JSON output.
     *
     * @param  mixed  $value
     * @param  (\Closure(mixed):(mixed))|null  $console
     * @param  (\Closure(mixed):(mixed))|null  $json
     * @return \Closure(bool):mixed
     */
    public static function format($value, ?Closure $console = null, ?Closure $json = null)
    {
        return function ($isJson) use ($value, $console, $json) {
            if ($isJson === true && $json instanceof Closure) {
                return value($json, $value);
            } elseif ($isJson === false && $console instanceof Closure) {
                return value($console, $value);
            }

            return value($value);
        };
    }

    /**
     * Format the given string for searching.
     *
     * @param  string  $value
     * @return string
     */
    protected function toSearchKeyword(string $value)
    {
        return (new Stringable($value))->lower()->snake()->value();
    }

    /**
     * Flush the registered about data.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$data = [];

        static::$customDataResolvers = [];
    }
}

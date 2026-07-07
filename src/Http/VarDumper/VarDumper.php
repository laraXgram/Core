<?php

namespace LaraGram\Http\VarDumper;

use LaraGram\Http\BaseRequest;
use LaraGram\Http\RequestStack;
use LaraGram\Http\VarDumper\Caster\ReflectionCaster;
use LaraGram\Http\VarDumper\Cloner\VarCloner;
use LaraGram\Http\VarDumper\Dumper\CliDumper;
use LaraGram\Http\VarDumper\Dumper\ContextProvider\CliContextProvider;
use LaraGram\Http\VarDumper\Dumper\ContextProvider\RequestContextProvider;
use LaraGram\Http\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use LaraGram\Http\VarDumper\Dumper\ContextualizedDumper;
use LaraGram\Http\VarDumper\Dumper\DataDumperInterface;
use LaraGram\Http\VarDumper\Dumper\HtmlDumper;
use LaraGram\Http\VarDumper\Dumper\ServerDumper;

// Load the global dump() function
require_once __DIR__.'/functions.php';

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class VarDumper
{
    /**
     * @var callable|null
     */
    private static $handler;

    public static function dump(mixed $var, ?string $label = null): mixed
    {
        if (null === self::$handler) {
            self::register();
        }

        return (self::$handler)($var, $label);
    }

    public static function setHandler(?callable $callable): ?callable
    {
        $prevHandler = self::$handler;

        // Prevent replacing the handler with expected format as soon as the env var was set:
        if (isset($_SERVER['VAR_DUMPER_FORMAT'])) {
            return $prevHandler;
        }

        self::$handler = $callable;

        return $prevHandler;
    }

    private static function register(): void
    {
        $cloner = new VarCloner();
        $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $format = $_SERVER['VAR_DUMPER_FORMAT'] ?? null;

        $dumper = match ($format) {
            'html' => new HtmlDumper(),
            'cli' => new CliDumper(),
            'server' => self::selectDumperForAccept($_SERVER['VAR_DUMPER_SERVER'] ?? '127.0.0.1:9912'),
            default => self::selectDumperForAccept(
                $format && 'tcp' === parse_url($format, \PHP_URL_SCHEME) ? $format : null,
            ),
        };

        if (!$dumper instanceof ServerDumper) {
            $dumper = new ContextualizedDumper($dumper, [new SourceContextProvider()]);
        }

        self::$handler = static function ($var, ?string $label = null) use ($cloner, $dumper) {
            $var = $cloner->cloneVar($var);

            if (null !== $label) {
                $var = $var->withContext(['label' => $label]);
            }

            $dumper->dump($var);
        };
    }

    private static function selectDumperForAccept(?string $serverHost): DataDumperInterface
    {
        $isCliSapi = \in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true);
        $accept = $_SERVER['HTTP_ACCEPT'] ?? ($isCliSapi ? 'txt' : 'html');

        $dumper = match (true) {
            str_contains($accept, 'html'), str_contains($accept, '*/*') => new HtmlDumper(),
            $isCliSapi => new CliDumper(),
            default => new CliDumper('php://output'),
        };

        if (null !== $serverHost) {
            $dumper = new ServerDumper($serverHost, $dumper, self::getDefaultContextProviders());
        }

        return $dumper;
    }

    private static function getDefaultContextProviders(): array
    {
        $contextProviders = [];

        if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && class_exists(BaseRequest::class)) {
            $requestStack = new RequestStack();
            $requestStack->push(BaseRequest::createFromGlobals());
            $contextProviders['request'] = new RequestContextProvider($requestStack);
        }

        $fileLinkFormatter = class_exists(FileLinkFormatter::class) ? new FileLinkFormatter(null, $requestStack ?? null) : null;

        return $contextProviders + [
            'cli' => new CliContextProvider(),
            'source' => new SourceContextProvider(null, null, $fileLinkFormatter),
        ];
    }
}

<?php

namespace LaraGram\Support\Facades;

/**
 * @method static string full()
 * @method static string current()
 * @method static string previous(mixed $fallback = false)
 * @method static string previousPath(mixed $fallback = false)
 * @method static string to(string $path, mixed $extra = [], bool|null $secure = null)
 * @method static string query(string $path, array $query = [], mixed $extra = [], bool|null $secure = null)
 * @method static string secure(string $path, array $parameters = [])
 * @method static string asset(string $path, bool|null $secure = null)
 * @method static string secureAsset(string $path)
 * @method static string assetFrom(string $root, string $path, bool|null $secure = null)
 * @method static string formatScheme(bool|null $secure = null)
 * @method static string signedRoute(\BackedEnum|string $name, mixed $parameters = [], \DateTimeInterface|\DateInterval|int|null $expiration = null, bool $absolute = true)
 * @method static string temporarySignedRoute(\BackedEnum|string $name, \DateTimeInterface|\DateInterval|int $expiration, array $parameters = [], bool $absolute = true)
 * @method static bool hasValidSignature(\LaraGram\Http\Request $request, bool $absolute = true, \Closure|array $ignoreQuery = [])
 * @method static bool hasValidRelativeSignature(\LaraGram\Http\Request $request, \Closure|array $ignoreQuery = [])
 * @method static bool hasCorrectSignature(\LaraGram\Http\Request $request, bool $absolute = true, \Closure|array $ignoreQuery = [])
 * @method static bool signatureHasNotExpired(\LaraGram\Http\Request $request)
 * @method static string route(\BackedEnum|string $name, mixed $parameters = [], bool $absolute = true)
 * @method static string toRoute(\LaraGram\Routing\Route $route, mixed $parameters, bool $absolute)
 * @method static string action(string|array $action, mixed $parameters = [], bool $absolute = true)
 * @method static array formatParameters(mixed $parameters)
 * @method static string formatRoot(string $scheme, string|null $root = null)
 * @method static string format(string $root, string $path, \LaraGram\Routing\Route|null $route = null)
 * @method static bool isValidUrl(string $path)
 * @method static void defaults(array $defaults)
 * @method static array getDefaultParameters()
 * @method static void forceScheme(string|null $scheme)
 * @method static void forceHttps(bool $force = true)
 * @method static void useOrigin(string|null $root)
 * @method static void useAssetOrigin(string|null $root)
 * @method static \LaraGram\Routing\UrlGenerator formatHostUsing(\Closure $callback)
 * @method static \LaraGram\Routing\UrlGenerator formatPathUsing(\Closure $callback)
 * @method static \Closure pathFormatter()
 * @method static \LaraGram\Http\Request getRequest()
 * @method static void setRequest(\LaraGram\Http\Request $request)
 * @method static \LaraGram\Routing\UrlGenerator setRoutes(\LaraGram\Routing\RouteCollectionInterface $routes)
 * @method static \LaraGram\Routing\UrlGenerator setSessionResolver(callable $sessionResolver)
 * @method static \LaraGram\Routing\UrlGenerator setKeyResolver(callable $keyResolver)
 * @method static \LaraGram\Routing\UrlGenerator withKeyResolver(callable $keyResolver)
 * @method static \LaraGram\Routing\UrlGenerator resolveMissingNamedRoutesUsing(callable $missingNamedRouteResolver)
 * @method static string getRootControllerNamespace()
 * @method static \LaraGram\Routing\UrlGenerator setRootControllerNamespace(string $rootNamespace)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \LaraGram\Routing\UrlGenerator
 */
class URL extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'url';
    }
}

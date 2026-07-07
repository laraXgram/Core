<?php

use LaraGram\Container\Container;
use LaraGram\Contracts\Auth\Access\Gate;
use LaraGram\Contracts\Auth\Guard;
use LaraGram\Contracts\Bus\Dispatcher;
use LaraGram\Contracts\Debug\ExceptionHandler;
use LaraGram\Contracts\Routing\ResponseFactory;
use LaraGram\Contracts\Routing\UrlGenerator;
use LaraGram\Contracts\Support\Responsable;
use LaraGram\Cookie\Cookie;
use LaraGram\Cookie\CookieJar;
use LaraGram\Contracts\Cookie\Factory as CookieFactory;
use LaraGram\Foundation\Bus\PendingClosureDispatch;
use LaraGram\Foundation\Bus\PendingDispatch;
use LaraGram\Http\BaseResponse;
use LaraGram\Http\Factory\UriInterface;
use LaraGram\Http\RedirectResponse;
use LaraGram\Log\Context\Repository;
use LaraGram\Queue\CallQueuedClosure;
use LaraGram\Support\Facades\Date;
use LaraGram\Support\Facades\Route;
use LaraGram\Support\Uri;
use LaraGram\Validation\Factory as ValidationFactory;
use LaraGram\Contracts\View\Factory as ViewFactory;
use LaraGram\Contracts\View\View as ViewContract;
use LaraGram\Contracts\Template\Factory as TemplateFactory;

if (! function_exists('abort')) {
    /**
     * Throw an HttpException with the given data.
     *
     * @param  \LaraGram\Http\BaseResponse|\LaraGram\Contracts\Support\Responsable|int  $code
     * @param  string  $message
     * @return never
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\HttpException
     * @throws \LaraGram\Foundation\Http\Exceptions\NotFoundHttpException
     * @throws \LaraGram\Http\Exceptions\HttpResponseException
     */
    function abort($code, $message = '', array $headers = [])
    {
        if ($code instanceof BaseResponse) {
            throw new HttpResponseException($code);
        } elseif ($code instanceof Responsable) {
            throw new HttpResponseException($code->toResponse(request()));
        }

        app()->abort($code, $message, $headers);
    }
}

if (! function_exists('abort_if')) {
    /**
     * Throw an HttpException with the given data if the given condition is true.
     *
     * @param  bool  $boolean
     * @param \LaraGram\Http\BaseResponse|\LaraGram\Contracts\Support\Responsable|int $code
     * @param  string  $message
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\HttpException
     * @throws \LaraGram\Foundation\Http\Exceptions\NotFoundHttpException
     */
    function abort_if($boolean, $code, $message = '', array $headers = []): void
    {
        if ($boolean) {
            abort($code, $message, $headers);
        }
    }
}

if (! function_exists('abort_unless')) {
    /**
     * Throw an HttpException with the given data unless the given condition is true.
     *
     * @param  bool  $boolean
     * @param \LaraGram\Http\BaseResponse|\LaraGram\Contracts\Support\Responsable|int $code
     * @param  string  $message
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\HttpException
     * @throws \LaraGram\Foundation\Http\Exceptions\NotFoundHttpException
     */
    function abort_unless($boolean, $code, $message = '', array $headers = []): void
    {
        if (! $boolean) {
            abort($code, $message, $headers);
        }
    }
}

if (! function_exists('action')) {
    /**
     * Generate the URL to a controller action.
     *
     * @param  string|array  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     */
    function action($name, $parameters = [], $absolute = true): string
    {
        return app('url')->action($name, $parameters, $absolute);
    }
}

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @template TClass
     *
     * @param  string|class-string<TClass>|null  $abstract
     * @param  array  $parameters
     * @return ($abstract is class-string<TClass> ? TClass : ($abstract is null ? \LaraGram\Foundation\Application : mixed))
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param  string  $path
     * @return string
     */
    function app_path($path = '')
    {
        return app()->path($path);
    }
}

if (! function_exists('asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param  string  $path
     * @param  bool|null  $secure
     */
    function asset($path, $secure = null): string
    {
        return app('url')->asset($path, $secure);
    }
}

if (! function_exists('auth')) {
    /**
     * Get the available auth instance.
     *
     * @param  string|null  $guard
     * @return ($guard is null ? \LaraGram\Contracts\Auth\Factory : \LaraGram\Contracts\Auth\Guard)
     */
    function auth($guard = null): AuthFactory|Guard
    {
        if (is_null($guard)) {
            return app(AuthFactory::class);
        }

        return app(AuthFactory::class)->guard($guard);
    }
}

if (! function_exists('back')) {
    /**
     * Create a new redirect response to the previous location.
     *
     * @param  int  $status
     * @param  array  $headers
     * @param  mixed  $fallback
     */
    function back($status = 302, $headers = [], $fallback = false): RedirectResponse
    {
        return app('redirect')->back($status, $headers, $fallback);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '')
    {
        return app()->basePath($path);
    }
}

if (! function_exists('bcrypt')) {
    /**
     * Hash the given value against the bcrypt algorithm.
     *
     * @param  string  $value
     * @param  array  $options
     * @return string
     */
    function bcrypt($value, $options = [])
    {
        return app('hash')->driver('bcrypt')->make($value, $options);
    }
}

if (! function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * If an array is passed, we'll assume you want to put to the cache.
     *
     * @param  string|array<string, mixed>|null  $key  key|data
     * @param  mixed  $default  default|expiration|null
     * @return ($key is null ? \LaraGram\Cache\CacheManager : ($key is string ? mixed : bool))
     *
     * @throws \InvalidArgumentException
     */
    function cache($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('cache');
        }

        if (is_string($key)) {
            return app('cache')->get($key, $default);
        }

        if (! is_array($key)) {
            throw new InvalidArgumentException(
                'When setting a value in the cache, you must pass an array of key / value pairs.'
            );
        }

        return app('cache')->put(key($key), reset($key), ttl: $default);
    }
}

if (! function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array<string, mixed>|string|null  $key
     * @param  mixed  $default
     * @return ($key is null ? \LaraGram\Config\Repository : ($key is string ? mixed : null))
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param  string  $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->configPath($path);
    }
}

if (! function_exists('context')) {
    /**
     * Get / set the specified context value.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return ($key is string ? mixed : \LaraGram\Log\Context\Repository)
     */
    function context($key = null, $default = null)
    {
        $context = app(Repository::class);

        return match (true) {
            is_null($key) => $context,
            is_array($key) => $context->add($key),
            default => $context->get($key, $default),
        };
    }
}

if (! function_exists('cookie')) {
    /**
     * Create a new cookie instance.
     *
     * @param  string|null  $name
     * @param  string|null  $value
     * @param  int  $minutes
     * @param  string|null  $path
     * @param  string|null  $domain
     * @param  bool|null  $secure
     * @param  bool  $httpOnly
     * @param  bool  $raw
     * @param  string|null  $sameSite
     * @return ($name is null ? \LaraGram\Cookie\CookieJar : \LaraGram\Cookie\Cookie)
     */
    function cookie($name = null, $value = null, $minutes = 0, $path = null, $domain = null, $secure = null, $httpOnly = true, $raw = false, $sameSite = null): CookieJar|Cookie
    {
        $cookie = app(CookieFactory::class);

        if (is_null($name)) {
            return $cookie;
        }

        return $cookie->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }
}

if (! function_exists('database_path')) {
    /**
     * Get the database path.
     *
     * @param  string  $path
     * @return string
     */
    function database_path($path = '')
    {
        return app()->databasePath($path);
    }
}

if (! function_exists('decrypt')) {
    /**
     * Decrypt the given value.
     *
     * @param  string  $value
     * @param  bool  $unserialize
     * @return mixed
     */
    function decrypt($value, $unserialize = true)
    {
        return app('encrypter')->decrypt($value, $unserialize);
    }
}

if (! function_exists('defer')) {
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback
     * @param  string|null  $name
     * @param  bool  $always
     * @return \LaraGram\Support\Defer\DeferredCallback
     */
    function defer(?callable $callback = null, ?string $name = null, bool $always = false)
    {
        return \LaraGram\Support\defer($callback, $name, $always);
    }
}

if (! function_exists('dispatch')) {
    /**
     * Dispatch a job to its appropriate handler.
     *
     * @param  mixed  $job
     * @return ($job is \Closure ? \LaraGram\Foundation\Bus\PendingClosureDispatch : \LaraGram\Foundation\Bus\PendingDispatch)
     */
    function dispatch($job)
    {
        return $job instanceof Closure
            ? new PendingClosureDispatch(CallQueuedClosure::create($job))
            : new PendingDispatch($job);
    }
}

if (! function_exists('dispatch_sync')) {
    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * Queueable jobs will be dispatched to the "sync" queue.
     *
     * @param  mixed  $job
     * @param  mixed  $handler
     * @return mixed
     */
    function dispatch_sync($job, $handler = null)
    {
        return app(Dispatcher::class)->dispatchSync($job, $handler);
    }
}

if (! function_exists('encrypt')) {
    /**
     * Encrypt the given value.
     *
     * @param  mixed  $value
     * @param  bool  $serialize
     * @return string
     */
    function encrypt($value, $serialize = true)
    {
        return app('encrypter')->encrypt($value, $serialize);
    }
}

if (! function_exists('event')) {
    /**
     * Dispatch an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    function event(...$args)
    {
        return app('events')->dispatch(...$args);
    }
}

if (! function_exists('info')) {
    /**
     * Write some information to the log.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    function info($message, $context = [])
    {
        app('log')->info($message, $context);
    }
}

if (! function_exists('lang_path')) {
    /**
     * Get the path to the language folder.
     *
     * @param  string  $path
     * @return string
     */
    function lang_path($path = '')
    {
        return app()->langPath($path);
    }
}

if (! function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * @param  string|null  $message
     * @param  array  $context
     * @return ($message is null ? \LaraGram\Log\LogManager : null)
     */
    function logger($message = null, array $context = [])
    {
        if (is_null($message)) {
            return app('log');
        }

        return app('log')->debug($message, $context);
    }
}

if (! function_exists('logs')) {
    /**
     * Get a log driver instance.
     *
     * @param  string|null  $driver
     * @return ($driver is null ? \LaraGram\Log\LogManager : \LaraGram\Log\LoggerInterface)
     */
    function logs($driver = null)
    {
        return $driver ? app('log')->driver($driver) : app('log');
    }
}

if (! function_exists('now')) {
    /**
     * Create a new Carbon instance for the current time.
     *
     * @param  \DateTimeZone|string|null  $tz
     * @return \LaraGram\Support\Tempora
     */
    function now($tz = null)
    {
        return Date::now($tz);
    }
}

if (! function_exists('policy')) {
    /**
     * Get a policy instance for a given class.
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    function policy($class)
    {
        return app(Gate::class)->getPolicyFor($class);
    }
}

if (! function_exists('precognitive')) {
    /**
     * Handle a Precognition controller hook.
     *
     * @param  null|callable  $callable
     * @return mixed
     */
    function precognitive($callable = null)
    {
        $callable ??= function () {
            //
        };

        $payload = $callable(function ($default, $precognition = null) {
            $response = request()->isPrecognitive()
                ? ($precognition ?? $default)
                : $default;

            abort(Router::toResponse(request(), value($response)));
        });

        if (request()->isPrecognitive()) {
            abort(204, headers: ['Precognition-Success' => 'true']);
        }

        return $payload;
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '')
    {
        return app()->publicPath($path);
    }
}

if (! function_exists('redirect')) {
    /**
     * Get an instance of the redirector.
     *
     * @return \LaraGram\Listening\Redirector
     */
    function redirect()
    {
        return app('redirect');
    }
}

if (! function_exists('report')) {
    /**
     * Report an exception.
     *
     * @param  \Throwable|string  $exception
     * @return void
     */
    function report($exception)
    {
        if (is_string($exception)) {
            $exception = new Exception($exception);
        }

        app(ExceptionHandler::class)->report($exception);
    }
}

if (! function_exists('report_if')) {
    /**
     * Report an exception if the given condition is true.
     *
     * @param  bool  $boolean
     * @param  \Throwable|string  $exception
     * @return void
     */
    function report_if($boolean, $exception)
    {
        if ($boolean) {
            report($exception);
        }
    }
}

if (! function_exists('report_unless')) {
    /**
     * Report an exception unless the given condition is true.
     *
     * @param  bool  $boolean
     * @param  \Throwable|string  $exception
     * @return void
     */
    function report_unless($boolean, $exception)
    {
        if (! $boolean) {
            report($exception);
        }
    }
}

if (! function_exists('request')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  list<string>|string|null  $key
     * @param  mixed  $default
     * @return ($key is null ? \LaraGram\Http\Request : ($key is string ? mixed : array<string, mixed>))
     */
    function request($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('http.request');
        }

        if (is_array($key)) {
            return app('http.request')->only($key);
        }

        $value = app('http.request')->__get($key);

        return is_null($value) ? value($default) : $value;
    }
}

if (! function_exists('rescue')) {
    /**
     * Catch a potential exception and return a default value.
     *
     * @template TValue
     * @template TFallback
     *
     * @param  callable(): TValue  $callback
     * @param  (callable(\Throwable): TFallback)|TFallback  $rescue
     * @param  bool|callable(\Throwable): bool  $report
     * @return TValue|TFallback
     */
    function rescue(callable $callback, $rescue = null, $report = true)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if (value($report, $e)) {
                report($e);
            }

            return value($rescue, $e);
        }
    }
}

if (! function_exists('resolve')) {
    /**
     * Resolve a service from the container.
     *
     * @template TClass
     *
     * @param  string|class-string<TClass>  $name
     * @param  array  $parameters
     * @return ($name is class-string<TClass> ? TClass : mixed)
     */
    function resolve($name, array $parameters = [])
    {
        return app($name, $parameters);
    }
}

if (! function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     *
     * @param  string  $path
     */
    function resource_path($path = ''): string
    {
        return app()->resourcePath($path);
    }
}

if (! function_exists('response')) {
    /**
     * Return a new response from the application.
     *
     * @param  \LaraGram\Contracts\View\View|string|array|null  $content
     * @param  int  $status
     * @return ($content is null ? \LaraGram\Contracts\Routing\ResponseFactory : \LaraGram\Http\Response)
     */
    function response($content = null, $status = 200, array $headers = []): ResponseFactory|\LaraGram\Http\Response
    {
        $factory = app(ResponseFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($content ?? '', $status, $headers);
    }
}

if (! function_exists('route')) {
    /**
     * Generate the URL to a named route.
     *
     * @param  \BackedEnum|string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     */
    function route($name, $parameters = [], $absolute = true): string
    {
        return app('url')->route($name, $parameters, $absolute);
    }
}

if (! function_exists('secure_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param  string  $path
     */
    function secure_asset($path): string
    {
        return asset($path, true);
    }
}

if (! function_exists('secure_url')) {
    /**
     * Generate a HTTPS url for the application.
     *
     * @param  string  $path
     * @param  mixed  $parameters
     * @return string
     */
    function secure_url($path, $parameters = [])
    {
        return url($path, $parameters, true);
    }
}

if (! function_exists('session')) {
    /**
     * Get / set the specified session value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array<string, mixed>|string|null  $key
     * @param  mixed  $default
     * @return ($key is null ? \LaraGram\Session\SessionManager : ($key is string ? mixed : null))
     */
    function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '')
    {
        return app()->storagePath($path);
    }
}

if (! function_exists('template')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string|null  $view
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return ($view is null ? \LaraGram\Contracts\Template\Factory : \LaraGram\Contracts\Template\Template)
     */
    function template($view = null, $data = [], $mergeData = [])
    {
        $factory = app(TemplateFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData)->render();
    }
}

if (! function_exists('template_path')) {
    /**
     * Get the path to the templates folder.
     *
     * @param  string  $path
     * @return string
     */
    function template_path($path = '')
    {
        return app()->templatePath($path);
    }
}

if (! function_exists('to_listen')) {
    /**
     * Create a new redirect response to a named listen.
     *
     * @param  \BackedEnum|string  $listen
     * @param  mixed  $parameters
     * @return \LaraGram\Request\RedirectResponse
     */
    function to_listen($listen, $parameters = [])
    {
        return redirect()->listen($listen, $parameters);
    }
}

if (! function_exists('today')) {
    /**
     * Create a new Tempora instance for the current date.
     *
     * @param  \DateTimeZone|string|null  $tz
     * @return \LaraGram\Support\Tempora
     */
    function today($tz = null)
    {
        return Date::today($tz);
    }
}

if (! function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @return ($key is null ? \LaraGram\Contracts\Translation\Translator : array|string)
     */
    function trans($key = null, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            return app('translator');
        }

        return app('translator')->get($key, $replace, $locale);
    }
}

if (! function_exists('trans_choice')) {
    /**
     * Translates the given message based on a count.
     *
     * @param  string  $key
     * @param  \Countable|int|float|array  $number
     * @param  array  $replace
     * @param  string|null  $locale
     * @return string
     */
    function trans_choice($key, $number, array $replace = [], $locale = null)
    {
        return app('translator')->choice($key, $number, $replace, $locale);
    }
}

if (! function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @return string|array|null
     */
    function __($key = null, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            return $key;
        }

        return trans($key, $replace, $locale);
    }
}

if (!function_exists('trigger_deprecation')) {
    /**
     * Triggers a silenced deprecation notice.
     *
     * @param string $package The name of the Composer package that is triggering the deprecation
     * @param string $version The version of the package that introduced the deprecation
     * @param string $message The message of the deprecation
     * @param mixed  ...$args Values to insert in the message using printf() formatting
     */
    function trigger_deprecation(string $package, string $version, string $message, mixed ...$args): void
    {
        @trigger_error(($package || $version ? "Since $package $version: " : '').($args ? vsprintf($message, $args) : $message), \E_USER_DEPRECATED);
    }
}

if (! function_exists('uri')) {
    /**
     * Generate a URI for the application.
     */
    function uri(UriInterface|Stringable|array|string $uri, mixed $parameters = [], bool $absolute = true): Uri
    {
        return match (true) {
            is_array($uri) || str_contains($uri, '\\') => Uri::action($uri, $parameters, $absolute),
            str_contains($uri, '.') && Route::has($uri) => Uri::route($uri, $parameters, $absolute),
            default => Uri::of($uri),
        };
    }
}

if (! function_exists('url')) {
    /**
     * Generate a URL for the application.
     *
     * @param  string|null  $path
     * @param  mixed  $parameters
     * @param  bool|null  $secure
     * @return ($path is null ? \LaraGram\Contracts\Routing\UrlGenerator : string)
     */
    function url($path = null, $parameters = [], $secure = null): UrlGenerator|string
    {
        if (is_null($path)) {
            return app(UrlGenerator::class);
        }

        return app(UrlGenerator::class)->to($path, $parameters, $secure);
    }
}

if (! function_exists('validator')) {
    /**
     * Create a new Validator instance.
     *
     * @param  array|null  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $attributes
     * @return ($data is null ? \LaraGram\Contracts\Validation\Factory : \LaraGram\Contracts\Validation\Validator)
     */
    function validator(?array $data = null, array $rules = [], array $messages = [], array $attributes = [])
    {
        $factory = app(ValidationFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($data ?? [], $rules, $messages, $attributes);
    }
}

if (! function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string|null  $view
     * @param  \LaraGram\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return ($view is null ? \LaraGram\Contracts\View\Factory : \LaraGram\Contracts\View\View)
     */
    function view($view = null, $data = [], $mergeData = []): ViewFactory|ViewContract
    {
        $factory = app(ViewFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData);
    }
}

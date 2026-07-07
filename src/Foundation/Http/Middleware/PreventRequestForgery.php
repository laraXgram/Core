<?php

namespace LaraGram\Foundation\Http\Middleware;

use Closure;
use LaraGram\Contracts\Encryption\DecryptException;
use LaraGram\Contracts\Encryption\Encrypter;
use LaraGram\Contracts\Foundation\Application;
use LaraGram\Contracts\Support\Responsable;
use LaraGram\Cookie\CookieValuePrefix;
use LaraGram\Cookie\Middleware\EncryptCookies;
use LaraGram\Foundation\Http\Middleware\Concerns\ExcludesPaths;
use LaraGram\Http\Exceptions\OriginMismatchException;
use LaraGram\Session\TokenMismatchException;
use LaraGram\Support\Arr;
use LaraGram\Support\InteractsWithTime;
use LaraGram\Cookie\Cookie;

class PreventRequestForgery
{
    use ExcludesPaths,
        InteractsWithTime;

    /**
     * The application instance.
     *
     * @var \LaraGram\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The encrypter implementation.
     *
     * @var \LaraGram\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The URIs that should be excluded.
     *
     * @var array<int, string>
     */
    protected $except = [];

    /**
     * The globally ignored URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected static $neverVerify = [];

    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * Indicates whether requests from the same site should be allowed.
     *
     * @var bool
     */
    protected static $allowSameSite = false;

    /**
     * Indicates whether only origin verification should be used.
     *
     * @var bool
     */
    protected static $originOnly = false;

    /**
     * Create a new middleware instance.
     *
     * @param  \LaraGram\Contracts\Foundation\Application  $app
     * @param  \LaraGram\Contracts\Encryption\Encrypter  $encrypter
     */
    public function __construct(Application $app, Encrypter $encrypter)
    {
        $this->app = $app;
        $this->encrypter = $encrypter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \LaraGram\Session\TokenMismatchException
     * @throws \LaraGram\Http\Exceptions\OriginMismatchException
     */
    public function handle($request, Closure $next)
    {
        if (
            $this->isReading($request) ||
            $this->inExceptArray($request) ||
            $this->hasValidOrigin($request) ||
            $this->tokensMatch($request)
        ) {
            return tap($next($request), function ($response) use ($request) {
                if ($this->shouldAddXsrfTokenCookie()) {
                    $this->addCookieToResponse($request, $response);
                }
            });
        }

        throw new TokenMismatchException('CSRF token mismatch.');
    }

    /**
     * Determine if the HTTP request uses a ‘read’ verb.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return bool
     */
    protected function isReading($request)
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the request has a valid origin based on the Sec-Fetch-Site header.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return bool
     *
     * @throws \LaraGram\Http\Exceptions\OriginMismatchException
     */
    protected function hasValidOrigin($request)
    {
        $secFetchSite = $request->header('Sec-Fetch-Site');

        if ($secFetchSite === 'same-origin') {
            return true;
        }

        if ($secFetchSite === 'same-site' && static::$allowSameSite) {
            return true;
        }

        if (static::$originOnly) {
            throw new OriginMismatchException('Origin mismatch.');
        }

        return false;
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);

        return is_string($request->session()->token()) &&
               is_string($token) &&
               hash_equals($request->session()->token(), $token);
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (! $token && $header = $request->header('X-XSRF-TOKEN')) {
            try {
                $token = CookieValuePrefix::remove($this->encrypter->decrypt($header, static::serialized()));
            } catch (DecryptException) {
                $token = '';
            }
        }

        return $token;
    }

    /**
     * Determine if the cookie should be added to the response.
     *
     * @return bool
     */
    public function shouldAddXsrfTokenCookie()
    {
        if (static::$originOnly) {
            return false;
        }

        return $this->addHttpCookie;
    }

    /**
     * Add the CSRF token to the response cookies.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \LaraGram\Http\BaseResponse  $response
     * @return \LaraGram\Http\BaseResponse
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');

        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        $response->headers->setCookie($this->newCookie($request, $config));

        return $response;
    }

    /**
     * Create a new "XSRF-TOKEN" cookie that contains the CSRF token.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  array  $config
     * @return \LaraGram\Cookie\Cookie
     */
    protected function newCookie($request, $config)
    {
        return new Cookie(
            'XSRF-TOKEN',
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $config['secure'],
            false,
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false
        );
    }

    /**
     * Indicate that the given URIs should be excluded from CSRF verification.
     *
     * @param  array|string  $uris
     * @return void
     */
    public static function except($uris)
    {
        static::$neverVerify = array_values(array_unique(
            array_merge(static::$neverVerify, Arr::wrap($uris))
        ));
    }

    /**
     * Indicate that requests from the same site should be allowed.
     *
     * @param  bool  $allow
     * @return void
     */
    public static function allowSameSite($allow = true)
    {
        static::$allowSameSite = $allow;
    }

    /**
     * Indicate that only origin verification should be used.
     *
     * @param  bool  $originOnly
     * @return void
     */
    public static function useOriginOnly($originOnly = true)
    {
        static::$originOnly = $originOnly;
    }

    /**
     * Get the URIs that should be excluded.
     *
     * @return array
     */
    public function getExcludedPaths()
    {
        return array_merge($this->except, static::$neverVerify);
    }

    /**
     * Determine if the cookie contents should be serialized.
     *
     * @return bool
     */
    public static function serialized()
    {
        return EncryptCookies::serialized('XSRF-TOKEN');
    }

    /**
     * Flush the state of the middleware.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$neverVerify = [];
        static::$allowSameSite = false;
        static::$originOnly = false;
    }
}

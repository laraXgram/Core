<?php

namespace LaraGram\Cookie\Middleware;

use Closure;
use LaraGram\Contracts\Encryption\DecryptException;
use LaraGram\Contracts\Encryption\Encrypter as EncrypterContract;
use LaraGram\Cookie\Cookie;
use LaraGram\Cookie\CookieValuePrefix;
use LaraGram\Support\Arr;
use LaraGram\Http\BaseRequest;
use LaraGram\Http\BaseResponse;

class EncryptCookies
{
    /**
     * The encrypter instance.
     *
     * @var \LaraGram\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [];

    /**
     * The globally ignored cookies that should not be encrypted.
     *
     * @var array
     */
    protected static $neverEncrypt = [];

    /**
     * Indicates if cookies should be serialized.
     *
     * @var bool
     */
    protected static $serialize = false;

    /**
     * Create a new CookieGuard instance.
     *
     * @param  \LaraGram\Contracts\Encryption\Encrypter  $encrypter
     */
    public function __construct(EncrypterContract $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Disable encryption for the given cookie name(s).
     *
     * @param  string|array  $name
     * @return void
     */
    public function disableFor($name)
    {
        $this->except = array_merge($this->except, (array) $name);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @return \LaraGram\Http\BaseResponse
     */
    public function handle($request, Closure $next)
    {
        return $this->encrypt($next($this->decrypt($request)));
    }

    /**
     * Decrypt the cookies on the request.
     *
     * @param  \LaraGram\Http\BaseRequest  $request
     * @return \LaraGram\Http\BaseRequest
     */
    protected function decrypt(BaseRequest $request)
    {
        foreach ($request->cookies as $key => $cookie) {
            if ($this->isDisabled($key)) {
                continue;
            }

            try {
                $value = $this->decryptCookie($key, $cookie);

                $request->cookies->set($key, $this->validateValue($key, $value));
            } catch (DecryptException) {
                $request->cookies->set($key, null);
            }
        }

        return $request;
    }

    /**
     * Validate and remove the cookie value prefix from the value.
     *
     * @param  string  $key
     * @param  array<string, string>|string  $value
     * @return array|string|null
     *
     * @phpstan-return ($value is array ? array<string|null> : string|null)
     */
    protected function validateValue(string $key, $value)
    {
        return is_array($value)
            ? $this->validateArray($key, $value)
            : CookieValuePrefix::validate($key, $value, $this->encrypter->getAllKeys());
    }

    /**
     * Validate and remove the cookie value prefix from all values of an array.
     *
     * @param  string  $key
     * @param  array  $value
     * @return array
     */
    protected function validateArray(string $key, array $value)
    {
        $validated = [];

        foreach ($value as $index => $subValue) {
            $validated[$index] = $this->validateValue("{$key}[{$index}]", $subValue);
        }

        return $validated;
    }

    /**
     * Decrypt the given cookie and return the value.
     *
     * @param  string  $name
     * @param  string|array  $cookie
     * @return string|array
     */
    protected function decryptCookie($name, $cookie)
    {
        return is_array($cookie)
            ? $this->decryptArray($cookie)
            : $this->encrypter->decrypt($cookie, static::serialized($name));
    }

    /**
     * Decrypt an array based cookie.
     *
     * @param  array  $cookie
     * @return array
     */
    protected function decryptArray(array $cookie)
    {
        $decrypted = [];

        foreach ($cookie as $key => $value) {
            if (is_string($value)) {
                $decrypted[$key] = $this->encrypter->decrypt($value, static::serialized($key));
            }

            if (is_array($value)) {
                $decrypted[$key] = $this->decryptArray($value);
            }
        }

        return $decrypted;
    }

    /**
     * Encrypt the cookies on an outgoing response.
     *
     * @param  \LaraGram\Http\BaseResponse  $response
     * @return \LaraGram\Http\BaseResponse
     */
    protected function encrypt(BaseResponse $response)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($this->isDisabled($cookie->getName())) {
                continue;
            }

            $response->headers->setCookie($this->duplicate(
                $cookie,
                $this->encrypter->encrypt(
                    CookieValuePrefix::create($cookie->getName(), $this->encrypter->getKey()).$cookie->getValue(),
                    static::serialized($cookie->getName())
                )
            ));
        }

        return $response;
    }

    /**
     * Duplicate a cookie with a new value.
     *
     * @param  \LaraGram\Cookie\Cookie  $cookie
     * @param  mixed  $value
     * @return \LaraGram\Cookie\Cookie
     */
    protected function duplicate(Cookie $cookie, $value)
    {
        return $cookie->withValue($value);
    }

    /**
     * Determine whether encryption has been disabled for the given cookie.
     *
     * @param  string  $name
     * @return bool
     */
    public function isDisabled($name)
    {
        return in_array($name, array_merge($this->except, static::$neverEncrypt));
    }

    /**
     * Indicate that the given cookies should never be encrypted.
     *
     * @param  array|string  $cookies
     * @return void
     */
    public static function except($cookies)
    {
        static::$neverEncrypt = array_values(array_unique(
            array_merge(static::$neverEncrypt, Arr::wrap($cookies))
        ));
    }

    /**
     * Determine if the cookie contents should be serialized.
     *
     * @param  string  $name
     * @return bool
     */
    public static function serialized($name)
    {
        return static::$serialize;
    }

    /**
     * Flush the middleware's global state.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$neverEncrypt = [];

        static::$serialize = false;
    }
}

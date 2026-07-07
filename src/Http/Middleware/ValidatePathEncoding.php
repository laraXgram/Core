<?php

namespace LaraGram\Http\Middleware;

use Closure;
use LaraGram\Http\Exceptions\MalformedUrlException;
use LaraGram\Http\Request;

class ValidatePathEncoding
{
    /**
     * Validate that the incoming request has a valid UTF-8 encoded path.
     *
     * @param  \LaraGram\Http\Request  $request
     * @param  \Closure  $next
     * @return \LaraGram\Http\BaseResponse
     *
     * @throws \LaraGram\Http\Exceptions\MalformedUrlException
     */
    public function handle(Request $request, Closure $next)
    {
        $decodedPath = rawurldecode($request->path());

        if (! mb_check_encoding($decodedPath, 'UTF-8')) {
            throw new MalformedUrlException;
        }

        return $next($request);
    }
}

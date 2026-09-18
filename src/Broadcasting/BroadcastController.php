<?php

namespace LaraGram\Broadcasting;

use LaraGram\Http\Request;
use LaraGram\Routing\Controller;
use LaraGram\Support\Facades\Broadcast;
use LaraGram\Foundation\Http\Exceptions\AccessDeniedHttpException;

class BroadcastController extends Controller
{
    /**
     * Authenticate the request for channel access.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return \LaraGram\Http\Response
     */
    public function authenticate(Request $request)
    {
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        return Broadcast::auth($request);
    }

    /**
     * Authenticate the current user.
     *
     * @param  \LaraGram\Http\Request  $request
     * @return array|null
     *
     * @throws \LaraGram\Foundation\Http\Exceptions\AccessDeniedHttpException
     */
    public function authenticateUser(Request $request)
    {
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        return Broadcast::resolveAuthenticatedUser($request)
            ?? throw new AccessDeniedHttpException;
    }
}

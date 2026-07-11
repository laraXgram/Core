<?php

namespace LaraGram\Request\Proxy;

class ProxyConnection
{
    /**
     * @param  int  $connectTimeout  Seconds allowed to establish the connection (incl. proxy).
     * @param  int  $timeout         Total seconds allowed for the whole transfer.
     */
    public function __construct(
        protected int $connectTimeout = 5,
        protected int $timeout = 10,
    ) {
    }

    /**
     * Build the full Bot-API URL for a method.
     *
     * @param  string  $apiServer
     * @param  string  $token
     * @param  string  $method
     * @return string
     */
    protected function url(string $apiServer, string $token, string $method): string
    {
        $sep = str_ends_with($apiServer, '/') ? 'bot' : '/bot';

        return $apiServer . $sep . $token . '/' . $method;
    }

    /**
     * Apply a proxy's options onto an open curl handle.
     *
     * @param  \CurlHandle          $curl
     * @param  \LaraGram\Request\Proxy\Proxy|null  $proxy
     * @return void
     */
    protected function applyProxy(\CurlHandle $curl, ?Proxy $proxy): void
    {
        if ($proxy === null) {
            return;
        }

        curl_setopt($curl, CURLOPT_PROXY, $proxy->endpoint());
        curl_setopt($curl, CURLOPT_PROXYTYPE, $proxy->curlType());

        if (($credentials = $proxy->credentials()) !== null) {
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $credentials);
        }
    }

    /**
     * Send a Bot-API call through the given proxy and return a structured
     * result: the decoded response plus any curl-level error information.
     *
     * @param  \LaraGram\Request\Proxy\Proxy|null  $proxy
     * @param  string  $token
     * @param  string  $apiServer
     * @param  string  $method
     * @param  array   $params
     * @return array{response: mixed, errno: int, error: string, latency: float}
     */
    public function send(?Proxy $proxy, string $token, string $apiServer, string $method, array $params): array
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->url($apiServer, $token, $method));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TCP_FASTOPEN, true);
        curl_setopt($curl, CURLOPT_TCP_NODELAY, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        $this->applyProxy($curl, $proxy);

        $start = microtime(true);
        $raw = curl_exec($curl);
        $latency = (microtime(true) - $start) * 1000;

        $errno = curl_errno($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($raw === false) {
            return [
                'response' => ['ok' => false, 'code' => $errno, 'message' => $error],
                'errno'    => $errno,
                'error'    => $error,
                'latency'  => $latency,
            ];
        }

        return [
            'response' => json_decode($raw, true),
            'errno'    => 0,
            'error'    => '',
            'latency'  => $latency,
        ];
    }

    /**
     * Fire a Bot-API call through the proxy without waiting for a response
     * (the "no_response_curl" transport mode). No failover is possible here.
     *
     * @param  \LaraGram\Request\Proxy\Proxy|null  $proxy
     * @param  string  $token
     * @param  string  $apiServer
     * @param  string  $method
     * @param  array   $params
     * @return bool
     */
    public function fire(?Proxy $proxy, string $token, string $apiServer, string $method, array $params): bool
    {
        $url = escapeshellarg($this->url($apiServer, $token, $method));
        $body = escapeshellarg(json_encode($params));

        $proxyFlags = '';
        if ($proxy !== null) {
            $scheme = str_starts_with($proxy->type, 'socks') ? $proxy->type : 'http';
            $proxyFlags = '--proxy ' . escapeshellarg($scheme . '://' . $proxy->endpoint());

            if (($credentials = $proxy->credentials()) !== null) {
                $proxyFlags .= ' --proxy-user ' . escapeshellarg($credentials);
            }
        }

        $command = sprintf(
            "curl %s --connect-timeout %d --max-time %d --tcp-fastopen --tcp-nodelay -X POST -H 'Content-type: application/json' -d %s %s -o /dev/null >> /dev/null 2>&1 &",
            $proxyFlags,
            $this->connectTimeout,
            $this->timeout,
            $body,
            $url
        );

        $handle = popen($command, 'r');

        if ($handle === false) {
            return false;
        }

        pclose($handle);

        return true;
    }

    /**
     * Measure a proxy's reachability against a target URL.
     *
     * @param  \LaraGram\Request\Proxy\Proxy|null  $proxy
     * @param  string  $target
     * @return float|false  Round-trip latency in milliseconds, or false on failure.
     */
    public function ping(?Proxy $proxy, string $target): float|false
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $target);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        $this->applyProxy($curl, $proxy);

        $start = microtime(true);
        $ok = curl_exec($curl);
        $latency = (microtime(true) - $start) * 1000;

        $failed = $ok === false;

        curl_close($curl);

        return $failed ? false : round($latency, 2);
    }
}

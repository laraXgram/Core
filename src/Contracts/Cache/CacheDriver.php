<?php

namespace LaraGram\Contracts\Cache;

interface  CacheDriver
{
    public function get($key);
    public function set($key, $value);
    public function forgot($key);
    public function clear();
}
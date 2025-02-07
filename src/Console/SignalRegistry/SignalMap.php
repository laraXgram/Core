<?php

namespace LaraGram\Console\SignalRegistry;

class SignalMap
{
    private static array $map;

    public static function getSignalName(int $signal): ?string
    {
        if (!\extension_loaded('pcntl')) {
            return null;
        }

        if (!isset(self::$map)) {
            $r = new \ReflectionExtension('pcntl');
            $c = $r->getConstants();
            $map = array_filter($c, fn ($k) => str_starts_with($k, 'SIG') && !str_starts_with($k, 'SIG_'), \ARRAY_FILTER_USE_KEY);
            self::$map = array_flip($map);
        }

        return self::$map[$signal] ?? null;
    }
}

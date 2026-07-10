<?php

namespace LaraGram\Container\Attributes;

use Attribute;
use LaraGram\Contracts\Container\Container;
use LaraGram\Contracts\Container\ContextualAttribute;
use UnitEnum;

use function LaraGram\Support\enum_value;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Log implements ContextualAttribute
{
    /**
     * Create a new class instance.
     *
     * @param  UnitEnum|string|null  $channel  The log configuration's channel name.
     * @param  UnitEnum|string|null  $name  The name to prefix all logs with. Only to be used with Monolog drivers.
     */
    public function __construct(
        public UnitEnum|string|null $channel = null,
        public UnitEnum|string|null $name = null,
    ) {
    }

    /**
     * Resolve the log channel.
     *
     * @param  self  $attribute
     * @param  \LaraGram\Contracts\Container\Container  $container
     * @return \LaraGram\Log\LoggerInterface
     */
    public static function resolve(self $attribute, Container $container)
    {
        $logger = $container->make('log')->channel(enum_value($attribute->channel));

        if ($attribute->name !== null) {
            $logger = $logger->withName(enum_value($attribute->name));
        }

        return $logger;
    }
}

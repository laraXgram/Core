<?php

namespace LaraGram\Log\Context;

use LaraGram\Container\Container;
use LaraGram\Contracts\Log\ContextLogProcessor as ContextLogProcessorContract;
use LaraGram\Log\Context\Repository as ContextRepository;
use LaraGram\Log\Logger\LogRecord;

class ContextLogProcessor implements ContextLogProcessorContract
{
    /**
     * Add contextual data to the log's "extra" parameter.
     *
     * @param  \LaraGram\Log\Logger\LogRecord  $record
     * @return \LaraGram\Log\Logger\LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $app = Container::getInstance();

        if (! $app->bound(ContextRepository::class)) {
            return $record;
        }

        return $record->with(extra: [
            ...$record->extra,
            ...$app->get(ContextRepository::class)->all(),
        ]);
    }
}

<?php

namespace LaraGram\Process\Exceptions;

use LaraGram\Contracts\Process\ProcessResult;
use LaraGram\Console\Process\Exception\ProcessTimedOutException as LaraGramTimeoutException;
use LaraGram\Console\Process\Exception\RuntimeException;

class ProcessTimedOutException extends RuntimeException
{
    /**
     * The process result instance.
     *
     * @var \LaraGram\Contracts\Process\ProcessResult
     */
    public $result;

    /**
     * Create a new exception instance.
     *
     * @param  \LaraGram\Console\Process\Exception\ProcessTimedOutException  $original
     * @param  \LaraGram\Contracts\Process\ProcessResult  $result
     * @return void
     */
    public function __construct(LaraGramTimeoutException $original, ProcessResult $result)
    {
        $this->result = $result;

        parent::__construct($original->getMessage(), $original->getCode(), $original);
    }
}

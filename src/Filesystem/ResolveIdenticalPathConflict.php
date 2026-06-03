<?php
declare(strict_types=1);

namespace LaraGram\Filesystem;

class ResolveIdenticalPathConflict
{
    public const IGNORE = 'ignore';
    public const FAIL = 'fail';
    public const TRY = 'try';
}

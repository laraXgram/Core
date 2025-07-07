<?php

declare(strict_types=1);

namespace LaraGram\Support\Env\Parser;

use LaraGram\Support\Env\Exception\InvalidFileException;
use LaraGram\Support\Env\Util\Regex;
use LaraGram\Support\Env\Util\Result;
use LaraGram\Support\Env\Util\Success;

final class Parser implements ParserInterface
{
    /**
     * Parse content into an entry array.
     *
     * @param string $content
     *
     * @throws \LaraGram\Support\Env\Exception\InvalidFileException
     *
     * @return \LaraGram\Support\Env\Parser\Entry[]
     */
    public function parse(string $content)
    {
        return Regex::split("/(\r\n|\n|\r)/", $content)->mapError(static function () {
            return 'Could not split into separate lines.';
        })->flatMap(static function (array $lines) {
            return self::process(Lines::process($lines));
        })->mapError(static function (string $error) {
            throw new InvalidFileException(\sprintf('Failed to parse dotenv file. %s', $error));
        })->success()->get();
    }

    /**
     * Convert the raw entries into proper entries.
     *
     * @param string[] $entries
     *
     * @return \LaraGram\Support\Env\Util\Result<\LaraGram\Support\Env\Parser\Entry[], string>
     */
    private static function process(array $entries)
    {
        /** @var \LaraGram\Support\Env\Util\Result<\LaraGram\Support\Env\Parser\Entry[], string> */
        return \array_reduce($entries, static function (Result $result, string $raw) {
            return $result->flatMap(static function (array $entries) use ($raw) {
                return EntryParser::parse($raw)->map(static function (Entry $entry) use ($entries) {
                    /** @var \LaraGram\Support\Env\Parser\Entry[] */
                    return \array_merge($entries, [$entry]);
                });
            });
        }, Success::create([]));
    }
}

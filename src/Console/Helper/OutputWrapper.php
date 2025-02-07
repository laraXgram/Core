<?php

namespace LaraGram\Console\Helper;

final class OutputWrapper
{
    private const TAG_OPEN_REGEX_SEGMENT = '[a-z](?:[^\\\\<>]*+ | \\\\.)*';
    private const TAG_CLOSE_REGEX_SEGMENT = '[a-z][^<>]*+';
    private const URL_PATTERN = 'https?://\S+';

    public function __construct(
        private bool $allowCutUrls = false,
    ) {
    }

    public function wrap(string $text, int $width, string $break = "\n"): string
    {
        if (!$width) {
            return $text;
        }

        $tagPattern = \sprintf('<(?:(?:%s)|/(?:%s)?)>', self::TAG_OPEN_REGEX_SEGMENT, self::TAG_CLOSE_REGEX_SEGMENT);
        $limitPattern = "{1,$width}";
        $patternBlocks = [$tagPattern];
        if (!$this->allowCutUrls) {
            $patternBlocks[] = self::URL_PATTERN;
        }
        $patternBlocks[] = '.';
        $blocks = implode('|', $patternBlocks);
        $rowPattern = "(?:$blocks)$limitPattern";
        $pattern = \sprintf('#(?:((?>(%1$s)((?<=[^\S\r\n])[^\S\r\n]?|(?=\r?\n)|$|[^\S\r\n]))|(%1$s))(?:\r?\n)?|(?:\r?\n|$))#imux', $rowPattern);
        $output = rtrim(preg_replace($pattern, '\\1'.$break, $text), $break);

        return str_replace(' '.$break, $break, $output);
    }
}

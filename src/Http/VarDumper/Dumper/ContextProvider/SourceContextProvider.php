<?php

namespace LaraGram\Http\VarDumper\Dumper\ContextProvider;

use LaraGram\Http\VarDumper\FileLinkFormatter;
use LaraGram\Http\VarDumper\Cloner\VarCloner;
use LaraGram\Http\VarDumper\Dumper\HtmlDumper;
use LaraGram\Http\VarDumper\VarDumper;

final class SourceContextProvider implements ContextProviderInterface
{
    public function __construct(
        private ?string $charset = null,
        private ?string $projectDir = null,
        private ?FileLinkFormatter $fileLinkFormatter = null,
        private int $limit = 9,
    ) {
    }

    public function getContext(): ?array
    {
        $trace = debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT | \DEBUG_BACKTRACE_IGNORE_ARGS, $this->limit);

        $file = $trace[1]['file'];
        $line = $trace[1]['line'];
        $name = '-' === $file || 'Standard input code' === $file ? 'Standard input code' : false;
        $fileExcerpt = false;

        for ($i = 2; $i < $this->limit; ++$i) {
            if (isset($trace[$i]['class'], $trace[$i]['function'])
                && 'dump' === $trace[$i]['function']
                && VarDumper::class === $trace[$i]['class']
            ) {
                $file = $trace[$i]['file'] ?? $file;
                $line = $trace[$i]['line'] ?? $line;

                while (++$i < $this->limit) {
                    if (isset($trace[$i]['function'], $trace[$i]['file']) && empty($trace[$i]['class']) && !str_starts_with($trace[$i]['function'], 'call_user_func')) {
                        $file = $trace[$i]['file'];
                        $line = $trace[$i]['line'];

                        break;
                    }
                }
                break;
            }
        }

        if (false === $name) {
            $name = str_replace('\\', '/', $file);
            $name = substr($name, strrpos($name, '/') + 1);
        }

        $context = ['name' => $name, 'file' => $file, 'line' => $line];
        $context['file_excerpt'] = $fileExcerpt;

        if (null !== $this->projectDir) {
            $context['project_dir'] = $this->projectDir;
            if (str_starts_with($file, $this->projectDir)) {
                $context['file_relative'] = ltrim(substr($file, \strlen($this->projectDir)), \DIRECTORY_SEPARATOR);
            }
        }

        if ($this->fileLinkFormatter && $fileLink = $this->fileLinkFormatter->format($context['file'], $context['line'])) {
            $context['file_link'] = $fileLink;
        }

        return $context;
    }

    private function htmlEncode(string $s): string
    {
        $html = '';

        $dumper = new HtmlDumper(static function ($line) use (&$html) { $html .= $line; }, $this->charset);
        $dumper->setDumpHeader('');
        $dumper->setDumpBoundaries('', '');

        $cloner = new VarCloner();
        $dumper->dump($cloner->cloneVar($s));

        return substr(strip_tags($html), 1, -1);
    }
}

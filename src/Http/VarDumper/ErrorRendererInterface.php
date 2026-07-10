<?php

namespace LaraGram\Http\VarDumper;

use LaraGram\Http\VarDumper\Exceptions\FlattenException;

interface ErrorRendererInterface
{
    public const IDE_LINK_FORMATS = [
        'textmate' => 'txmt://open?url=file://%f&line=%l',
        'macvim' => 'mvim://open?url=file://%f&line=%l',
        'emacs' => 'emacs://open?url=file://%f&line=%l',
        'sublime' => 'subl://open?url=file://%f&line=%l',
        'phpstorm' => 'phpstorm://open?file=%f&line=%l',
        'atom' => 'atom://core/open/file?filename=%f&line=%l',
        'vscode' => 'vscode://file/%f:%l',
    ];

    /**
     * Renders a Throwable as a FlattenException.
     */
    public function render(\Throwable $exception): FlattenException;
}

<?php

namespace LaraGram\Console\Prompts\Concerns;

use InvalidArgumentException;
use LaraGram\Console\Prompts\Clear;
use LaraGram\Console\Prompts\ConfirmPrompt;
use LaraGram\Console\Prompts\MultiSearchPrompt;
use LaraGram\Console\Prompts\MultiSelectPrompt;
use LaraGram\Console\Prompts\Note;
use LaraGram\Console\Prompts\PasswordPrompt;
use LaraGram\Console\Prompts\PausePrompt;
use LaraGram\Console\Prompts\Progress;
use LaraGram\Console\Prompts\SearchPrompt;
use LaraGram\Console\Prompts\SelectPrompt;
use LaraGram\Console\Prompts\Spinner;
use LaraGram\Console\Prompts\SuggestPrompt;
use LaraGram\Console\Prompts\Table;
use LaraGram\Console\Prompts\TextareaPrompt;
use LaraGram\Console\Prompts\TextPrompt;
use LaraGram\Console\Prompts\Themes\Default\ClearRenderer;
use LaraGram\Console\Prompts\Themes\Default\ConfirmPromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\MultiSearchPromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\MultiSelectPromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\NoteRenderer;
use LaraGram\Console\Prompts\Themes\Default\PasswordPromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\PausePromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\ProgressRenderer;
use LaraGram\Console\Prompts\Themes\Default\SearchPromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\SelectPromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\SpinnerRenderer;
use LaraGram\Console\Prompts\Themes\Default\SuggestPromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\TableRenderer;
use LaraGram\Console\Prompts\Themes\Default\TextareaPromptRenderer;
use LaraGram\Console\Prompts\Themes\Default\TextPromptRenderer;

trait Themes
{
    /**
     * The name of the active theme.
     */
    protected static string $theme = 'default';

    /**
     * The available themes.
     *
     * @var array<string, array<class-string<\LaraGram\Console\Prompts\Prompt>, class-string<object&callable>>>
     */
    protected static array $themes = [
        'default' => [
            TextPrompt::class => TextPromptRenderer::class,
            TextareaPrompt::class => TextareaPromptRenderer::class,
            PasswordPrompt::class => PasswordPromptRenderer::class,
            SelectPrompt::class => SelectPromptRenderer::class,
            MultiSelectPrompt::class => MultiSelectPromptRenderer::class,
            ConfirmPrompt::class => ConfirmPromptRenderer::class,
            PausePrompt::class => PausePromptRenderer::class,
            SearchPrompt::class => SearchPromptRenderer::class,
            MultiSearchPrompt::class => MultiSearchPromptRenderer::class,
            SuggestPrompt::class => SuggestPromptRenderer::class,
            Spinner::class => SpinnerRenderer::class,
            Note::class => NoteRenderer::class,
            Table::class => TableRenderer::class,
            Progress::class => ProgressRenderer::class,
            Clear::class => ClearRenderer::class,
        ],
    ];

    /**
     * Get or set the active theme.
     *
     * @throws \InvalidArgumentException
     */
    public static function theme(?string $name = null): string
    {
        if ($name === null) {
            return static::$theme;
        }

        if (! isset(static::$themes[$name])) {
            throw new InvalidArgumentException("Prompt theme [{$name}] not found.");
        }

        return static::$theme = $name;
    }

    /**
     * Add a new theme.
     *
     * @param  array<class-string<\LaraGram\Console\Prompts\Prompt>, class-string<object&callable>>  $renderers
     */
    public static function addTheme(string $name, array $renderers): void
    {
        if ($name === 'default') {
            throw new InvalidArgumentException('The default theme cannot be overridden.');
        }

        static::$themes[$name] = $renderers;
    }

    /**
     * Get the renderer for the current prompt.
     */
    protected function getRenderer(): callable
    {
        $class = get_class($this);

        return new (static::$themes[static::$theme][$class] ?? static::$themes['default'][$class])($this);
    }

    /**
     * Render the prompt using the active theme.
     */
    protected function renderTheme(): string
    {
        $renderer = $this->getRenderer();

        return $renderer($this);
    }
}

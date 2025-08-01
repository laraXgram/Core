<?php

namespace LaraGram\Console\Prompts;

use Closure;

if (! function_exists('\LaraGram\Console\Prompts\text')) {
    /**
     * Prompt the user for text input.
     */
    function text(string $label, string $placeholder = '', string $default = '', bool|string $required = false, mixed $validate = null, string $hint = '', ?Closure $transform = null): string
    {
        return (new TextPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\textarea')) {
    /**
     * Prompt the user for multiline text input.
     */
    function textarea(string $label, string $placeholder = '', string $default = '', bool|string $required = false, mixed $validate = null, string $hint = '', int $rows = 5, ?Closure $transform = null): string
    {
        return (new TextareaPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\password')) {
    /**
     * Prompt the user for input, hiding the value.
     */
    function password(string $label, string $placeholder = '', bool|string $required = false, mixed $validate = null, string $hint = '', ?Closure $transform = null): string
    {
        return (new PasswordPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\select')) {
    /**
     * Prompt the user to select an option.
     *
     * @param  array<int|string, string>  $options
     * @param  true|string  $required
     */
    function select(string $label, array $options, int|string|null $default = null, int $scroll = 5, mixed $validate = null, string $hint = '', bool|string $required = true, ?Closure $transform = null): int|string
    {
        return (new SelectPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\multiselect')) {
    /**
     * Prompt the user to select multiple options.
     *
     * @param  array<int|string, string>  $options
     * @param  array<int|string>  $default
     * @return array<int|string>
     */
    function multiselect(string $label, array $options, array $default = [], int $scroll = 5, bool|string $required = false, mixed $validate = null, string $hint = 'Use the space bar to select options.', ?Closure $transform = null): array
    {
        return (new MultiSelectPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\confirm')) {
    /**
     * Prompt the user to confirm an action.
     */
    function confirm(string $label, bool $default = true, string $yes = 'Yes', string $no = 'No', bool|string $required = false, mixed $validate = null, string $hint = '', ?Closure $transform = null): bool
    {
        return (new ConfirmPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\pause')) {
    /**
     * Prompt the user to continue or cancel after pausing.
     */
    function pause(string $message = 'Press enter to continue...'): bool
    {
        return (new PausePrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\clear')) {
    /**
     * Clear the terminal.
     */
    function clear(): void
    {
        (new Clear)->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\suggest')) {
    /**
     * Prompt the user for text input with auto-completion.
     *
     * @param  array<string>|Closure(string): array<string>  $options
     */
    function suggest(string $label, array|Closure $options, string $placeholder = '', string $default = '', int $scroll = 5, bool|string $required = false, mixed $validate = null, string $hint = '', ?Closure $transform = null): string
    {
        return (new SuggestPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\search')) {
    /**
     * Allow the user to search for an option.
     *
     * @param  Closure(string): array<int|string, string>  $options
     * @param  true|string  $required
     */
    function search(string $label, Closure $options, string $placeholder = '', int $scroll = 5, mixed $validate = null, string $hint = '', bool|string $required = true, ?Closure $transform = null): int|string
    {
        return (new SearchPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\multisearch')) {
    /**
     * Allow the user to search for multiple option.
     *
     * @param  Closure(string): array<int|string, string>  $options
     * @return array<int|string>
     */
    function multisearch(string $label, Closure $options, string $placeholder = '', int $scroll = 5, bool|string $required = false, mixed $validate = null, string $hint = 'Use the space bar to select options.', ?Closure $transform = null): array
    {
        return (new MultiSearchPrompt(...get_defined_vars()))->prompt();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\spin')) {
    /**
     * Render a spinner while the given callback is executing.
     *
     * @template TReturn of mixed
     *
     * @param  \Closure(): TReturn  $callback
     * @return TReturn
     */
    function spin(Closure $callback, string $message = ''): mixed
    {
        return (new Spinner($message))->spin($callback);
    }
}

if (! function_exists('\LaraGram\Console\Prompts\note')) {
    /**
     * Display a note.
     */
    function note(string $message, ?string $type = null): void
    {
        (new Note($message, $type))->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\error')) {
    /**
     * Display an error.
     */
    function error(string $message): void
    {
        (new Note($message, 'error'))->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\warning')) {
    /**
     * Display a warning.
     */
    function warning(string $message): void
    {
        (new Note($message, 'warning'))->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\alert')) {
    /**
     * Display an alert.
     */
    function alert(string $message): void
    {
        (new Note($message, 'alert'))->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\info')) {
    /**
     * Display an informational message.
     */
    function info(string $message): void
    {
        (new Note($message, 'info'))->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\intro')) {
    /**
     * Display an introduction.
     */
    function intro(string $message): void
    {
        (new Note($message, 'intro'))->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\outro')) {
    /**
     * Display a closing message.
     */
    function outro(string $message): void
    {
        (new Note($message, 'outro'))->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\table')) {
    /**
     * Display a table.
     *
     * @param  array<int, string|array<int, string>> $headers
     * @param  array<int, array<int, string>> $rows
     */
    function table(array $headers = [], array|null $rows = null): void
    {
        (new Table($headers, $rows))->display();
    }
}

if (! function_exists('\LaraGram\Console\Prompts\progress')) {
    /**
     * Display a progress bar.
     *
     * @template TSteps of iterable<mixed>|int
     * @template TReturn
     *
     * @param  TSteps  $steps
     * @param  ?Closure((TSteps is int ? int : value-of<TSteps>), Progress<TSteps>): TReturn  $callback
     * @return ($callback is null ? Progress<TSteps> : array<TReturn>)
     */
    function progress(string $label, iterable|int $steps, ?Closure $callback = null, string $hint = ''): array|Progress
    {
        $progress = new Progress($label, $steps, $hint);

        if ($callback !== null) {
            return $progress->map($callback);
        }

        return $progress;
    }
}

if (! function_exists('\LaraGram\Console\Prompts\form')) {
    function form(): FormBuilder
    {
        return new FormBuilder;
    }
}

<?php

namespace LaraGram\Template\Compilers\Concerns;

trait CompilesKeyboards
{
    /**
     * Compile the @keyboard directive.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileKeyboard($expression)
    {
        $type = strtolower(trim(str_replace(['(', ')', '\'', '"', ' '], '', (string) $expression)));

        $factory = match ($type) {
            'reply'  => 'replyKeyboardMarkup()',
            'remove' => 'replyKeyboardRemove()',
            'force'  => 'forceReply()',
            default  => 'inlineKeyboardMarkup()',
        };

        return "<?php \$__t8__kb = {$factory}; \$__t8__row = []; ?>";
    }

    /**
     * Compile the @endkeyboard directive.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileEndKeyboard($expression)
    {
        return "<?php \$__t8__reply_markup = \$__t8__kb->get(); ?>";
    }

    /**
     * Compile the @keyboardOptions directive (resize_keyboard, one_time_keyboard, ...).
     *
     * @param  string  $expression
     * @return string
     */
    public function compileKeyboardOptions($expression)
    {
        return "<?php \$__t8__kb->setOptions{$expression}; ?>";
    }

    /**
     * Compile the @row directive.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRow($expression)
    {
        return "<?php \$__t8__row = []; ?>";
    }

    /**
     * Compile the @endrow directive.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileEndRow($expression)
    {
        return "<?php if (! empty(\$__t8__row)) { \$__t8__kb->appendRow(\$__t8__row); } \$__t8__row = []; ?>";
    }

    /**
     * Compile the @col directive.
     *
     * @param  string  $expression
     * @return string
     */
    public function compileCol($expression)
    {
        return "<?php \$__t8__row[] = col{$expression}; ?>";
    }
}

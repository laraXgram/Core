<?php

namespace LaraGram\Template\Compilers\Concerns;

trait CompilesKeyboards
{
    public function compileKeyboard($expression)
    {
        $compiled = '<?php $__t8__reply_markup = ';
        $expression = str_replace(['\'', '"'], '', $expression);

        if ($expression == '(reply)') {
            $compiled .= "replyKeyboardMarkup(";
        } elseif ($expression == '(remove)') {
            $compiled .= "replyKeyboardRemove(";
        } elseif ($expression == '(force)') {
            $compiled .= "forceReply(";
        } elseif ($expression == '(copy)') {
            $compiled .= "copyTextButton(";
        } else {
            $compiled .= "inlineKeyboardMarkup(";
        }

        return $compiled;
    }

    public function compileEndKeyboard($expression)
    {
        return ")->get(); ?>";
    }

    public function compileRow($expression)
    {
        return "row(";
    }

    public function compileEndRow($expression)
    {
        return "),";
    }

    public function compileCol($expression)
    {
        return "col".$expression.",";
    }
}
<?php

namespace LaraGram\Template\Compilers\Concerns;

trait CompilesPagination
{
    /**
     * Compile the @paginate statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compilePaginate($expression)
    {
        $paginator = trim($this->stripParentheses($expression ?? '')) ?: '$paginator';

        return "<?php \$__paginate = {$paginator};"
            .' $__t8__reply_markup = $__t8__reply_markup ?? $__paginate->keyboard();'
            .' $__t8__method = $__t8__method ?? $__paginate->method();'
            .' if (! isset($__t8__message_id) && ! is_null($__paginate->messageId())) {'
            .' $__t8__message_id = $__paginate->messageId(); } ?>';
    }
}

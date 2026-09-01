<?php

namespace LaraGram\Template\Compilers\Concerns;

trait CompilesRichMessages
{
    /**
     * Compile the @rich statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRich($expression)
    {
        return $this->openRichMessage($expression, false);
    }

    /**
     * Compile the @endrich statement into valid PHP.
     *
     * @return string
     */
    public function compileEndrich()
    {
        return $this->closeRichMessage('sendRichMessage');
    }

    /**
     * Compile the @richDraft statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichDraft($expression)
    {
        return $this->openRichMessage($expression, true);
    }

    /**
     * Compile the @endrichDraft statement into valid PHP.
     *
     * @return string
     */
    public function compileEndrichDraft()
    {
        return $this->closeRichMessage('sendRichMessageDraft');
    }

    /**
     * Compile the @richPhoto statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichPhoto($expression)
    {
        return $this->richMedia('photo', $expression);
    }

    /**
     * Compile the @richVideo statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichVideo($expression)
    {
        return $this->richMedia('video', $expression);
    }

    /**
     * Compile the @richAnimation statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichAnimation($expression)
    {
        return $this->richMedia('animation', $expression);
    }

    /**
     * Compile the @richAudio statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichAudio($expression)
    {
        return $this->richMedia('audio', $expression);
    }

    /**
     * Compile the @richVoice statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichVoice($expression)
    {
        return $this->richMedia('voice_note', $expression);
    }

    /**
     * Compile the @richDocument statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichDocument($expression)
    {
        return $this->richMedia('document', $expression);
    }

    /**
     * Compile the @richMedia statement into valid PHP.
     *
     * Registers a file and echoes the tg:// link that references it, so the
     * media can be placed by hand inside a <figure> or a <collage>.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichMedia($expression)
    {
        return "<?php echo \$__rich_message->media{$this->richArguments($expression)}; ?>";
    }

    /**
     * Compile the @richTable statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichTable($expression)
    {
        return $this->richAppend('table', $expression);
    }

    /**
     * Compile the @richList statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichList($expression)
    {
        return $this->richAppend('list', $expression);
    }

    /**
     * Compile the @richChecklist statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichChecklist($expression)
    {
        return $this->richAppend('checklist', $expression);
    }

    /**
     * Build the PHP that opens a rich message and starts buffering its markup.
     *
     * @param  string|null  $expression
     * @param  bool  $draft
     * @return string
     */
    protected function openRichMessage($expression, bool $draft)
    {
        $options = $this->richOptions($expression, $draft);

        return "<?php \$__rich_message = (new \\LaraGram\\Template\\Rich\\RichMessage())"
            .$options.'; ob_start(); ?>';
    }

    /**
     * Build the PHP that flushes the buffered markup into the payload.
     *
     * The method is only defaulted, never forced, so an explicit @method call
     * in the template still wins.
     *
     * @param  string  $method
     * @return string
     */
    protected function closeRichMessage(string $method)
    {
        return "<?php \$__rich_message->append(ob_get_clean());"
            ." \$__t8__rich_message = \$__rich_message->toArray();"
            ." \$__t8__method = \$__t8__method ?? '{$method}'; ?>";
    }

    /**
     * Build the PHP that appends a media block to the current rich message.
     *
     * @param  string  $type
     * @param  string|null  $expression
     * @return string
     */
    protected function richMedia(string $type, $expression)
    {
        $arguments = $this->stripParentheses($expression ?? '');

        $arguments = trim($arguments) === ''
            ? "type: '{$type}'"
            : $this->injectMediaType($arguments, $type);

        return $this->richStatement("\$__rich_message->attach({$arguments})");
    }

    /**
     * Build the PHP that appends the result of a builder call to the message.
     *
     * @param  string  $method
     * @param  string|null  $expression
     * @return string
     */
    protected function richAppend(string $method, $expression)
    {
        return $this->richStatement(
            "\$__rich_message->{$method}{$this->richArguments($expression)}"
        );
    }

    /**
     * Wrap a builder call so it lands after the markup written before it.
     *
     * The @rich block buffers its markup, so anything the builder appends
     * directly has to be preceded by a flush of that buffer, otherwise it would
     * jump ahead of the text the author already wrote.
     *
     * @param  string  $statement
     * @return string
     */
    protected function richStatement(string $statement)
    {
        return '<?php $__rich_message->append(ob_get_clean()); '
            .$statement.'; ob_start(); ?>';
    }

    /**
     * Insert the media type into a directive's argument list.
     *
     * The type always travels as a named argument, so it can sit after the
     * positional file argument the template author wrote.
     *
     * @param  string  $arguments
     * @param  string  $type
     * @return string
     */
    protected function injectMediaType(string $arguments, string $type)
    {
        return trim($arguments).", type: '{$type}'";
    }

    /**
     * Translate the options of a @rich directive into builder calls.
     *
     * @param  string|null  $expression
     * @param  bool  $draft
     * @return string
     */
    protected function richOptions($expression, bool $draft)
    {
        $calls = $draft ? '->draft()' : '';

        $arguments = $this->stripParentheses($expression ?? '');

        if (trim($arguments) === '') {
            return $calls;
        }

        $map = [
            'rtl' => 'rtl',
            'is_rtl' => 'rtl',
            'skipEntityDetection' => 'skipEntityDetection',
            'skip_entity_detection' => 'skipEntityDetection',
            'strict' => 'strict',
            'pretty' => 'pretty',
        ];

        foreach ($map as $option => $method) {
            $pattern = '/(?:^|[\s,(\[])[\'"]?'.preg_quote($option, '/').'[\'"]?\s*(?::|=>)\s*([^,)\]]+)/';

            if (preg_match($pattern, $arguments, $match)) {
                $calls .= '->'.$method.'('.trim($match[1]).')';
            }
        }

        return $calls;
    }

    /**
     * Normalise a directive's argument list into a callable argument string.
     *
     * @param  string|null  $expression
     * @return string
     */
    protected function richArguments($expression)
    {
        $arguments = $this->stripParentheses($expression ?? '');

        return '('.trim($arguments).')';
    }
}

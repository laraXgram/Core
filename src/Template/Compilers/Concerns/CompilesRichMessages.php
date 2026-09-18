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
        return $this->richMedia('photo', $expression, 'richPhoto');
    }

    /**
     * Compile the @richVideo statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichVideo($expression)
    {
        return $this->richMedia('video', $expression, 'richVideo');
    }

    /**
     * Compile the @richAnimation statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichAnimation($expression)
    {
        return $this->richMedia('animation', $expression, 'richAnimation');
    }

    /**
     * Compile the @richAudio statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichAudio($expression)
    {
        return $this->richMedia('audio', $expression, 'richAudio');
    }

    /**
     * Compile the @richVoice statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichVoice($expression)
    {
        return $this->richMedia('voice_note', $expression, 'richVoice');
    }

    /**
     * Compile the @richDocument statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichDocument($expression)
    {
        return $this->richMedia('document', $expression, 'richDocument');
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
        return $this->richEcho('richMedia', 'media', $this->richArguments($expression));
    }

    /**
     * Compile the @richTable statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichTable($expression)
    {
        return $this->richEcho('richTable', 'buildTable', $this->richArguments($expression));
    }

    /**
     * Compile the @richList statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichList($expression)
    {
        return $this->richEcho('richList', 'buildList', $this->richArguments($expression));
    }

    /**
     * Compile the @richChecklist statement into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     */
    public function compileRichChecklist($expression)
    {
        return $this->richEcho('richChecklist', 'buildChecklist', $this->richArguments($expression));
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

        return '<?php $__rich_message = \\LaraGram\\Template\\Rich\\RichMessage::begin()'
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
        return '<?php $__rich_message = \\LaraGram\\Template\\Rich\\RichMessage::end()'
            .'->append(ob_get_clean());'
            ." \$__t8__rich_message = \$__rich_message->toArray();"
            ." \$__t8__method = \$__t8__method ?? '{$method}'; ?>";
    }

    /**
     * Build the PHP that writes a media block into the message being built.
     *
     * @param  string  $type
     * @param  string|null  $expression
     * @param  string  $directive
     * @return string
     */
    protected function richMedia(string $type, $expression, string $directive)
    {
        $arguments = $this->stripParentheses($expression ?? '');

        $arguments = trim($arguments) === ''
            ? "type: '{$type}'"
            : $this->injectMediaType($arguments, $type);

        return $this->richEcho($directive, 'buildAttachment', "({$arguments})");
    }

    /**
     * Build the PHP that echoes a builder call into the surrounding markup.
     *
     * The call is echoed rather than appended to the message, so a directive
     * lands exactly where it was written, even when the markup around it is
     * produced by a loop, a condition, an include or a component.
     *
     * @param  string  $directive
     * @param  string  $method
     * @param  string  $arguments
     * @return string
     */
    protected function richEcho(string $directive, string $method, string $arguments)
    {
        return "<?php echo \\LaraGram\\Template\\Rich\\RichMessage::current('{$directive}')"
            ."->{$method}{$arguments}; ?>";
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

<?php

namespace LaraGram\Template\Rich\Html;

use LaraGram\Template\Rich\Exceptions\InvalidRichMarkupException;
use LaraGram\Template\Rich\Exceptions\RichMessageException;
use LaraGram\Template\Rich\Exceptions\RichMessageLimitException;
use LaraGram\Template\Rich\Exceptions\UnsupportedRichTagException;
use LaraGram\Template\Rich\Limits;

/**
 * Turns LaraGram's simplified rich markup into the HTML Telegram expects.
 */
class Transpiler
{
    /**
     * Void tags Telegram's own examples write in XML style.
     *
     * @var array<int, string>
     */
    private const SELF_CLOSED = ['hr', 'img', 'tg-map'];

    /**
     * Simplified button attributes mapped to the Telegram button type they
     * imply and the attribute that carries their value.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const BUTTON_TYPES = [
        'url'         => ['url', 'url'],
        'callback'    => ['callback_data', 'data'],
        'webapp'      => ['web_app', 'url'],
        'login'       => ['login_url', 'url'],
        'inline'      => ['switch_inline_query', 'query'],
        'inline-here' => ['switch_inline_query_current_chat', 'query'],
        'inline-chat' => ['switch_inline_query_chosen_chat', 'query'],
        'copy'        => ['copy_text', 'text'],
    ];

    /**
     * Chat filter flags accepted by an inline-chat button.
     *
     * @var array<string, string>
     */
    private const CHAT_FILTERS = [
        'users'    => 'allow-user-chats',
        'bots'     => 'allow-bot-chats',
        'groups'   => 'allow-group-chats',
        'channels' => 'allow-channel-chats',
    ];

    /**
     * Number of visible characters counted towards the text length limit.
     *
     * @var int
     */
    protected int $textLength = 0;

    /**
     * Number of blocks counted towards the block limit.
     *
     * @var int
     */
    protected int $blocks = 0;

    /**
     * @param  bool  $strict  Reject tags Telegram does not document.
     * @param  bool  $pretty  Normalise template indentation away.
     * @param  bool  $draft  Allow tags that only work in sendRichMessageDraft.
     */
    public function __construct(
        protected bool $strict = true,
        protected bool $pretty = true,
        protected bool $draft = false,
    ) {
    }

    /**
     * Compile simplified rich markup into Telegram rich HTML.
     *
     * @param  string  $markup
     * @return string
     */
    public function transpile(string $markup): string
    {
        $this->textLength = 0;
        $this->blocks = 0;

        $tokens = $this->expand(Tokenizer::tokenize($markup));

        $tokens = $this->validate($tokens);

        if ($this->pretty) {
            $tokens = $this->normaliseWhitespace($tokens);
        }

        if ($this->textLength > Limits::TEXT_LENGTH) {
            throw RichMessageLimitException::exceeded('text length', $this->textLength, Limits::TEXT_LENGTH);
        }

        return $this->render($tokens);
    }

    /**
     * Expand simplified tags into the Telegram vocabulary.
     *
     * @param  array<int, Token>  $tokens
     * @return array<int, Token>
     */
    protected function expand(array $tokens): array
    {
        $out = [];
        $frames = [];

        foreach ($tokens as $token) {
            if ($token->isText()) {
                $out[] = $token;

                continue;
            }

            if ($token->isClose()) {
                $this->closeFrame($token, $frames, $out);

                continue;
            }

            [$name, $attributes] = $this->resolveOpenTag($token);

            $trailing = [];

            if (in_array($name, Tags::CAPTIONABLE, true) && $this->hasCaptionShorthand($token)) {
                [$attributes, $trailing] = $this->extractCaption($attributes, $out);
            }

            $out[] = new Token(Token::OPEN, name: $name, attributes: $attributes, offset: $token->offset);

            $void = Tags::isVoid($name);

            if ($void || $token->selfClosing) {
                if (! $void) {
                    $out[] = new Token(Token::CLOSE, name: $name, offset: $token->offset);
                }

                foreach ($trailing as $extra) {
                    $out[] = $extra;
                }

                continue;
            }

            $frames[] = ['source' => $token->name, 'name' => $name, 'trailing' => $trailing];
        }

        if ($frames !== []) {
            $unclosed = end($frames);

            throw new InvalidRichMarkupException(
                "<{$unclosed['source']}> is never closed in the rich message markup."
            );
        }

        return $out;
    }

    /**
     * Close the innermost open tag, emitting anything queued behind it.
     *
     * @param  Token  $token
     * @param  array<int, array{source: string, name: string, trailing: array<int, Token>}>  $frames
     * @param  array<int, Token>  $out
     * @return void
     */
    protected function closeFrame(Token $token, array &$frames, array &$out): void
    {
        $name = strtolower($token->name);

        // A stray closing tag for a void element is harmless; drop it.
        if (Tags::isVoid(Tags::ALIASES[$name] ?? $name)) {
            return;
        }

        if ($frames === []) {
            throw new InvalidRichMarkupException(
                "</{$token->name}> closes a tag that was never opened."
            );
        }

        $frame = array_pop($frames);

        if ($frame['source'] !== $name) {
            throw new InvalidRichMarkupException(
                "</{$token->name}> does not match the open <{$frame['source']}> tag."
            );
        }

        $out[] = new Token(Token::CLOSE, name: $frame['name'], offset: $token->offset);

        foreach ($frame['trailing'] as $extra) {
            $out[] = $extra;
        }
    }

    /**
     * Resolve an opening tag to its Telegram name and attributes.
     *
     * @param  Token  $token
     * @return array{0: string, 1: array<string, string|true>}
     */
    protected function resolveOpenTag(Token $token): array
    {
        $source = strtolower($token->name);
        $attributes = $token->attributes;

        if ($source === 'math') {
            $block = $token->hasAttribute('block');
            unset($attributes['block']);

            return [$block ? 'tg-math-block' : 'tg-math', $attributes];
        }

        if ($source === 'btn') {
            return ['tg-button', $this->buttonAttributes($token)];
        }

        $name = Tags::ALIASES[$source] ?? $source;

        foreach (Tags::ATTRIBUTE_ALIASES[$name] ?? [] as $from => $to) {
            if (array_key_exists($from, $attributes)) {
                $attributes[$to] = $attributes[$from];
                unset($attributes[$from]);
            }
        }

        return [$name, $attributes];
    }

    /**
     * Build the attributes of a `<tg-button>` from the shorthand spelling.
     *
     * @param  Token  $token
     * @return array<string, string|true>
     */
    protected function buttonAttributes(Token $token): array
    {
        $matched = [];

        foreach (array_keys(self::BUTTON_TYPES) as $shorthand) {
            if ($token->hasAttribute($shorthand)) {
                $matched[] = $shorthand;
            }
        }

        if ($token->hasAttribute('disabled')) {
            $matched[] = 'disabled';
        }

        if ($matched === []) {
            throw new InvalidRichMarkupException(
                '<btn> needs exactly one of: '
                .implode(', ', array_keys(self::BUTTON_TYPES)).', disabled.'
            );
        }

        if (count($matched) > 1) {
            throw new InvalidRichMarkupException(
                '<btn> has more than one type: '.implode(', ', $matched).'. Use exactly one.'
            );
        }

        $kind = $matched[0];

        if ($kind === 'disabled') {
            return $this->withStyle(['type' => 'disabled'], $token);
        }

        [$type, $target] = self::BUTTON_TYPES[$kind];

        $attributes = ['type' => $type, $target => (string) $token->attribute($kind)];

        if ($kind === 'login') {
            if ($token->hasAttribute('forward-text')) {
                $attributes['forward-text'] = (string) $token->attribute('forward-text');
            }

            if ($token->hasAttribute('request-write-access')) {
                $attributes['request-write-access'] = true;
            }
        }

        if ($kind === 'inline-chat') {
            foreach (self::CHAT_FILTERS as $flag => $attribute) {
                if ($token->hasAttribute($flag)) {
                    $attributes[$attribute] = true;
                }
            }
        }

        return $this->withStyle($attributes, $token);
    }

    /**
     * Carry the presentational attributes of a button through unchanged.
     *
     * @param  array<string, string|true>  $attributes
     * @param  Token  $token
     * @return array<string, string|true>
     */
    protected function withStyle(array $attributes, Token $token): array
    {
        foreach (['style', 'icon-custom-emoji-id'] as $attribute) {
            if ($token->hasAttribute($attribute)) {
                $attributes[$attribute] = $token->attributes[$attribute];
            }
        }

        return $attributes;
    }

    /**
     * Determine whether a media tag uses the caption shorthand.
     *
     * @param  Token  $token
     * @return bool
     */
    protected function hasCaptionShorthand(Token $token): bool
    {
        return $token->hasAttribute('caption')
            || $token->hasAttribute('credit')
            || $token->hasAttribute('spoiler');
    }

    /**
     * Peel the caption shorthand off a media tag, opening a `<figure>` in front
     * of it and queueing the matching `<figcaption>` to follow it.
     *
     * @param  array<string, string|true>  $attributes
     * @param  array<int, Token>  $out
     * @return array{0: array<string, string|true>, 1: array<int, Token>}
     */
    protected function extractCaption(array $attributes, array &$out): array
    {
        $caption = is_string($attributes['caption'] ?? null) ? $attributes['caption'] : '';
        $credit = is_string($attributes['credit'] ?? null) ? $attributes['credit'] : '';
        $spoiler = array_key_exists('spoiler', $attributes);

        unset($attributes['caption'], $attributes['credit'], $attributes['spoiler']);

        if ($spoiler) {
            $attributes['tg-spoiler'] = true;
        }

        $out[] = new Token(Token::OPEN, name: 'figure');

        $trailing = [new Token(Token::OPEN, name: 'figcaption')];

        if ($caption !== '') {
            $trailing[] = new Token(Token::TEXT, text: $caption);
        }

        if ($credit !== '') {
            $trailing[] = new Token(Token::OPEN, name: 'cite');
            $trailing[] = new Token(Token::TEXT, text: $credit);
            $trailing[] = new Token(Token::CLOSE, name: 'cite');
        }

        $trailing[] = new Token(Token::CLOSE, name: 'figcaption');
        $trailing[] = new Token(Token::CLOSE, name: 'figure');

        return [$attributes, $trailing];
    }

    /**
     * Check the expanded token stream against the tag vocabulary and the limits.
     *
     * Text tokens come back tagged with whether their whitespace is significant,
     * which the normalisation pass then relies on.
     *
     * @param  array<int, Token>  $tokens
     * @return array<int, Token>
     */
    protected function validate(array $tokens): array
    {
        $stack = [];
        $preserve = 0;
        $buttons = [];
        $columns = [];
        $preserved = [];

        foreach ($tokens as $index => $token) {
            if ($token->isText()) {
                $this->textLength += mb_strlen($token->text, 'UTF-8');
                $preserved[$index] = $preserve > 0;

                continue;
            }

            $name = $token->name;

            if ($token->isOpen()) {
                if ($this->strict && ! Tags::isSupported($name)) {
                    throw UnsupportedRichTagException::tag($name);
                }

                if (! $this->draft && in_array($name, Tags::DRAFT_ONLY, true)) {
                    throw new RichMessageException(
                        "<{$name}> may only be used in a draft. Use @richDraft, "
                        .'or sendRichMessageDraft, to send it.'
                    );
                }

                if (in_array($name, Tags::COUNTS_AS_BLOCK, true) && ++$this->blocks > Limits::BLOCKS) {
                    throw RichMessageLimitException::exceeded('block', $this->blocks, Limits::BLOCKS);
                }

                if ($name === 'tg-button' && $buttons !== []) {
                    $buttons[count($buttons) - 1]++;
                }

                if (($name === 'td' || $name === 'th') && $columns !== []) {
                    $span = max(1, (int) ($token->attribute('colspan') ?? 1));
                    $columns[count($columns) - 1] += $span;

                    if ($columns[count($columns) - 1] > Limits::TABLE_COLUMNS) {
                        throw RichMessageLimitException::exceeded(
                            'table column', $columns[count($columns) - 1], Limits::TABLE_COLUMNS
                        );
                    }
                }

                if (Tags::isVoid($name)) {
                    continue;
                }

                $stack[] = $name;

                if (count($stack) > Limits::NESTING) {
                    throw RichMessageLimitException::exceeded('nesting', count($stack), Limits::NESTING);
                }

                if (in_array($name, Tags::PRESERVES_WHITESPACE, true)) {
                    $preserve++;
                }

                if ($name === 'tg-button-row') {
                    $buttons[] = 0;
                }

                if ($name === 'tr') {
                    $columns[] = 0;
                }

                continue;
            }

            array_pop($stack);

            if (in_array($name, Tags::PRESERVES_WHITESPACE, true)) {
                $preserve = max(0, $preserve - 1);
            }

            if ($name === 'tg-button-row') {
                $count = (int) array_pop($buttons);

                if ($count < Limits::ROW_BUTTONS_MIN || $count > Limits::ROW_BUTTONS_MAX) {
                    throw new InvalidRichMarkupException(
                        "A button row holds {$count} buttons; Telegram allows "
                        .Limits::ROW_BUTTONS_MIN.'-'.Limits::ROW_BUTTONS_MAX.'.'
                    );
                }
            }

            if ($name === 'tr') {
                array_pop($columns);
            }
        }

        foreach ($preserved as $index => $significant) {
            if ($significant) {
                $tokens[$index] = new Token(
                    Token::TEXT,
                    text: $tokens[$index]->text,
                    offset: $tokens[$index]->offset,
                    preserve: true,
                );
            }
        }

        return $tokens;
    }

    /**
     * Collapse the whitespace a template's indentation leaves behind.
     *
     * Text inside `<pre>`, `<code>` and formulas is left exactly as written; the
     * validation pass marks those tokens with their `preserve` flag.
     *
     * @param  array<int, Token>  $tokens
     * @return array<int, Token>
     */
    protected function normaliseWhitespace(array $tokens): array
    {
        $tokens = array_values($tokens);
        $collapsed = [];

        foreach ($tokens as $token) {
            if (! $token->isText() || $token->preserve) {
                $collapsed[] = $token;

                continue;
            }

            $collapsed[] = new Token(
                Token::TEXT,
                text: preg_replace('/\s+/u', ' ', $token->text) ?? $token->text,
                offset: $token->offset,
            );
        }

        $out = [];
        $count = count($collapsed);

        foreach ($collapsed as $index => $token) {
            if (! $token->isText() || $token->preserve) {
                $out[] = $token;

                continue;
            }

            if (trim($token->text) === '') {
                // A run of pure whitespace only survives between two inline tags.
                if (! $this->touchesBlockBoundary($collapsed, $index, $count)) {
                    $out[] = $token;
                }

                continue;
            }

            $out[] = new Token(
                Token::TEXT,
                text: $this->trimAtBoundaries($collapsed, $index, $count, $token->text),
                offset: $token->offset,
            );
        }

        return $out;
    }

    /**
     * Drop the padding a block boundary leaves at the edges of a text run.
     *
     * @param  array<int, Token>  $tokens
     * @param  int  $index
     * @param  int  $count
     * @param  string  $text
     * @return string
     */
    protected function trimAtBoundaries(array $tokens, int $index, int $count, string $text): string
    {
        $before = $index > 0 ? $tokens[$index - 1] : null;
        $after = $index + 1 < $count ? $tokens[$index + 1] : null;

        if ($before === null || (! $before->isText() && Tags::isBlockLevel($before->name))) {
            $text = ltrim($text);
        }

        if ($after === null || (! $after->isText() && Tags::isBlockLevel($after->name))) {
            $text = rtrim($text);
        }

        return $text;
    }

    /**
     * Determine whether a whitespace-only run sits next to a block boundary.
     *
     * @param  array<int, Token>  $tokens
     * @param  int  $index
     * @param  int  $count
     * @return bool
     */
    protected function touchesBlockBoundary(array $tokens, int $index, int $count): bool
    {
        $before = $index > 0 ? $tokens[$index - 1] : null;
        $after = $index + 1 < $count ? $tokens[$index + 1] : null;

        if ($before === null || $after === null) {
            return true;
        }

        // Buttons sit side by side; the gap between two of them is never text.
        if ($before->isClose() && $before->name === 'tg-button'
            && $after->isOpen() && $after->name === 'tg-button') {
            return true;
        }

        return (! $before->isText() && Tags::isBlockLevel($before->name))
            || (! $after->isText() && Tags::isBlockLevel($after->name));
    }

    /**
     * Render the token stream back into a string.
     *
     * @param  array<int, Token>  $tokens
     * @return string
     */
    protected function render(array $tokens): string
    {
        $html = '';

        foreach ($tokens as $token) {
            if ($token->isText()) {
                $html .= $this->encode($token->text);

                continue;
            }

            if ($token->isClose()) {
                $html .= '</'.$token->name.'>';

                continue;
            }

            $html .= '<'.$token->name;

            foreach ($token->attributes as $name => $value) {
                $html .= $value === true
                    ? ' '.$name
                    : ' '.$name.'="'.$this->encode((string) $value).'"';
            }

            $html .= in_array($token->name, self::SELF_CLOSED, true) ? '/>' : '>';
        }

        return $html;
    }

    /**
     * Escape a value using only the entities Telegram understands.
     *
     * @param  string  $value
     * @return string
     */
    protected function encode(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

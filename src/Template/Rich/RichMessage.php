<?php

namespace LaraGram\Template\Rich;

use LaraGram\Template\Rich\Exceptions\RichMessageException;
use LaraGram\Template\Rich\Html\Transpiler;

/**
 * Builds the `rich_message` payload of sendRichMessage and sendRichMessageDraft.
 *
 * @see https://core.telegram.org/bots/api#inputrichmessage
 */
class RichMessage
{
    /**
     * The markup collected so far, before expansion.
     *
     * @var string
     */
    protected string $markup = '';

    /**
     * The media referenced by the markup.
     *
     * @var \LaraGram\Template\Rich\MediaBag
     */
    protected MediaBag $bag;

    /**
     * Whether the message renders right-to-left.
     *
     * @var bool|null
     */
    protected ?bool $rtl = null;

    /**
     * Whether Telegram should skip automatic entity detection.
     *
     * @var bool|null
     */
    protected ?bool $skipEntityDetection = null;

    /**
     * Whether unsupported tags are rejected instead of passed through.
     *
     * @var bool
     */
    protected bool $strict = true;

    /**
     * Whether template indentation is normalised away.
     *
     * @var bool
     */
    protected bool $pretty = true;

    /**
     * Whether draft-only tags are allowed.
     *
     * @var bool
     */
    protected bool $draft = false;

    /**
     * Create a new rich message builder.
     *
     * @param  \LaraGram\Template\Rich\MediaBag|null  $media
     */
    public function __construct(?MediaBag $media = null)
    {
        $this->bag = $media ?? new MediaBag();
    }

    /**
     * Create a new rich message builder.
     *
     * @return static
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * Append raw markup.
     *
     * @param  string  $markup
     * @return $this
     */
    public function append(string $markup): static
    {
        $this->markup .= $markup;

        return $this;
    }

    /**
     * Append text, escaped so it is never interpreted as markup.
     *
     * @param  string  $text
     * @return $this
     */
    public function text(string $text): static
    {
        return $this->append(static::escape($text));
    }

    /**
     * Render the message right-to-left.
     *
     * @param  bool  $rtl
     * @return $this
     */
    public function rtl(bool $rtl = true): static
    {
        $this->rtl = $rtl;

        return $this;
    }

    /**
     * Stop Telegram from auto-linking URLs, mentions, hashtags and the like.
     *
     * @param  bool  $skip
     * @return $this
     */
    public function skipEntityDetection(bool $skip = true): static
    {
        $this->skipEntityDetection = $skip;

        return $this;
    }

    /**
     * Allow tags Telegram does not document to pass through untouched.
     *
     * @param  bool  $strict
     * @return $this
     */
    public function strict(bool $strict = true): static
    {
        $this->strict = $strict;

        return $this;
    }

    /**
     * Send the markup byte-for-byte instead of normalising its whitespace.
     *
     * @param  bool  $pretty
     * @return $this
     */
    public function pretty(bool $pretty = true): static
    {
        $this->pretty = $pretty;

        return $this;
    }

    /**
     * Allow draft-only tags such as `<thinking>`.
     *
     * @param  bool  $draft
     * @return $this
     */
    public function draft(bool $draft = true): static
    {
        $this->draft = $draft;

        return $this;
    }

    /**
     * Register a file and get back the tg:// link that references it.
     *
     * @param  mixed  $file
     * @param  string  $type
     * @param  string|null  $id
     * @param  array<string, mixed>  $options
     * @return string
     */
    public function media(mixed $file, string $type, ?string $id = null, array $options = []): string
    {
        return $this->bag->add($file, $type, $id, $options);
    }

    /**
     * Append a media block for a file that is not reachable by a public URL.
     *
     * @param  mixed  $file
     * @param  string  $type
     * @param  string|null  $caption
     * @param  string|null  $credit
     * @param  bool  $spoiler
     * @param  string|null  $id
     * @param  array<string, mixed>  $options
     * @return $this
     */
    public function attach(
        mixed $file,
        string $type,
        ?string $caption = null,
        ?string $credit = null,
        bool $spoiler = false,
        ?string $id = null,
        array $options = [],
    ): static {
        $source = $this->bag->add($file, $type, $id, $options);
        $tag = MediaBag::tagFor($type);

        $attributes = ' src="'.static::escape($source).'"';

        if ($caption !== null) {
            $attributes .= ' caption="'.static::escape($caption).'"';
        }

        if ($credit !== null) {
            $attributes .= ' credit="'.static::escape($credit).'"';
        }

        if ($spoiler) {
            $attributes .= ' spoiler';
        }

        return $this->append(
            $tag === 'img' ? "<img{$attributes}/>" : "<{$tag}{$attributes}></{$tag}>"
        );
    }

    /**
     * Append a photo block.
     *
     * @param  mixed  $file
     * @param  string|null  $caption
     * @param  string|null  $credit
     * @param  bool  $spoiler
     * @param  string|null  $id
     * @return $this
     */
    public function photo(mixed $file, ?string $caption = null, ?string $credit = null, bool $spoiler = false, ?string $id = null): static
    {
        return $this->attach($file, 'photo', $caption, $credit, $spoiler, $id);
    }

    /**
     * Append a video block.
     *
     * @param  mixed  $file
     * @param  string|null  $caption
     * @param  string|null  $credit
     * @param  bool  $spoiler
     * @param  string|null  $id
     * @return $this
     */
    public function video(mixed $file, ?string $caption = null, ?string $credit = null, bool $spoiler = false, ?string $id = null): static
    {
        return $this->attach($file, 'video', $caption, $credit, $spoiler, $id);
    }

    /**
     * Append an animation block.
     *
     * @param  mixed  $file
     * @param  string|null  $caption
     * @param  string|null  $credit
     * @param  bool  $spoiler
     * @param  string|null  $id
     * @return $this
     */
    public function animation(mixed $file, ?string $caption = null, ?string $credit = null, bool $spoiler = false, ?string $id = null): static
    {
        return $this->attach($file, 'animation', $caption, $credit, $spoiler, $id);
    }

    /**
     * Append an audio block.
     *
     * @param  mixed  $file
     * @param  string|null  $caption
     * @param  string|null  $credit
     * @param  string|null  $id
     * @return $this
     */
    public function audio(mixed $file, ?string $caption = null, ?string $credit = null, ?string $id = null): static
    {
        return $this->attach($file, 'audio', $caption, $credit, false, $id);
    }

    /**
     * Append a voice note block.
     *
     * @param  mixed  $file
     * @param  string|null  $caption
     * @param  string|null  $credit
     * @param  string|null  $id
     * @return $this
     */
    public function voice(mixed $file, ?string $caption = null, ?string $credit = null, ?string $id = null): static
    {
        return $this->attach($file, 'voice_note', $caption, $credit, false, $id);
    }

    /**
     * Append a document block.
     *
     * @param  mixed  $file
     * @param  string|null  $caption
     * @param  string|null  $credit
     * @param  string|null  $id
     * @return $this
     */
    public function document(mixed $file, ?string $caption = null, ?string $credit = null, ?string $id = null): static
    {
        return $this->attach($file, 'document', $caption, $credit, false, $id);
    }

    /**
     * Append a list built from a PHP array.
     *
     * @param  iterable<mixed>  $items
     * @param  bool  $ordered
     * @param  string|null  $type  Ordered list label style: a, A, i, I or 1.
     * @param  int|null  $start
     * @param  bool  $reversed
     * @return $this
     */
    public function list(iterable $items, bool $ordered = false, ?string $type = null, ?int $start = null, bool $reversed = false): static
    {
        $tag = $ordered ? 'ol' : 'ul';
        $open = '<'.$tag;

        if ($ordered) {
            if ($type !== null) {
                $open .= ' type="'.static::escape($type).'"';
            }

            if ($start !== null) {
                $open .= ' start="'.(int) $start.'"';
            }

            if ($reversed) {
                $open .= ' reversed';
            }
        }

        $html = $open.'>';

        foreach ($items as $item) {
            $html .= '<li>'.static::escape((string) $item).'</li>';
        }

        return $this->append($html.'</'.$tag.'>');
    }

    /**
     * Append a checklist, where each key is a label and each value its state.
     *
     * @param  iterable<string, bool>  $items
     * @return $this
     */
    public function checklist(iterable $items): static
    {
        $html = '<ul>';

        foreach ($items as $label => $checked) {
            $html .= '<li><input type="checkbox"'.($checked ? ' checked' : '').'>'
                .static::escape((string) $label).'</li>';
        }

        return $this->append($html.'</ul>');
    }

    /**
     * Append a table built from a PHP array of rows.
     *
     * @param  iterable<int, iterable<mixed>>  $rows
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $align  Per-column alignment: left, center or right.
     * @param  bool  $bordered
     * @param  bool  $striped
     * @param  bool  $compact
     * @param  string|null  $caption
     * @return $this
     */
    public function table(
        iterable $rows,
        array $headers = [],
        array $align = [],
        bool $bordered = false,
        bool $striped = false,
        bool $compact = false,
        ?string $caption = null,
    ): static {
        $html = '<table'
            .($bordered ? ' bordered' : '')
            .($striped ? ' striped' : '')
            .($compact ? ' compact' : '')
            .'>';

        if ($caption !== null) {
            $html .= '<caption>'.static::escape($caption).'</caption>';
        }

        if ($headers !== []) {
            $html .= '<tr>';

            foreach (array_values($headers) as $column => $header) {
                $html .= '<th'.$this->alignment($align, $column).'>'
                    .static::escape((string) $header).'</th>';
            }

            $html .= '</tr>';
        }

        foreach ($rows as $row) {
            $html .= '<tr>';

            foreach (array_values(is_array($row) ? $row : iterator_to_array($row)) as $column => $cell) {
                $html .= '<td'.$this->alignment($align, $column).'>'
                    .static::escape((string) $cell).'</td>';
            }

            $html .= '</tr>';
        }

        return $this->append($html.'</table>');
    }

    /**
     * Build the alignment attribute of a table cell.
     *
     * @param  array<int, string>  $align
     * @param  int  $column
     * @return string
     */
    protected function alignment(array $align, int $column): string
    {
        $value = array_values($align)[$column] ?? null;

        return $value === null ? '' : ' align="'.static::escape($value).'"';
    }

    /**
     * Compile the markup and get the finished `rich_message` payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = ['html' => $this->toHtml()];

        if (! $this->bag->isEmpty()) {
            $payload['media'] = $this->bag->toArray();
        }

        if ($this->rtl !== null) {
            $payload['is_rtl'] = $this->rtl;
        }

        if ($this->skipEntityDetection !== null) {
            $payload['skip_entity_detection'] = $this->skipEntityDetection;
        }

        return $payload;
    }

    /**
     * Compile the markup into the HTML Telegram expects.
     *
     * @return string
     */
    public function toHtml(): string
    {
        return (new Transpiler($this->strict, $this->pretty, $this->draft))
            ->transpile($this->markup);
    }

    /**
     * Get the media registry backing this message.
     *
     * @return \LaraGram\Template\Rich\MediaBag
     */
    public function mediaBag(): MediaBag
    {
        return $this->bag;
    }

    /**
     * Escape a value so it is treated as text rather than markup.
     *
     * @param  string|null  $value
     * @return string
     */
    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Get the finished payload as JSON.
     *
     * @return string
     */
    public function __toString(): string
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RichMessageException('The rich message payload could not be encoded as JSON.');
        }

        return $json;
    }
}

<?php

declare(strict_types=1);

namespace LaraGram\Console\Prompts\Convertor;

use DOMDocument;
use DOMNode;
use LaraGram\Console\Prompts\Convertor\Html\CodeRenderer;
use LaraGram\Console\Prompts\Convertor\Html\PreRenderer;
use LaraGram\Console\Prompts\Convertor\Html\TableRenderer;
use LaraGram\Console\Prompts\Convertor\ValueObjects\Node;

/**
 * @internal
 */
final class HtmlRenderer
{
    /**
     * Renders the given html.
     */
    public function render(string $html, int $options): void
    {        
        $this->parse($html)->render($options);
    }

    /**
     * Parses the given html.
     */
    public function parse(string $html): Components\Element
    {
        $dom = new DOMDocument;

        if (strip_tags($html) === $html) {
            return Convertor::span($html);
        }

        $html = '<?xml encoding="UTF-8">'.trim($html);
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_COMPACT | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS | LIBXML_NOXMLDECL);

        /** @var DOMNode $body */
        $body = $dom->getElementsByTagName('body')->item(0);
        $el = $this->convert(new Node($body));

        // @codeCoverageIgnoreStart
        return is_string($el)
            ? Convertor::span($el)
            : $el;
        // @codeCoverageIgnoreEnd
    
    }

    /**
     * Convert a tree of DOM nodes to a tree of Convertor elements.
     */
    private function convert(Node $node): Components\Element|string
    {
        $children = [];

        if ($node->isName('table')) {
            return (new TableRenderer)->toElement($node);
        } elseif ($node->isName('code')) {
            return (new CodeRenderer)->toElement($node);
        } elseif ($node->isName('pre')) {
            return (new PreRenderer)->toElement($node);
        }

        foreach ($node->getChildNodes() as $child) {
            $children[] = $this->convert($child);
        }

        $children = array_filter($children, fn ($child) => $child !== '');

        return $this->toElement($node, $children);
    }

    /**
     * Convert a given DOM node to it's Convertor element equivalent.
     *
     * @param  array<int, Components\Element|string>  $children
     */
    private function toElement(Node $node, array $children): Components\Element|string
    {
        if ($node->isText() || $node->isComment()) {
            return (string) $node;
        }

        /** @var array<string, mixed> $properties */
        $properties = [
            'isFirstChild' => $node->isFirstChild(),
        ];

        $styles = $node->getClassAttribute();

        return match ($node->getName()) {
            'body' => $children[0], // Pick only the first element from the body node
            'div' => Convertor::div($children, $styles, $properties),
            'p' => Convertor::paragraph($children, $styles, $properties),
            'ul' => Convertor::ul($children, $styles, $properties),
            'ol' => Convertor::ol($children, $styles, $properties),
            'li' => Convertor::li($children, $styles, $properties),
            'dl' => Convertor::dl($children, $styles, $properties),
            'dt' => Convertor::dt($children, $styles, $properties),
            'dd' => Convertor::dd($children, $styles, $properties),
            'span' => Convertor::span($children, $styles, $properties),
            'br' => Convertor::breakLine($styles, $properties),
            'strong' => Convertor::span($children, $styles, $properties)->strong(),
            'b' => Convertor::span($children, $styles, $properties)->fontBold(),
            'em', 'i' => Convertor::span($children, $styles, $properties)->italic(),
            'u' => Convertor::span($children, $styles, $properties)->underline(),
            's' => Convertor::span($children, $styles, $properties)->lineThrough(),
            'a' => Convertor::anchor($children, $styles, $properties)->href($node->getAttribute('href')),
            'hr' => Convertor::hr($styles, $properties),
            default => Convertor::div($children, $styles, $properties),
        };
    }
}

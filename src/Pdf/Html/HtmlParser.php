<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf\Html;

/**
 * Class to parse HTML content.
 */
readonly class HtmlParser
{
    /**
     * Constructor.
     *
     * @param string $html the HTML content to parse
     */
    public function __construct(private string $html)
    {
    }

    /**
     * Parses this HTML content and return the root parent.
     *
     * @return ?HtmlParentChunk the root parent, if success; <code>null</code> otherwise
     */
    public function parse(): ?HtmlParentChunk
    {
        if (false === $source = $this->trimHtml()) {
            return null;
        }

        // load content
        $document = new \DOMDocument();
        if (!$document->loadHTML($source, \LIBXML_NOERROR | \LIBXML_NOBLANKS)) {
            return null;
        }

        // find body
        if (!($body = $this->findBody($document)) instanceof \DOMNode) {
            return null;
        }

        // parse
        $root = new HtmlParentChunk($body->nodeName);
        $this->parseNodes($root, $body);

        return $root->isEmpty() ? null : $root;
    }

    /**
     * Creates an HTML line break chunk.
     */
    private function createBrChunk(string $name, HtmlParentChunk $parent, ?string $class): void
    {
        $chunk = new HtmlBrChunk($name, $parent);
        $chunk->setClassName($class);
    }

    /**
     * Creates an HTML list item chunk.
     */
    private function createLiChunk(string $name, HtmlParentChunk $parent, ?string $class): HtmlLiChunk
    {
        $chunk = new HtmlLiChunk($name, $parent);
        $chunk->setClassName($class);

        return $chunk;
    }

    /**
     * Creates an HTML ordered list chunk.
     */
    private function createOlChunk(string $name, HtmlParentChunk $parent, ?string $class, \DOMNode $node): HtmlOlChunk
    {
        $chunk = new HtmlOlChunk($name, $parent);
        $chunk->setClassName($class)
            ->setType($this->getTypeAttribute($node))
            ->setStart($this->getStartAttribute($node));

        return $chunk;
    }

    /**
     * Creates an HTML page break chunk.
     */
    private function createPageBreakChunk(string $name, HtmlParentChunk $parent): void
    {
        new HtmlPageBreakChunk($name, $parent);
    }

    /**
     * Creates an HTML parent chunk.
     */
    private function createParentChunk(string $name, HtmlParentChunk $parent, ?string $class): HtmlParentChunk
    {
        $chunk = new HtmlParentChunk($name, $parent);
        $chunk->setClassName($class);

        return $chunk;
    }

    /**
     * Creates an HTML text chunk.
     */
    private function createTextChunk(string $name, HtmlParentChunk $parent, ?string $class, \DOMNode $node): void
    {
        if (!$node instanceof \DOMText) {
            return;
        }
        $text = $node->wholeText;
        if ('' === $text || (' ' === $text && $parent->isEmpty())) {
            return;
        }
        $chunk = new HtmlTextChunk($name, $parent);
        $chunk->setClassName($class)
            ->setText($text);
    }

    /**
     * Creates an HTML unordered list chunk.
     */
    private function createUlChunk(string $name, HtmlParentChunk $parent, ?string $class): HtmlUlChunk
    {
        $chunk = new HtmlUlChunk($name, $parent);
        $chunk->setClassName($class);

        return $chunk;
    }

    /**
     * Finds the body element.
     */
    private function findBody(\DOMDocument $dom): ?\DOMNode
    {
        $bodies = $dom->getElementsByTagName('body');
        if (0 !== $bodies->length) {
            return $bodies->item(0);
        }

        return null;
    }

    /**
     * Gets an attribute value for the given node.
     *
     * @psalm-return ($default is null ? (string|null) : string)
     */
    private function getAttribute(\DOMNode $node, string $name, string $default = null): ?string
    {
        if (!$node->hasAttributes()) {
            return $default;
        }

        /** @var \DOMNamedNodeMap $attributes */
        $attributes = $node->attributes;
        if (!($attribute = $attributes->getNamedItem($name)) instanceof \DOMNode) {
            return $default;
        }

        $value = \trim((string) $attribute->nodeValue);
        if ('' === $value) {
            return $default;
        }

        return $value;
    }

    /**
     * Gets the class attribute value for the given node.
     */
    private function getClassAttribute(\DOMNode $node): ?string
    {
        return $this->getAttribute($node, 'class');
    }

    /**
     * Gets the start attribute value for the given node.
     */
    private function getStartAttribute(\DOMNode $node): int
    {
        return (int) $this->getAttribute($node, 'start', '1');
    }

    /**
     * Gets the list type attribute value for the given node.
     */
    private function getTypeAttribute(\DOMNode $node): HtmlListType
    {
        $default = HtmlListType::NUMBER;
        $value = $this->getAttribute($node, 'type', $default->value);

        return HtmlListType::tryFrom($value) ?? $default;
    }

    /**
     * Parse a node and it's children (if any).
     */
    private function parseNode(HtmlParentChunk $parent, \DOMNode $node): void
    {
        $name = $node->nodeName;
        $class = $this->getClassAttribute($node);
        switch ($node->nodeType) {
            case \XML_ELEMENT_NODE:
                if (HtmlConstantsInterface::PAGE_BREAK === $class) {
                    $this->createPageBreakChunk($name, $parent);
                } elseif (HtmlConstantsInterface::LINE_BREAK === $name) {
                    $this->createBrChunk($name, $parent, $class);
                } elseif (HtmlConstantsInterface::LIST_ITEM === $name) {
                    $parent = $this->createLiChunk($name, $parent, $class);
                } elseif (HtmlConstantsInterface::LIST_ORDERED === $name) {
                    $parent = $this->createOlChunk($name, $parent, $class, $node);
                } elseif (HtmlConstantsInterface::LIST_UNORDERED === $name) {
                    $parent = $this->createUlChunk($name, $parent, $class);
                } else {
                    $parent = $this->createParentChunk($name, $parent, $class);
                }
                break;
            case \XML_TEXT_NODE:
                $this->createTextChunk($name, $parent, $class, $node);
                break;
        }
        $this->parseNodes($parent, $node);
    }

    /**
     * Parse the children nodes. Do nothing if node has no children.
     */
    private function parseNodes(HtmlParentChunk $parent, \DOMNode $node): void
    {
        if (!$node->hasChildNodes()) {
            return;
        }
        foreach ($node->childNodes as $child) {
            $this->parseNode($parent, $child);
        }
    }

    /**
     * Gets the clean HTML content.
     *
     * @psalm-return non-empty-string|false
     */
    private function trimHtml(): string|false
    {
        if ('' === $content = \trim($this->html)) {
            return false;
        }
        if ('' === $content = \trim(\preg_replace('/\r\n|\n|\r/m', '', $content))) {
            return false;
        }
        if ('' === $content = \trim(\preg_replace('/\s\s+/m', ' ', $content))) {
            return false;
        }

        return "<?xml encoding='UTF-8'>$content";
    }
}

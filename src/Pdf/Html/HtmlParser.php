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
     * @return HtmlParentChunk|false the root parent, if success; <code>false</code> otherwise
     */
    public function parse(): HtmlParentChunk|false
    {
        $source = $this->trimHtml();
        if (false === $source) {
            return false;
        }
        $document = $this->loadDocument($source);
        if (false === $document) {
            return false;
        }
        $body = $this->findBody($document);
        if (false === $body) {
            return false;
        }

        $root = new HtmlParentChunk($body->nodeName);
        $this->parseNodes($root, $body);

        return $root->isEmpty() ? false : $root;
    }

    /**
     * Creates an HTML line break chunk.
     */
    private function createBrChunk(string $name, HtmlParentChunk $parent, ?string $className): void
    {
        new HtmlBrChunk($name, $parent, $className);
    }

    /**
     * Creates an HTML list item chunk.
     */
    private function createLiChunk(string $name, HtmlParentChunk $parent, ?string $className): HtmlLiChunk
    {
        return new HtmlLiChunk($name, $parent, $className);
    }

    /**
     * Creates an HTML ordered list chunk.
     */
    private function createOlChunk(string $name, HtmlParentChunk $parent, ?string $className, \DOMNode $node): HtmlOlChunk
    {
        $chunk = new HtmlOlChunk($name, $parent, $className);
        $chunk->setType($this->getTypeAttribute($node))
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
    private function createParentChunk(string $name, HtmlParentChunk $parent, ?string $className): HtmlParentChunk
    {
        return new HtmlParentChunk($name, $parent, $className);
    }

    /**
     * Creates an HTML text chunk.
     */
    private function createTextChunk(string $name, HtmlParentChunk $parent, ?string $className, \DOMNode $node): void
    {
        if (!$node instanceof \DOMText) {
            return;
        }
        $text = $node->wholeText;
        if ('' === $text || (' ' === $text && $parent->isEmpty())) {
            return;
        }
        $chunk = new HtmlTextChunk($name, $parent, $className);
        $chunk->setText($text);
    }

    /**
     * Creates an HTML unordered list chunk.
     */
    private function createUlChunk(string $name, HtmlParentChunk $parent, ?string $className): HtmlUlChunk
    {
        return new HtmlUlChunk($name, $parent, $className);
    }

    /**
     * Finds the body element.
     */
    private function findBody(\DOMDocument $document): \DOMNode|false
    {
        $bodies = $document->getElementsByTagName(HtmlConstantsInterface::BODY);
        if (0 !== $bodies->length) {
            return $bodies->item(0) ?? false;
        }

        return false;
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
        $attribute = $attributes->getNamedItem($name);
        if (!$attribute instanceof \DOMNode) {
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
        return $this->getAttribute($node, HtmlConstantsInterface::CLASS_ATTRIBUTE);
    }

    /**
     * Gets the start attribute value for the given node.
     */
    private function getStartAttribute(\DOMNode $node): int
    {
        return (int) $this->getAttribute($node, HtmlConstantsInterface::START_ATTRIBUTE, '1');
    }

    /**
     * Gets the list type attribute value for the given node.
     */
    private function getTypeAttribute(\DOMNode $node): HtmlListType
    {
        $default = HtmlListType::NUMBER;
        $value = $this->getAttribute($node, HtmlConstantsInterface::TYPE_ATTRIBUTE, $default->value);

        return HtmlListType::tryFrom($value) ?? $default;
    }

    /**
     * Load the given HTML.
     */
    private function loadDocument(string $source): \DOMDocument|false
    {
        $document = new \DOMDocument();
        if ($document->loadHTML($source, \LIBXML_NOERROR | \LIBXML_NOBLANKS)) {
            return $document;
        }

        return false;
    }

    /**
     * Parse a node and it's children (if any).
     */
    private function parseNode(HtmlParentChunk $parent, \DOMNode $node): void
    {
        $name = $node->nodeName;
        $className = $this->getClassAttribute($node);
        switch ($node->nodeType) {
            case \XML_ELEMENT_NODE:
                if (HtmlConstantsInterface::PAGE_BREAK === $className) {
                    $this->createPageBreakChunk($name, $parent);
                } elseif (HtmlConstantsInterface::LINE_BREAK === $name) {
                    $this->createBrChunk($name, $parent, $className);
                } elseif (HtmlConstantsInterface::LIST_ITEM === $name) {
                    $parent = $this->createLiChunk($name, $parent, $className);
                } elseif (HtmlConstantsInterface::LIST_ORDERED === $name) {
                    $parent = $this->createOlChunk($name, $parent, $className, $node);
                } elseif (HtmlConstantsInterface::LIST_UNORDERED === $name) {
                    $parent = $this->createUlChunk($name, $parent, $className);
                } else {
                    $parent = $this->createParentChunk($name, $parent, $className);
                }
                break;
            case \XML_TEXT_NODE:
                $this->createTextChunk($name, $parent, $className, $node);
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
        $content = \trim($this->html);
        if ('' === $content) {
            return false;
        }
        $content = \trim((string) \preg_replace('/\r\n|\n|\r/m', '', $content));
        if ('' === $content) {
            return false;
        }
        $content = \trim((string) \preg_replace('/\s\s+/m', ' ', $content));
        if ('' === $content) {
            return false;
        }

        return "<?xml encoding='UTF-8'>$content";
    }
}

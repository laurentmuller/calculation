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

    private function createBrChunk(string $name, HtmlParentChunk $parent, ?string $className): HtmlParentChunk
    {
        new HtmlBrChunk($name, $parent, $className);

        return $parent;
    }

    private function createLiChunk(string $name, HtmlParentChunk $parent, ?string $className): HtmlLiChunk
    {
        return new HtmlLiChunk($name, $parent, $className);
    }

    private function createOlChunk(string $name, HtmlParentChunk $parent, ?string $className, \DOMNode $node): HtmlOlChunk
    {
        $chunk = new HtmlOlChunk($name, $parent, $className);
        $chunk->setType($this->getTypeAttribute($node))
            ->setStart($this->getStartAttribute($node));

        return $chunk;
    }

    private function createPageBreakChunk(string $name, HtmlParentChunk $parent): HtmlParentChunk
    {
        new HtmlPageBreakChunk($name, $parent);

        return $parent;
    }

    private function createParentChunk(string $name, HtmlParentChunk $parent, ?string $className): HtmlParentChunk
    {
        return new HtmlParentChunk($name, $parent, $className);
    }

    private function createUlChunk(string $name, HtmlParentChunk $parent, ?string $className): HtmlUlChunk
    {
        return new HtmlUlChunk($name, $parent, $className);
    }

    private function findBody(\DOMDocument $document): \DOMNode|false
    {
        $bodies = $document->getElementsByTagName(HtmlConstantsInterface::BODY);
        if (0 !== $bodies->length) {
            return $bodies->item(0) ?? false;
        }

        return false;
    }

    /**
     * @psalm-return ($default is null ? (string|null) : string)
     */
    private function getAttribute(\DOMNode $node, string $name, string $default = null): ?string
    {
        if (!$node->attributes instanceof \DOMNamedNodeMap) {
            return $default;
        }

        $attribute = $node->attributes->getNamedItem($name);
        if (!$attribute instanceof \DOMNode || null === $attribute->nodeValue) {
            return $default;
        }

        $value = \trim($attribute->nodeValue);
        if ('' === $value) {
            return $default;
        }

        return $value;
    }

    private function getClassAttribute(\DOMNode $node): ?string
    {
        return $this->getAttribute($node, HtmlConstantsInterface::CLASS_ATTRIBUTE);
    }

    private function getStartAttribute(\DOMNode $node): int
    {
        return (int) $this->getAttribute($node, HtmlConstantsInterface::START_ATTRIBUTE, '1');
    }

    private function getTypeAttribute(\DOMNode $node): HtmlListType
    {
        $default = HtmlListType::NUMBER;
        $value = $this->getAttribute($node, HtmlConstantsInterface::TYPE_ATTRIBUTE, $default->value);

        return HtmlListType::tryFrom($value) ?? $default;
    }

    private function loadDocument(string $source): \DOMDocument|false
    {
        $document = new \DOMDocument();
        if ($document->loadHTML($source, \LIBXML_NOERROR | \LIBXML_NOBLANKS)) {
            return $document;
        }

        return false;
    }

    private function parseNode(HtmlParentChunk $parent, \DOMNode $node): void
    {
        $name = $node->nodeName;
        $className = $this->getClassAttribute($node);
        switch ($node->nodeType) {
            case \XML_ELEMENT_NODE:
                $parent = $this->parseNodeElement($parent, $node, $name, $className);
                break;
            case \XML_TEXT_NODE:
                $this->parseNodeText($name, $parent, $className, $node);
                break;
        }
        $this->parseNodes($parent, $node);
    }

    private function parseNodeElement(HtmlParentChunk $parent, \DOMNode $node, string $name, ?string $className): HtmlParentChunk
    {
        if (HtmlConstantsInterface::PAGE_BREAK === $className) {
            return $this->createPageBreakChunk($name, $parent);
        }

        return match ($name) {
            HtmlConstantsInterface::LINE_BREAK => $this->createBrChunk($name, $parent, $className),
            HtmlConstantsInterface::LIST_ITEM => $this->createLiChunk($name, $parent, $className),
            HtmlConstantsInterface::LIST_ORDERED => $this->createOlChunk($name, $parent, $className, $node),
            HtmlConstantsInterface::LIST_UNORDERED => $this->createUlChunk($name, $parent, $className),
            default => $this->createParentChunk($name, $parent, $className)
        };
    }

    private function parseNodes(HtmlParentChunk $parent, \DOMNode $node): void
    {
        if (!$node->hasChildNodes()) {
            return;
        }
        foreach ($node->childNodes as $child) {
            $this->parseNode($parent, $child);
        }
    }

    private function parseNodeText(string $name, HtmlParentChunk $parent, ?string $className, \DOMNode $node): void
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

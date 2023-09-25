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
     * @return ?HtmlParentChunk the root parent, if success; null otherwise
     */
    public function parse(): ?HtmlParentChunk
    {
        $source = $this->trimHtml();
        if (null === $source) {
            return null;
        }
        $document = $this->loadDocument($source);
        if (!$document instanceof \DOMDocument) {
            return null;
        }
        $body = HtmlTag::BODY->findFirst($document);
        if (!$body instanceof \DOMNode) {
            return null;
        }

        $root = new HtmlParentChunk($body->nodeName);
        $this->parseNodes($root, $body);

        return $root->isEmpty() ? null : $root;
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
        $type = HtmlAttribute::LIST_TYPE->getEnumValue($node, HtmlListType::NUMBER);
        $start = HtmlAttribute::LIST_START->getIntValue($node, 1);
        $chunk = new HtmlOlChunk($name, $parent, $className);

        return $chunk->setType($type)
            ->setStart($start);
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

    private function loadDocument(string $source): ?\DOMDocument
    {
        $document = new \DOMDocument();
        if ($document->loadHTML($source, \LIBXML_NOERROR | \LIBXML_NOBLANKS)) {
            return $document;
        }

        return null;
    }

    private function parseNode(HtmlParentChunk $parent, \DOMNode $node): void
    {
        $name = $node->nodeName;
        $className = HtmlAttribute::CLASS_NAME->getValue($node);
        switch ($node->nodeType) {
            case \XML_ELEMENT_NODE:
                $parent = $this->parseNodeElement($name, $parent, $className, $node);
                break;
            case \XML_TEXT_NODE:
                $this->parseNodeText($name, $parent, $className, $node);
                break;
        }
        $this->parseNodes($parent, $node);
    }

    private function parseNodeElement(string $name, HtmlParentChunk $parent, ?string $className, \DOMNode $node): HtmlParentChunk
    {
        if (HtmlTag::PAGE_BREAK->match((string) $className)) {
            return $this->createPageBreakChunk($name, $parent);
        }

        return match ($name) {
            HtmlTag::LINE_BREAK->value => $this->createBrChunk($name, $parent, $className),
            HtmlTag::LIST_ITEM->value => $this->createLiChunk($name, $parent, $className),
            HtmlTag::LIST_ORDERED->value => $this->createOlChunk($name, $parent, $className, $node),
            HtmlTag::LIST_UNORDERED->value => $this->createUlChunk($name, $parent, $className),
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
     * @psalm-return non-empty-string|null
     */
    private function trimHtml(): ?string
    {
        $content = \trim($this->html);
        if ('' === $content) {
            return null;
        }
        $content = \trim((string) \preg_replace('/\r\n|\n|\r/m', '', $content));
        if ('' === $content) {
            return null;
        }
        $content = \trim((string) \preg_replace('/\s\s+/m', ' ', $content));
        if ('' === $content) {
            return null;
        }

        return '<?xml encoding="UTF-8">' . $content;
    }
}

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

use App\Utils\StringUtils;

/**
 * Class to parse HTML content.
 */
readonly class HtmlParser
{
    /**
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
        $body = HtmlTag::BODY->findFirst($document);
        if (!$body instanceof \DOMElement) {
            return null;
        }

        $root = new HtmlParentChunk(HtmlTag::BODY);
        $this->parseNodes($root, $body);

        return $root->isEmpty() ? null : $root;
    }

    private function createBrChunk(HtmlParentChunk $parent, ?string $className): HtmlParentChunk
    {
        new HtmlBrChunk($parent, $className);

        return $parent;
    }

    private function createDescriptionListChunk(HtmlParentChunk $parent, ?string $className): HtmlDescriptionListChunk
    {
        return new HtmlDescriptionListChunk($parent, $className);
    }

    private function createLiChunk(HtmlParentChunk $parent, ?string $className): HtmlLiChunk
    {
        return new HtmlLiChunk($parent, $className);
    }

    private function createOlChunk(HtmlParentChunk $parent, ?string $className, \DOMNode $node): HtmlOlChunk
    {
        /** @var positive-int $start */
        $start = HtmlAttribute::LIST_START->getIntValue($node, 1);
        $type = HtmlAttribute::LIST_TYPE->getEnumValue($node, HtmlListType::NUMBER);

        return new HtmlOlChunk($parent, $className, $start, $type);
    }

    private function createPageBreakChunk(HtmlParentChunk $parent): HtmlParentChunk
    {
        new HtmlPageBreakChunk($parent);

        return $parent;
    }

    private function createParentChunk(HtmlTag $tag, HtmlParentChunk $parent, ?string $className): HtmlParentChunk
    {
        return new HtmlParentChunk($tag, $parent, $className);
    }

    private function createUlChunk(HtmlParentChunk $parent, ?string $className): HtmlUlChunk
    {
        return new HtmlUlChunk($parent, $className);
    }

    private function loadDocument(string $source): \DOMDocument
    {
        $document = new \DOMDocument();
        $document->loadHTML($source, \LIBXML_NOERROR | \LIBXML_NOBLANKS);

        return $document;
    }

    private function parseNode(HtmlParentChunk $parent, \DOMNode $node): void
    {
        $className = HtmlAttribute::CLASS_NAME->getValue($node);
        if ($node instanceof \DOMElement) {
            $name = \strtolower($node->nodeName);
            $parent = $this->parseNodeElement($name, $parent, $className, $node);
        } elseif ($node instanceof \DOMText) {
            $this->parseNodeText($parent, $className, $node);
        }
        $this->parseNodes($parent, $node);
    }

    private function parseNodeElement(string $name, HtmlParentChunk $parent, ?string $className, \DOMElement $node): HtmlParentChunk
    {
        if (HtmlTag::PAGE_BREAK->match((string) $className)) {
            return $this->createPageBreakChunk($parent);
        }

        $tag = HtmlTag::tryFrom($name) ?? HtmlTag::PARAGRAPH;

        return match ($tag) {
            HtmlTag::LINE_BREAK => $this->createBrChunk($parent, $className),
            HtmlTag::LIST_ITEM => $this->createLiChunk($parent, $className),
            HtmlTag::LIST_ORDERED => $this->createOlChunk($parent, $className, $node),
            HtmlTag::LIST_UNORDERED => $this->createUlChunk($parent, $className),
            HtmlTag::DESCRIPTION_LIST => $this->createDescriptionListChunk($parent, $className),
            default => $this->createParentChunk($tag, $parent, $className)
        };
    }

    private function parseNodes(HtmlParentChunk $parent, \DOMNode $node): void
    {
        foreach ($node->childNodes as $child) {
            $this->parseNode($parent, $child);
        }
    }

    private function parseNodeText(HtmlParentChunk $parent, ?string $className, \DOMText $node): void
    {
        $text = $node->wholeText;
        if ('' === \trim($text) && $parent->isEmpty()) {
            return;
        }
        new HtmlTextChunk($parent, $className, $text);
    }

    private function trimHtml(): ?string
    {
        $content = StringUtils::pregReplaceAll([
            '/\r\n|\n|\r/m' => '',
            '/\s{2,}/m' => ' ',
        ], $this->html);
        $content = StringUtils::trim($content);

        return null === $content ? null : '<?xml encoding="UTF-8">' . $content;
    }
}

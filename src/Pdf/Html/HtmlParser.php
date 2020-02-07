<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Pdf\Html;

use App\Utils\Utils;

/**
 * Class to parse HTML content.
 *
 * @author Laurent Muller
 */
class HtmlParser
{
    /**
     * The HTML content.
     *
     * @var string
     */
    protected $html;

    /**
     * Constructor.
     *
     * @param string $html the HTML content to parse
     */
    public function __construct(?string $html)
    {
        $this->html = $html;
    }

    /**
     * Parses this HTML content and return the root parent.
     *
     * @return HtmlParentChunk|null the root parent, if success; <code>null</code> otherwise
     */
    public function parse(): ?HtmlParentChunk
    {
        if (!$html = $this->trimHtml()) {
            return null;
        }

        // load content
        $dom = new \DOMDocument();
        if (!$dom->loadHTML($html)) { //, LIBXML_NOERROR | LIBXML_NOBLANKS
            return null;
        }

        // find body
        if (!$body = $this->findBody($dom)) {
            return null;
        }

        // parse
        $root = new HtmlParentChunk($body->nodeName);
        $this->parseNodes($root, $body);
        if ($root->isEmpty()) {
            return null;
        }

        return $root;
    }

    /**
     * Creates a HTML list item chunk.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     * @param string          $class  the optional class name
     * @param \DOMNode        $node   the current node
     */
    private function createLiChunk(string $name, HtmlParentChunk $parent, ?string $class, \DOMNode $node): HtmlLiChunk
    {
        $chunk = new HtmlLiChunk($name, $parent);
        $chunk->setClassName($class);

        return $chunk;
    }

    /**
     * Creates a HTML ordered list chunk.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     * @param string          $class  the optional class name
     * @param \DOMNode        $node   the current node
     */
    private function createOlChunk(string $name, HtmlParentChunk $parent, ?string $class, \DOMNode $node): HtmlOlChunk
    {
        $chunk = new HtmlOlChunk($name, $parent);
        $chunk->setClassName($class);
        $chunk->setType($this->getTypeAttribute($node));
        $chunk->setStart($this->getStartAttribute($node));

        return $chunk;
    }

    /**
     * Creates a HTML page break chunk.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     * @param string          $class  the optional class name
     * @param \DOMNode        $node   the current node
     */
    private function createPageBreakChunk(string $name, HtmlParentChunk $parent, ?string $class, \DOMNode $node): HtmlPageBreakChunk
    {
        return new HtmlPageBreakChunk($name, $parent);
    }

    /**
     * Creates a HTML parent chunk.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     * @param string          $class  the optional class name
     * @param \DOMNode        $node   the current node
     */
    private function createParentChunk(string $name, HtmlParentChunk $parent, ?string $class, \DOMNode $node): HtmlParentChunk
    {
        $chunk = new HtmlParentChunk($name, $parent);
        $chunk->setClassName($class);

        return $chunk;
    }

    /**
     * Creates a HTML text chunk.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     * @param string          $class  the optional class name
     * @param \DOMNode        $node   the current node
     */
    private function createTextChunk(string $name, HtmlParentChunk $parent, ?string $class, \DOMNode $node): ?HtmlTextChunk
    {
        /** @var \DOMText $nodeText */
        $nodeText = $node;
        $value = $nodeText->wholeText;
        if (\strlen(\trim($value))) {
            $chunk = new HtmlTextChunk($name, $parent);
            $chunk->setClassName($class);
            $chunk->setText($value);

            return $chunk;
        }

        return null;
    }

    /**
     * Creates a HTML unordered list chunk.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     * @param string          $class  the optional class name
     * @param \DOMNode        $node   the current node
     */
    private function createUlChunk(string $name, HtmlParentChunk $parent, ?string $class, \DOMNode $node): HtmlUlChunk
    {
        $chunk = new HtmlUlChunk($name, $parent);
        $chunk->setClassName($class);

        return $chunk;
    }

    /**
     * Finds the body element.
     *
     * @param \DOMDocument $dom the document to search in
     *
     * @return \DOMNode|null the body, if found; null otherwise
     */
    private function findBody(\DOMDocument $dom): ? \DOMNode
    {
        $bodies = $dom->getElementsByTagName('body');
        if ($bodies->length) {
            return $bodies->item(0);
        }

        return null;
    }

    /**
     * Gets a attribute value for the given node.
     *
     * @param \DOMNode $node    the node to get attribute for
     * @param string   $name    the attribute name to find
     * @param string   $default the default value to returns if the attribute is not found
     *
     * @return string the attribute value, if found; the default value otherwise
     */
    private function getAttribute(\DOMNode $node, string $name, ?string $default = null): ?string
    {
        if ($node->hasAttributes()) {
            $attributes = $node->attributes;
            if ($attribute = $attributes->getNamedItem($name)) {
                $value = \trim($attribute->nodeValue);
                if (Utils::isString($value)) {
                    return $value;
                }
            }
        }

        return $default;
    }

    /**
     * Gets the class attribute value for the given node.
     *
     * @param \DOMNode $node the node to get class attribute for
     *
     * @return string|null the class attribute, if found; null otherwise
     */
    private function getClassAttribute(\DOMNode $node): ?string
    {
        return $this->getAttribute($node, 'class');
    }

    /**
     * Gets the start attribute value for the given node.
     *
     * @param \DOMNode $node the node to get type attribute for
     *
     * @return int the start attribute, if found; 1 otherwise
     */
    private function getStartAttribute(\DOMNode $node): int
    {
        return (int) $this->getAttribute($node, 'start', '1');
    }

    /**
     * Gets the type attribute value for the given node.
     *
     * @param \DOMNode $node the node to get type attribute for
     *
     * @return string|null the type attribute, if found; number type ('1') otherwise
     */
    private function getTypeAttribute(\DOMNode $node): ?string
    {
        return $this->getAttribute($node, 'type', HtmlOlChunk::TYPE_NUMBER);
    }

    /**
     * Parse a node and it's children (if any).
     *
     * @param HtmlParentChunk $parent the parent chunk
     * @param \DOMNode        $node   the node to parse
     */
    private function parseNode(HtmlParentChunk $parent, \DOMNode $node): void
    {
        // create chunk
        switch ($node->nodeType) {
            case XML_ELEMENT_NODE:
                $name = $node->nodeName;
                $class = $this->getClassAttribute($node);
                if (HtmlChunk::PAGE_BREAK === $class) {
                    $this->createPageBreakChunk($name, $parent, $class, $node);
                } elseif (HtmlChunk::LIST_ITEM === $name) {
                    $parent = $this->createLiChunk($name, $parent, $class, $node);
                } elseif (HtmlChunk::LIST_ORDERED === $name) {
                    $parent = $this->createOlChunk($name, $parent, $class, $node);
                } elseif (HtmlChunk::LIST_UNORDERED === $name) {
                    $parent = $this->createUlChunk($name, $parent, $class, $node);
                } else {
                    $parent = $this->createParentChunk($name, $parent, $class, $node);
                }
                break;

            case XML_TEXT_NODE:
                $name = $node->nodeName;
                $class = $this->getClassAttribute($node);
                $this->createTextChunk($name, $parent, $class, $node);
                break;
        }

        // children
        $this->parseNodes($parent, $node);
    }

    /**
     * Parse the children nodes. Do nothing if node has no children.
     *
     * @param HtmlParentChunk $parent the parent chunk
     * @param \DOMNode        $node   the node to get children to parse
     */
    private function parseNodes(HtmlParentChunk $parent, \DOMNode $node): void
    {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $this->parseNode($parent, $child);
            }
        }
    }

    /**
     * Gets the clean HTML content.
     *
     * @return string|null the HTML content or <code>null</code> if no content
     */
    private function trimHtml(): ?string
    {
        // check content
        if (!Utils::isString($content = \trim((string) $this->html))) {
            return null;
        }

        // trim new line and spaces
        $content = \trim(\preg_replace('/\r\n|\n|\r/m', '', $content));
        $content = \trim(\preg_replace('/\s\s+/m', ' ', $content));

        // string?
        if (!Utils::isString($content)) {
            return null;
        }

        // add encoding
        return "<?xml encoding='UTF-8'>{$content}";
    }
}

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
     * @return HtmlParentChunk|null the root parent, if success, <code>null</code> otherwise
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
        $bodies = $dom->getElementsByTagName('body');
        if (!$bodies->length) {
            return null;
        }

        // parse
        $body = $bodies->item(0);
        $root = new HtmlParentChunk($body->nodeName);
        $this->parseNodes($root, $body);
        if ($root->isEmpty()) {
            return null;
        }

        return $root;
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
                    new HtmlPageBreakChunk($name, $parent);
                } elseif (HtmlChunk::LIST_ITEM === $name) {
                    $parent = new HtmlLiChunk($name, $parent);
                    $parent->setClassName($class);
                } elseif (HtmlChunk::LIST_ORDERED === $name) {
                    $parent = new HtmlOlChunk($name, $parent);
                    $parent->setClassName($class)
                        ->setType($this->getTypeAttribute($node))
                        ->setStart($this->getStartAttribute($node));
                } elseif (HtmlChunk::LIST_UNORDERED === $name) {
                    $parent = new HtmlUlChunk($name, $parent);
                    $parent->setClassName($class);
                } else {
                    $parent = new HtmlParentChunk($name, $parent);
                    $parent->setClassName($class);
                }
                break;

            case XML_TEXT_NODE:
                /** @var \DOMText $nodeText */
                $nodeText = $node;
                $value = $nodeText->wholeText;
                $value = $node->wholeText; //nodeValue;
                if (\strlen(\trim($value))) {
                    $name = $node->nodeName;
                    $textChunk = new HtmlTextChunk($name, $parent);
                    $textChunk->setClassName($this->getClassAttribute($node))
                        ->setText($value);
                }
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

        // trim new line
        $content = \trim(\preg_replace('/\r\n|\n|\r/m', '', $content));

        // trim spaces
        $content = \trim(\preg_replace('/\s\s+/m', ' ', $content));

        if (!Utils::isString($content)) {
            return null;
        }

        // add encoding
        return "<?xml encoding='UTF-8'>{$content}";
    }
}

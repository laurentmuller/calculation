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
 * Html attribute enumeration.
 */
enum HtmlAttribute: string
{
    /** The class attribute name. */
    case CLASS_NAME = 'class';

    /** The start ordered-list attribute name. */
    case LIST_START = 'start';

    /** The list type attribute name. */
    case LIST_TYPE = 'type';

    /**
     * @template T of \BackedEnum
     *
     * @phpstan-param T $default
     *
     * @phpstan-return T
     */
    public function getEnumValue(\DOMNode $node, \BackedEnum $default): \BackedEnum
    {
        $value = $this->getValue($node, (string) $default->value);
        if (\is_int($default->value)) {
            return $default::tryFrom((int) $value) ?? $default;
        }

        return $default::tryFrom($value) ?? $default;
    }

    public function getIntValue(\DOMNode $node, int $default = 0): int
    {
        return (int) $this->getValue($node, (string) $default);
    }

    /**
     * @phpstan-return ($default is null ? (string|null) : string)
     */
    public function getValue(\DOMNode $node, ?string $default = null): ?string
    {
        if (!$node->attributes instanceof \DOMNamedNodeMap) {
            return $default;
        }

        $attribute = $node->attributes->getNamedItem($this->value);
        if (!$attribute instanceof \DOMNode || null === $attribute->nodeValue) {
            return $default;
        }

        $value = StringUtils::trim($attribute->nodeValue);
        if (null === $value) {
            return $default;
        }

        return $value;
    }
}

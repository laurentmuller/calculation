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

namespace App\Word;

use PhpOffice\PhpWord\Element\Section;

/**
 * Abstract base class to output header or footer in Word documents.
 */
abstract class AbstractHeaderFooter
{
    /** The document width. */
    protected const int TOTAL_WIDTH = 12_000;

    /**
     * @param AbstractWordDocument $parent the parent's document
     */
    public function __construct(private readonly AbstractWordDocument $parent)
    {
    }

    /**
     * Add texts to the given section.
     */
    abstract public function output(Section $section): void;

    /**
     * Gets the document's title.
     */
    protected function getTitle(): ?string
    {
        return $this->parent->getTitle();
    }

    /**
     * Translates the given message.
     *
     * @param string $id         the message identifier (may also be an object that can be cast to string)
     * @param array  $parameters an array of parameters for the message
     *
     * @return string the translated string
     */
    protected function trans(string $id, array $parameters = []): string
    {
        return $this->parent->trans($id, $parameters);
    }
}

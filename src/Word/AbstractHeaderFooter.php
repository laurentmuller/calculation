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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract base class to output header or footer in Word documents.
 */
abstract class AbstractHeaderFooter
{
    /**
     * The document width.
     */
    protected const TOTAL_WIDTH = 12000;

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
     * Gets the translator.
     */
    protected function getTranslator(): TranslatorInterface
    {
        return $this->parent->getTranslator();
    }

    /**
     * Translates the given message.
     *
     * @param string  $id         the message id (may also be an object that can be cast to string)
     * @param array   $parameters an array of parameters for the message
     * @param ?string $domain     the domain for the message or null to use the default
     * @param ?string $locale     the locale or null to use the default
     *
     * @return string the translated string
     */
    protected function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->parent->trans($id, $parameters, $domain, $locale);
    }
}

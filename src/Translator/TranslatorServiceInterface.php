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

namespace App\Translator;

use App\Model\HttpClientError;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Service to detect and translate texts.
 */
#[AutoconfigureTag]
interface TranslatorServiceInterface
{
    /**
     * Identifies the language of a piece of text.
     *
     * @param string $text the text to detect
     *
     * @return array{tag: string, name: string|null}|false the detected language; false if not
     *                                                     found or if an error occurs
     */
    public function detect(string $text): array|false;

    /**
     * Gets the API documentation.
     */
    public static function getApiUrl(): string;

    /**
     * Gets the set of languages currently supported by other operations of the service.
     *
     * @return array<string, string>|false an array containing the language name as key and the BCP 47
     *                                     language tag as value; false if an error occurs
     */
    public function getLanguages(): array|false;

    /**
     * Gets the last error.
     */
    public function getLastError(): ?HttpClientError;

    /**
     * Gets the name.
     */
    public static function getName(): string;

    /**
     * Translates a text.
     *
     * @param string  $text the text to translate
     * @param string  $to   the language of the output text
     * @param ?string $from the language of the input text. If the form parameter is not specified, automatic
     *                      language detection is applied to determine the source language.
     * @param bool    $html defines whether the text being translated is HTML text (true) or plain text (false)
     *
     * @return array{source: string, target: string, from: array{tag: string, name: string|null}, to: array{tag: string, name: string|null}}|false the translated text; false if an error occurs
     */
    public function translate(string $text, string $to, ?string $from = null, bool $html = false): array|false;
}

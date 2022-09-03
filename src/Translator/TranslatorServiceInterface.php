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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Service to detect and translate texts.
 */
#[Autoconfigure(tags: ['translators_service'])]
interface TranslatorServiceInterface
{
    /**
     * Identifies the language of a piece of text.
     *
     * @param string $text the text to detect
     *
     * @return bool|array on success, returns an array with the following entries:
     *                    <ul>
     *                    <li>'tag': The detected language tag (BCP 47).</li>
     *                    <li>'name': The detected language display name.</li>
     *                    </ul>
     *                    Returns false if an error occurs.
     *
     * @psalm-return array{
     *      tag: string,
     *      name: string|null
     * }|false
     */
    public function detect(string $text): array|false;

    /**
     * Gets the API documentation.
     */
    public static function getApiUrl(): string;

    /**
     * Gets the class name.
     */
    public static function getClassName(): string;

    /**
     * Gets the default index name (the service name).
     */
    public static function getDefaultIndexName(): string;

    /**
     * Gets the set of languages currently supported by other operations of the service.
     *
     * @return array|false an array containing the language name as key and the BCP 47 language tag as value; false if an error occurs
     *
     * @psalm-return array<string, string>|false
     */
    public function getLanguages(): array|false;

    /**
     * Gets the last error.
     *
     * @return array|null the last error with the 'code' and the 'message' entries; null if none
     *
     * @psalm-return null|array{
     *      result: bool,
     *      code: string|int,
     *      message: string,
     *      exception?: array|\Exception}
     */
    public function getLastError(): ?array;

    /**
     * Translates a text.
     *
     * @param string  $text the text to translate
     * @param string  $to   the language of the output text
     * @param ?string $from the language of the input text. If the form parameter is not specified, automatic language detection is applied to determine the source language.
     * @param bool    $html defines whether the text being translated is HTML text (true) or plain text (false)
     *
     * @return array|false on success, returns an array with the following entries:
     *                     <ul>
     *                     <li>'source': The source text.</li>
     *                     <li>'target': The translated text.</li>
     *                     <li>'from': The from values.
     *                     <ul>
     *                     <li>'tag': The language tag (BCP 47).</li>
     *                     <li>'name': The language display name.</li>
     *                     </ul>
     *                     </li>
     *                     <li>'to': The to values.
     *                     <ul>
     *                     <li>'tag': The language tag (BCP 47).</li>
     *                     <li>'name': The language display name.</li>
     *                     </ul>
     *                     </li>
     *                     </ul>
     *                     Returns false if an error occurs.
     *
     * @psalm-return array{
     *      source: string,
     *      target: string,
     *      from: array {
     *          tag: string,
     *          name: string|null},
     *      to: array {
     *          tag: string,
     *          name: string|null}
     *      }|false
     */
    public function translate(string $text, string $to, ?string $from = null, bool $html = false): array|false;
}

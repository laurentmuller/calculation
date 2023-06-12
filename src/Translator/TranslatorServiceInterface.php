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
use App\Model\TranslateQuery;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Service to detect and translate texts.
 *
 * @psalm-type TranslatorDetectType = array{
 *     tag: string,
 *     name: string|null}
 * @psalm-type TranslatorTranslateType = array{
 *     source: string,
 *     target: string,
 *     from: TranslatorDetectType,
 *     to: TranslatorDetectType}
 */
#[AutoconfigureTag]
interface TranslatorServiceInterface
{
    /**
     * Identifies the language of a piece of text.
     *
     * @param string $text the text to detect
     *
     * @return array|false the detected language; false if not found or if an error occurs
     *
     * @psalm-return TranslatorDetectType|false
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
     * @param TranslateQuery $query the query to translate
     *
     * @return array|false the translated text; false if an error occurs
     *
     * @psalm-return TranslatorTranslateType|false
     */
    public function translate(TranslateQuery $query): array|false;
}

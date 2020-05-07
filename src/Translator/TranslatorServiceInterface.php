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

namespace App\Translator;

/**
 * Service to detect and translate texts.
 *
 * @author Laurent Muller
 */
interface TranslatorServiceInterface
{
    /**
     * Identifies the language of a piece of text.
     *
     * @param string $text the text to detect
     *
     * @return array|bool on success, returns an array with the following entries:
     *                    <ul>
     *                    <li>'tag': The detected language tag (BCP 47).</li>
     *                    <li>'name': The detected language display name.</li>
     *                    </ul>
     *                    Returns false if an error occurs.
     */
    public function detect(string $text);

    /**
     * Gets the API documentation.
     */
    public static function getApiUrl(): string;

    /**
     * Gets the class name.
     */
    public static function getClassName(): string;

    /**
     * Gets the set of languages currently supported by other operations of the service.
     *
     * @return array|bool an array containing the language name as key and the BCP 47 language tag as value; false if an error occurs
     */
    public function getLanguages();

    /**
     * Gets the last error.
     *
     * @return array|null the last error with the 'code' and the 'message' entries; null if none
     */
    public function getLastError(): ?array;

    /**
     * Gets the service name.
     */
    public static function getName(): string;

    /**
     * Translates a text.
     *
     * @param string $text the text to translate
     * @param string $to   the language of the output text
     * @param string $from the language of the input text. If the from parameter is not specified, automatic language detection is applied to determine the source language.
     * @param bool   $html defines whether the text being translated is HTML text (true) or plain text (false)
     *
     * @return array|bool on success, returns an array with the following entries:
     *                    <ul>
     *                    <li>'source': The source text.</li>
     *                    <li>'target': The translated text.</li>
     *                    <li>'from': The from values.
     *                    <ul>
     *                    <li>'tag': The language tag (BCP 47).</li>
     *                    <li>'name': The language display name.</li>
     *                    </ul>
     *                    </li>
     *                    <li>'to': The to values.
     *                    <ul>
     *                    <li>'tag': The language tag (BCP 47).</li>
     *                    <li>'name': The language display name.</li>
     *                    </ul>
     *                    </li>
     *                    </ul>
     *                    Returns false if an error occurs.
     */
    public function translate(string $text, string $to, ?string $from = null, bool $html = false);
}

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

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Google translator service v2.0.
 *
 * @author Laurent Muller
 *
 * @see https://cloud.google.com/translate/docs/translating-text
 */
class GoogleTranslatorService extends AbstractTranslatorService
{
    /**
     * The host name.
     */
    private const HOST_NAME = 'https://translation.googleapis.com/language/translate/v2/';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'google_translator_key';

    /**
     * The detect URI.
     */
    private const URI_DETECT = 'detect';

    /**
     * The languages URI.
     */
    private const URI_LANGUAGE = 'languages';

    /**
     * The translate URI.
     */
    private const URI_TRANSLATE = '';

    /**
     * Constructor.
     *
     * @throws ParameterNotFoundException if the Google key parameter is not defined
     */
    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, AdapterInterface $cache)
    {
        $key = $params->get(self::PARAM_KEY);
        parent::__construct($kernel, $cache, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function detect(string $text)
    {
        $query = ['q' => $text];
        if (!$response = $this->get(self::URI_DETECT, $query)) {
            return false;
        }

        // detections
        if (!$detections = $this->getPropertyArray($response, 'detections')) {
            return false;
        }

        // entries
        if (!$this->isValidArray($detections[0], 'entries')) {
            return false;
        }
        $entries = $detections[0];

        // entry
        if (!$this->isValidArray($entries[0], 'detection')) {
            return false;
        }
        $detection = $entries[0];

        // language
        if (!$tag = $this->getProperty($detection, 'language')) {
            return false;
        }

        return [
            'tag' => $tag,
            'name' => $this->findLanguage($tag),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getApiUrl(): string
    {
        return 'https://cloud.google.com/translate/docs/translating-text';
    }

    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'Google';
    }

    /**
     * {@inheritdoc}
     */
    public function translate(string $text, string $to, ?string $from = null, bool $html = false)
    {
        $query = [
            'q' => $text,
            'target' => $to,
            'source' => $from ?: '',
            'format' => $html ? 'html' : 'text',
        ];
        if (!$response = $this->get(self::URI_TRANSLATE, $query)) {
            return false;
        }

        // translations
        if (!$translations = $this->getPropertyArray($response, 'translations')) {
            return false;
        }
        $translation = $translations[0];

        // target
        if (!$target = $this->getProperty($translation, 'translatedText')) {
            return false;
        }

        // from
        if ($detectedSourceLanguage = $this->getProperty($translation, 'detectedSourceLanguage', false)) {
            $from = $detectedSourceLanguage;
        }

        return [
            'source' => $text,
            'target' => $target,
            'from' => [
                'tag' => $from,
                'name' => $this->findLanguage($from),
            ],
            'to' => [
                'tag' => $to,
                'name' => $this->findLanguage($to),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetLanguages()
    {
        $query = ['target' => self::getAcceptLanguage(true)];
        if (!$response = $this->get(self::URI_LANGUAGE, $query)) {
            return false;
        }

        // languages
        if (!$languages = $this->getPropertyArray($response, 'languages')) {
            return false;
        }

        // build
        $result = [];
        foreach ($languages as $language) {
            $result[$language['name']] = $language['language'];
        }
        \ksort($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => self::HOST_NAME];
    }

    /**
     * Make a HTTP-GET call.
     *
     * @param string $uri   the uri to append to the host name
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return mixed|bool the data response on success, false otherwise
     */
    private function get(string $uri, array $query = [])
    {
        // add key parameter
        $query['key'] = $this->key;

        // call
        $response = $this->requestGet($uri, [
            self::QUERY => $query,
        ]);

        // check status code
//         $status = $response->getStatusCode();
//         if (Response::HTTP_OK !== $status) {
//             //return $this->setLastError($status, $response->getReasonPhrase());
//         }

        // decode
        $response = $response->toArray(false);

        // check error
        if ($this->lastError = $this->getProperty($response, 'error', false)) {
            return false;
        }

        // get data
        if (!$data = $this->getProperty($response, 'data')) {
            return false;
        }

        return $data;
    }
}

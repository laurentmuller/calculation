<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Translator;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Locales;

/**
 * Ynadex translator service.
 *
 * @author Laurent Muller
 *
 * @see https://tech.yandex.com/translate/doc/dg/concepts/about-docpage/
 */
class YandexTranslatorService extends AbstractTranslatorService
{
    /**
     * The host name.
     */
    private const HOST_NAME = 'https://translate.yandex.net/api/v1.5/tr.json/';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'yandex_translator_key';

    /**
     * The detect URI.
     */
    private const URI_DETECT = 'detect';

    /**
     * The languages URI.
     */
    private const URI_LANGUAGE = 'getLangs';

    /**
     * The translate URI.
     */
    private const URI_TRANSLATE = 'translate';

    /**
     * Constructor.
     *
     * @throws ParameterNotFoundException if the Yandex key parameter is not defined
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
        $query = ['text' => $text];
        if (!$response = $this->get(self::URI_DETECT, $query)) {
            return false;
        }

        if (!$tag = $this->getProperty($response, 'lang')) {
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
        return 'https://tech.yandex.com/translate/doc/dg/concepts/about-docpage/';
    }

    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'Yandex';
    }

    /**
     * {@inheritdoc}
     */
    public function translate(string $text, string $to, ?string $from = null, bool $html = false)
    {
        $query = [
            'options' => 1,
            'text' => $text,
            'lang' => $from ? $from . '-' . $to : $to,
            'format' => $html ? 'html' : 'plain',
        ];
        if (!$response = $this->get(self::URI_TRANSLATE, $query)) {
            return false;
        }

        // text
        if (!$targetText = $this->getPropertyArray($response, 'text')) {
            return false;
        }

        // from
        if ($lang = $this->getProperty($response, 'lang', false)) {
            $from = \explode('-', $lang)[0];
        } elseif ($detected = $this->getProperty($response, 'detected', false)) {
            if ($lang = $this->getProperty($detected, 'lang', false)) {
                $from = $lang;
            }
        }

        return [
            'source' => $text,
            'target' => $targetText[0],
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
        $query = ['ui' => self::getAcceptLanguage(true)];
        if (false === $response = $this->get(self::URI_LANGUAGE, $query)) {
            return false;
        }

        // languages
        if (!$langs = $this->getPropertyArray($response, 'langs')) {
            return false;
        }

        // build
        $result = [];
        $keys = \array_keys($langs);
        foreach ($keys as $key) {
            if (Locales::exists($key)) {
                $name = \ucfirst(Locales::getName($key));
                $result[$name] = $key;
            }
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
     * @return mixed|bool the response on success, false otherwise
     */
    private function get(string $uri, array $query = [])
    {
        // add key parameter
        $query['key'] = $this->key;

        // call
        $response = $this->requestGet($uri, [
            self::QUERY => $query,
        ]);

        // decode
        $response = $response->toArray(false);

        // check code
        if (isset($response['code']) && Response::HTTP_OK !== $response['code']) {
            $this->lastError = $response;

            return false;
        }

        return $response;
    }
}

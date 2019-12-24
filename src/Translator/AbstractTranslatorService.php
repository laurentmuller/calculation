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

use App\Service\HttpClientService;
use App\Utils\Utils;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Abstract translator service.
 *
 * @author Laurent Muller
 */
abstract class AbstractTranslatorService extends HttpClientService implements ITranslatorService
{
    /**
     * The cache timeout (60 minutes).
     */
    protected const CACHE_TIMEOUT = 60 * 60;

    /**
     * The field not found status code.
     */
    protected const ERROR_NOT_FOUND = 199;

    /**
     * The property accessor.
     *
     * @var PropertyAccessor
     */
    protected $accessor;

    /**
     * The translator cache.
     *
     * @var AdapterInterface
     */
    protected $cache;

    /**
     * The key to cache language.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * The debug mode.
     *
     * @var bool
     */
    protected $debug;

    /**
     * The API key.
     *
     * @var string
     */
    protected $key;

    /**
     * Constructor.
     *
     * @param KernelInterface  $kernel the kernel to get the debug mode
     * @param AdapterInterface $cache  the cache used to save or retrieve languages
     * @param string           $key    the API key
     *
     * @throws \InvalidArgumentException if the key is empty
     */
    public function __construct(KernelInterface $kernel, AdapterInterface $cache, string $key)
    {
        // check key
        if (empty($key)) {
            throw new \InvalidArgumentException('The translator key is empty.');
        }

        $this->debug = $kernel->isDebug();
        $this->cache = $cache;
        $this->key = $key;
    }

    /**
     * Gets the display name of the language for the given BCP 47 language tag.
     *
     * @param string $tag the BCP 47 language tag to earch for
     *
     * @return string|null the display name, if found; null otherwise
     */
    public function findLanguage(?string $tag): ?string
    {
        if ($tag && $languages = $this->getLanguages()) {
            if ($name = \array_search($tag, $languages, true)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguages()
    {
        // already cached?
        if (!$this->debug) {
            $item = $this->cache->getItem($this->getCacheKey());
            if ($item->isHit()) {
                return $item->get();
            }
        }

        // get language
        $languages = $this->doGetLanguages();

        // cache result
        if (!$this->debug) {
            if (!empty($languages) && empty($this->lastError)) {
                $item->set($languages);
                $item->expiresAfter(self::CACHE_TIMEOUT);
                $this->cache->save($item);
            }
        }

        return $languages;
    }

    /**
     * Gets the set of languages currently supported by other operations of the service.
     *
     * @return array|bool an array containing the language name as key and the BCP 47 language tag as value; false if an error occurs
     */
    abstract protected function doGetLanguages();

    /**
     * Gets the cache key used to save/retrieve languages.
     */
    protected function getCacheKey(): string
    {
        if (!$this->cacheKey) {
            $this->cacheKey = Utils::getShortName($this).'Languages';
        }

        return $this->cacheKey;
    }

    /**
     * Gets the property value.
     *
     * @param array  $data  the data to search in
     * @param string $name  the property name to search for
     * @param bool   $error true to create an error if the property is not found
     *
     * @return mixed|bool the property value, if found; false if fail
     */
    protected function getProperty(array $data, string $name, bool $error = true)
    {
        if (!isset($data[$name])) {
            if ($error) {
                return $this->setLastError(self::ERROR_NOT_FOUND, "Unable to find the '{$name}' field.");
            }

            return false;
        }

        return $data[$name];
    }

    /**
     * Gets the property accessor.
     */
    protected function getPropertyAccessor(): PropertyAccessor
    {
        if (!$this->accessor) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }

    /**
     * Gets the property value as an array.
     *
     * @param array  $data  the data to search in
     * @param string $name  the property name to search for
     * @param bool   $error true to create an error if the property is not found
     *
     * @return array|bool a none empty array, if found; false if fail
     */
    protected function getPropertyArray(array $data, string $name, bool $error = true)
    {
        if (!$property = $this->getProperty($data, $name, $error)) {
            return false;
        }

        if (!$this->isValidArray($property, $name, $error)) {
            return false;
        }

        return (array) $property;
    }

    /**
     * Checks if the given variable is an array and is not empty.
     *
     * @param mixed  $var   the variable being evaluated
     * @param string $name  the variable name to use to report error
     * @param bool   $error true to create an error if the property is not found
     *
     * @return bool true if variable is an array and is not empty
     */
    protected function isValidArray($var, string $name, bool $error = true): bool
    {
        if (!\is_array($var)) {
            if ($error) {
                return $this->setLastError(self::ERROR_NOT_FOUND, "The '{$name}' field is not an array.");
            }

            return false;
        } elseif (empty($var)) {
            if ($error) {
                return $this->setLastError(self::ERROR_NOT_FOUND, "The '{$name}' field is empty.");
            }

            return false;
        }

        return true;
    }
}

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

use App\Service\AbstractHttpClientService;
use App\Util\Utils;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Abstract translator service.
 *
 * @author Laurent Muller
 */
abstract class AbstractTranslatorService extends AbstractHttpClientService implements TranslatorServiceInterface
{
    /**
     * The field not found status code.
     */
    protected const ERROR_NOT_FOUND = 199;

    /**
     * The property accessor.
     */
    protected ?PropertyAccessor $accessor = null;

    /**
     * The key to cache language.
     */
    protected ?string $cacheKey = null;

    /**
     * Gets the display name of the language for the given BCP 47 language tag.
     *
     * @param string $tag the BCP 47 language tag to earch for
     *
     * @return string|null the display name, if found; null otherwise
     */
    public function findLanguage(?string $tag): ?string
    {
        if ($tag && ($languages = $this->getLanguages()) && ($name = \array_search($tag, (array) $languages, true))) {
            return (string) $name;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getClassName(): string
    {
        return static::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguages()
    {
        // already cached?
        $key = $this->getCacheKey();
        if ($languages = $this->getCacheValue($key)) {
            return $languages;
        }

        // get language
        $languages = $this->doGetLanguages();

        // cache result
        if (!empty($languages) && empty($this->lastError)) {
            $this->setCacheValue($key, $languages);
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
     * Gets the cache key used to save or retrieve languages.
     */
    protected function getCacheKey(): string
    {
        if (!$this->cacheKey) {
            $this->cacheKey = Utils::getShortName($this) . 'Languages';
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
        if (null === $this->accessor) {
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

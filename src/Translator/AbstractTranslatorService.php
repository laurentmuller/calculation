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

use App\Model\TranslateQuery;
use App\Service\AbstractHttpClientService;
use App\Utils\StringUtils;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Abstract translator service.
 *
 * @psalm-import-type TranslatorTranslateType from TranslatorServiceInterface
 */
abstract class AbstractTranslatorService extends AbstractHttpClientService implements TranslatorServiceInterface
{
    /**
     * The not found field status code.
     */
    final protected const ERROR_NOT_FOUND = 199;

    /**
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 15;

    /**
     * The property accessor to get values.
     */
    private ?PropertyAccessorInterface $accessor = null;

    /**
     * The key to cache language.
     */
    private ?string $cacheKey = null;

    /**
     * Gets the display name of the language for the given BCP 47 language tag.
     *
     * @param ?string $tag the BCP 47 language tag to search for
     *
     * @return string|null the display name, if found; null otherwise
     */
    public function findLanguage(?string $tag): ?string
    {
        if (null === $tag) {
            return null;
        }

        $languages = $this->getLanguages();
        if (false === $languages) {
            return null;
        }

        $name = \array_search($tag, $languages, true);
        if (false === $name) {
            return null;
        }

        return $name;
    }

    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
    }

    public function getLanguages(): array|false
    {
        $key = $this->getCacheKey();

        return $this->getCacheValue($key, fn (): array|false => $this->doLoadLanguages());
    }

    /**
     * @psalm-return TranslatorTranslateType
     */
    protected function createTranslateResults(TranslateQuery $query, string $target): array
    {
        return [
            'source' => $query->text,
            'target' => $target,
            'from' => [
                'tag' => $query->from,
                'name' => $this->findLanguage($query->from),
            ],
            'to' => [
                'tag' => $query->to,
                'name' => $this->findLanguage($query->to),
            ],
        ];
    }

    protected function getValue(array $values, string $path, bool $error = true): mixed
    {
        $accessor = $this->getPropertyAccessor();
        /** @psalm-var mixed $value */
        $value = $accessor->getValue($values, $path);
        if (null === $value && $error) {
            return $this->setLastError(self::ERROR_NOT_FOUND, "Unable to find the value at '$path'.");
        }

        return $value;
    }

    /**
     * Handle the response error.
     *
     * @return bool false if an error is set; true otherwise
     */
    protected function handleError(array $response): bool
    {
        if (isset($response['error'])) {
            /** @psalm-var array{code: int, message: string} $error */
            $error = $response['error'];

            return $this->setLastError($error['code'], $error['message']);
        }

        return true;
    }

    /**
     * Gets the set of languages supported by other operations of the service.
     *
     * @return array|false an array containing the language name as the key and the BCP 47 language tag as value;
     *                     false if an error occurs
     *
     * @psalm-return array<string, string>|false
     */
    abstract protected function loadLanguages(): array|false;

    /**
     * @return array<string, string>|false
     */
    private function doLoadLanguages(): array|false
    {
        $languages = $this->loadLanguages();
        if (false !== $languages && [] !== $languages && !$this->hasLastError()) {
            return $languages;
        }

        return false;
    }

    private function getCacheKey(): string
    {
        return $this->cacheKey ??= \sprintf('%s.Languages', StringUtils::getShortName($this));
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (!$this->accessor instanceof PropertyAccessorInterface) {
            $this->accessor = PropertyAccess::createPropertyAccessorBuilder()
                ->disableExceptionOnInvalidPropertyPath()
                ->getPropertyAccessor();
        }

        return $this->accessor;
    }
}

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

use App\Traits\SessionTrait;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Factory to provide translator services.
 *
 * @see TranslatorServiceInterface
 */
class TranslatorFactory
{
    use SessionTrait;

    /**
     * The default translator service class name (Bing).
     */
    final public const DEFAULT_SERVICE = BingTranslatorService::class;

    /**
     * The key to save/retrieve the last used service.
     */
    private const KEY_LAST_SERVICE = 'translator_service';

    /**
     * @var TranslatorServiceInterface[]
     */
    private readonly array $translators;

    /**
     * Constructor.
     *
     * @param iterable<TranslatorServiceInterface> $translators
     */
    public function __construct(RequestStack $requestStack, #[TaggedIterator('translators_service')] iterable $translators)
    {
        $this->setRequestStack($requestStack);
        $this->translators = $translators instanceof \Traversable ? \iterator_to_array($translators) : $translators;
    }

    /**
     * Returns if the given translator service exists.
     *
     * @param string $class the service class name to be tested
     *
     * @return bool true if exist
     */
    public function exists(string $class): bool
    {
        return null !== $this->find($class);
    }

    /**
     * Finds the translator service for the given class.
     *
     * @param string $class the service class name to find
     *
     * @return TranslatorServiceInterface|null the service, if found; null otherwise
     */
    public function find(string $class): ?TranslatorServiceInterface
    {
        foreach ($this->translators as $translator) {
            if ($class === $translator::class) {
                return $translator;
            }
        }

        return null;
    }

    /**
     * Gets a translator service from the given class name.
     *
     * @param string $class the service class name to return
     *
     * @return TranslatorServiceInterface the translator service
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function getService(string $class): TranslatorServiceInterface
    {
        $service = $this->find($class);
        if (!$service instanceof TranslatorServiceInterface) {
            throw new ServiceNotFoundException($class);
        }
        $this->setSessionValue(self::KEY_LAST_SERVICE, $class);

        return $service;
    }

    /**
     * Gets the last used translator service from the session.
     *
     * @return TranslatorServiceInterface the translator service or the default (Bing) if not found
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function getSessionService(): TranslatorServiceInterface
    {
        $class = (string) $this->getSessionValue(self::KEY_LAST_SERVICE, self::DEFAULT_SERVICE);
        if (!$this->exists($class)) {
            $class = self::DEFAULT_SERVICE;
        }

        return $this->getService($class);
    }

    /**
     * Gets the registered translator services.
     *
     * @return TranslatorServiceInterface[]
     */
    public function getTranslators(): array
    {
        return $this->translators;
    }
}

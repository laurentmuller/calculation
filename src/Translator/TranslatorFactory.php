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

use App\Traits\SessionAwareTrait;
use App\Util\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Factory to provide translator services.
 *
 * @see TranslatorServiceInterface
 */
class TranslatorFactory implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use SessionAwareTrait;

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
    public function __construct(#[TaggedIterator(TranslatorServiceInterface::class)] iterable $translators)
    {
        $this->translators = $translators instanceof \Traversable ? \iterator_to_array($translators) : $translators;
    }

    /**
     * Returns if the given translator service exists.
     *
     * @param string $classOrName the service class or name to be tested
     *
     * @return bool true if exist
     */
    public function exists(string $classOrName): bool
    {
        return null !== $this->find($classOrName);
    }

    /**
     * Finds the translator service for the given class or name.
     *
     * @param string $classOrName the service class or name to find
     *
     * @return TranslatorServiceInterface|null the service, if found; null otherwise
     */
    public function find(string $classOrName): ?TranslatorServiceInterface
    {
        foreach ($this->translators as $translator) {
            if (StringUtils::equalIgnoreCase($classOrName, $translator::class)
                || StringUtils::equalIgnoreCase($classOrName, $translator::getName())) {
                return $translator;
            }
        }

        return null;
    }

    /**
     * Gets a translator service from the given class or name.
     *
     * @param string $classOrName the service class or name to return
     *
     * @return TranslatorServiceInterface the translator service
     *
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    public function getService(string $classOrName): TranslatorServiceInterface
    {
        $service = $this->find($classOrName);
        if (!$service instanceof TranslatorServiceInterface) {
            throw new ServiceNotFoundException($classOrName);
        }
        $this->setSessionValue(self::KEY_LAST_SERVICE, $classOrName);

        return $service;
    }

    /**
     * Gets the last used translator service from the session.
     *
     * @return TranslatorServiceInterface the translator service or the default (Bing) if not found
     *
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    public function getSessionService(): TranslatorServiceInterface
    {
        $class = $this->getSessionString(self::KEY_LAST_SERVICE, self::DEFAULT_SERVICE);
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

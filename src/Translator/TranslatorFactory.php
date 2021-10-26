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

use App\Traits\SessionTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Factory to provide translator services.
 *
 * @author Laurent Muller
 */
class TranslatorFactory
{
    use SessionTrait;

    /**
     * The default translator service class name (Bing).
     */
    public const DEFAULT_SERVICE = BingTranslatorService::class;

    /**
     * The name of the key to save/retrieve the last used translation service.
     */
    private const KEY_LAST_SERVICE = 'translator_service';

    private AdapterInterface $cache;

    private bool $isDebug;

    private ParameterBagInterface $params;

    /**
     * Constructor.
     */
    public function __construct(ParameterBagInterface $params, AdapterInterface $cache, RequestStack $requestStack, bool $isDebug)
    {
        $this->params = $params;
        $this->cache = $cache;
        $this->requestStack = $requestStack;
        $this->isDebug = $isDebug;
    }

    /**
     * Returns if the given translator service exists.
     *
     * @param string $class the service class to be tested
     *
     * @return bool true if exist
     */
    public function exists(string $class): bool
    {
        $services = $this->getServices();
        foreach ($services as $service) {
            if ($class === $service['class']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a translator service.
     *
     * @param string $class the service class. Can be one of this defined constants.
     *
     * @return TranslatorServiceInterface the translator service
     *
     * @throws ParameterNotFoundException if the service can not be found or if the API key parameter is not defined
     * @template T of TranslatorServiceInterface
     * @psalm-param class-string<T> $class
     */
    public function getService(string $class): TranslatorServiceInterface
    {
        if (!$this->exists($class)) {
            throw new ParameterNotFoundException("The translator service '{$class}' can not be found.");
        }

        // create and save service
        $service = new $class($this->params, $this->cache, $this->isDebug);
        $this->setSessionValue(self::KEY_LAST_SERVICE, $class);

        return $service;
    }

    /**
     * Gets the defined services.
     *
     * Each entry contains the following values:
     * <ul>
     * <li><code>'name'</code>: The service name.</li>
     * <li><code>'class'</code>: The class name.</li>
     * <li><code>'api'</code>: The URL for the API documentation.</li>
     * </ul>
     */
    public function getServices(): array
    {
        return [
            [
                'name' => BingTranslatorService::getName(),
                'class' => BingTranslatorService::getClassName(),
                'api' => BingTranslatorService::getApiUrl(),
            ],
            [
                'name' => GoogleTranslatorService::getName(),
                'class' => GoogleTranslatorService::getClassName(),
                'api' => GoogleTranslatorService::getApiUrl(),
            ],
        ];
    }

    /**
     * Gets the last used translator service from the session.
     *
     * @return TranslatorServiceInterface the translator service or the default (Bing) if not found
     */
    public function getSessionService(): TranslatorServiceInterface
    {
        $class = $this->getSessionValue(self::KEY_LAST_SERVICE, self::DEFAULT_SERVICE);
        if (!$this->exists($class)) {
            $class = self::DEFAULT_SERVICE;
        }

        return $this->getService($class);
    }
}

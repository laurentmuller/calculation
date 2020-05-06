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

use App\Traits\SessionTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

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
     * The name of the key to save/retrieve the last translation service used.
     */
    private const KEY_LAST_SERVICE = 'translator_service';

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * Constructor.
     */
    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, AdapterInterface $cache, SessionInterface $session)
    {
        $this->params = $params;
        $this->kernel = $kernel;
        $this->cache = $cache;
        $this->session = $session;
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
     * @return ITranslatorService the translator service
     *
     * @throws ParameterNotFoundException if the service can not be found or if the API key parameter is not defined
     */
    public function getService(string $class): ITranslatorService
    {
        if (!$this->exists($class)) {
            throw new ParameterNotFoundException("The translator service '{$class}' can not be found.");
        }

        // create and save service
        $service = new $class($this->params, $this->kernel, $this->cache);
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
            [
                'name' => YandexTranslatorService::getName(),
                'class' => YandexTranslatorService::getClassName(),
                'api' => YandexTranslatorService::getApiUrl(),
            ],
        ];
    }

    /**
     * Gets the last used translator service from the session.
     *
     * @return ITranslatorService the translator service or the default (Bing) if not found
     */
    public function getSessionService(): ITranslatorService
    {
        $class = $this->getSessionValue(self::KEY_LAST_SERVICE, self::DEFAULT_SERVICE);
        if (!$this->exists($class)) {
            $class = self::DEFAULT_SERVICE;
        }

        return $this->getService($class);
    }
}

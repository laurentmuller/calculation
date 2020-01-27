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

namespace App\Twig;

use App\Interfaces\IEntityVoter;
use App\Service\CalculationService;
use App\Service\ThemeService;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension to access application class constants.
 *
 * @author Laurent Muller
 */
final class ConstantExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * The key name to cache constants.
     */
    private const CACHE_KEY = 'constant_extension';

    /**
     * The cache timeout (60 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 60;

    /**
     * The cache.
     *
     * @var AdapterInterface
     */
    private $cache;

    /**
     * Constructor.
     */
    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals(): array
    {
        // already in the cache?
        $item = $this->cache->getItem(self::CACHE_KEY);
        if ($item->isHit()) {
            return $item->get();
        }

        // create array
        $values = [
            $this->getShortName(CalculationService::class) => $this->getConstants(CalculationService::class),
            $this->getShortName(ThemeService::class) => $this->getConstants(ThemeService::class),
            $this->getShortName(IEntityVoter::class) => $this->getConstants(IEntityVoter::class),
        ];

        // put to the cache
        $item->expiresAfter(self::CACHE_TIMEOUT)
            ->set($values);
        $this->cache->save($item);

        return $values;
    }

    /**
     * Gets public constants of the given class name.
     *
     * @param string $className the class name to get constants for
     *
     * @return array an array tah hold the constant names and the contant values
     */
    private function getConstants(string $className): array
    {
        $result = [];

        /** @var \ReflectionClassConstant[] $constants */
        $constants = $this->getReflection($className)->getReflectionConstants();
        foreach ($constants as $constant) {
            if ($constant->isPublic()) {
                $result[$constant->getName()] = $constant->getValue();
            }
        }

        return $result;
    }

    /**
     * Gets the reflection class.
     *
     * @param string $className the class name to get reflection for
     *
     * @return \ReflectionClass the reflection class
     */
    private function getReflection(string $className): \ReflectionClass
    {
        return  new \ReflectionClass($className);
    }

    /**
     * Gets short name of the given class name.
     *
     * @param string $className the class name to get short name for
     *
     * @return string the short name
     */
    private function getShortName(string $className): string
    {
        return $this->getReflection($className)->getShortName();
    }
}

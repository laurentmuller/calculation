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

namespace App\Twig;

use App\Interfaces\ActionInterface;
use App\Interfaces\EntityVoterInterface;
use App\Service\CalculationService;
use App\Service\ThemeService;
use App\Util\Utils;
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
            Utils::getShortName(ThemeService::class) => $this->getConstants(ThemeService::class),
            Utils::getShortName(ActionInterface::class) => $this->getConstants(ActionInterface::class),
            Utils::getShortName(CalculationService::class) => $this->getConstants(CalculationService::class),
            Utils::getShortName(EntityVoterInterface::class) => $this->getConstants(EntityVoterInterface::class),
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
     * @return array an array that hold the constant names and the contant values
     */
    private function getConstants(string $className): array
    {
        $result = [];

        /** @var \ReflectionClass $reflection */
        $reflection = new \ReflectionClass($className);

        /** @var \ReflectionClassConstant[] $constants */
        $constants = $reflection->getReflectionConstants();
        foreach ($constants as $constant) {
            if ($constant->isPublic()) {
                $result[$constant->getName()] = $constant->getValue();
            }
        }

        return $result;
    }
}

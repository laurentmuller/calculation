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

use App\Interfaces\EntityVoterInterface;
use App\Service\CalculationService;
use App\Traits\CacheTrait;
use Psr\Cache\CacheItemPoolInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension to access application class constants.
 *
 * @author Laurent Muller
 */
final class ConstantExtension extends AbstractExtension implements GlobalsInterface
{
    use CacheTrait;

    /**
     * The key name to cache constants.
     */
    private const CACHE_KEY = 'constant_extension';

    /**
     * Constructor.
     */
    public function __construct(CacheItemPoolInterface $adapter, bool $isDebug)
    {
        if (!$isDebug) {
            $this->adapter = $adapter;
        }
    }

    /**
     * The callback function used to create constants.
     *
     * @return array the constants
     */
    public function callback(): array
    {
        $values = [];
        $this->addConstants(CalculationService::class, $values);
        $this->addConstants(EntityVoterInterface::class, $values);

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals(): array
    {
        return $this->getCacheValue(self::CACHE_KEY, function (): array {
            return $this->callback();
        });
    }

    /**
     * Adds the public constants of the given class name.
     *
     * @param string $className the class name to get constants for
     * @param array  $values    the array to update
     *
     * @template T
     * @psalm-param class-string<T> $className
     */
    private function addConstants(string $className, array &$values): void
    {
        $reflection = new \ReflectionClass($className);

        /** @var \ReflectionClassConstant[] $constants */
        $constants = \array_filter($reflection->getReflectionConstants(), static function (\ReflectionClassConstant $constant) {
            return $constant->isPublic();
        });

        foreach ($constants as $constant) {
            $values[$constant->getName()] = $constant->getValue();
        }
    }
}

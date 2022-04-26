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

namespace App\Twig;

use App\Interfaces\EntityVoterInterface;
use App\Service\CalculationService;
use App\Traits\CacheTrait;
use Psr\Cache\CacheItemPoolInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension to access application class constants.
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
            $this->setAdapter($adapter);
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
        $this->addIcons($values);

        return $values;
    }

    public function getGlobals(): array
    {
        return (array) $this->getCacheValue(self::CACHE_KEY, fn (): array => $this->callback());
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
        $constants = \array_filter($reflection->getReflectionConstants(), static fn (\ReflectionClassConstant $constant) => $constant->isPublic());

        foreach ($constants as $constant) {
            $values[$constant->getName()] = $constant->getValue();
        }
    }

    private function addIcons(array &$values): void
    {
        $values['ICON_CALCULATION'] = 'calculator';
        $values['ICON_CALCULATIONSTATE'] = 'flag far';
        $values['ICON_CATEGORY'] = 'folder far';
        $values['ICON_CUSTOMER'] = 'address-card far';
        $values['ICON_GLOBALMARGIN'] = 'percent';
        $values['ICON_GROUP'] = 'code-branch';
        $values['ICON_LOG'] = 'book';
        $values['ICON_PRODUCT'] = 'file-alt far';
        $values['ICON_TASK'] = 'tasks';
        $values['ICON_USER'] = 'user far';
    }
}

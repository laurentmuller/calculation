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

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Service\CalculationService;
use App\Traits\CacheTrait;
use Psr\Cache\CacheItemPoolInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension to access global class and icon constants.
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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getGlobals(): array
    {
        return (array) $this->getCacheValue(self::CACHE_KEY, fn (): array => $this->callback());
    }

    /**
     * @throws \ReflectionException
     */
    private function callback(): array
    {
        return \array_merge(
            EntityName::constants(),
            EntityPermission::constants(),
            $this->getConstants(CalculationService::class),
            $this->getIcons()
        );
    }

    /**
     * @psalm-template T
     * @psalm-param class-string<T> $className
     *
     * @throws \ReflectionException
     */
    private function getConstants(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        $constants = \array_filter($reflection->getReflectionConstants(), static fn (\ReflectionClassConstant $c) => $c->isPublic());

        return \array_reduce($constants, static function (array $carry, \ReflectionClassConstant $c): array {
            $carry[$c->getName()] = $c->getValue();

            return $carry;
        }, []);
    }

    private function getIcons(): array
    {
        return [
            // entity
            'ICON_CALCULATION' => 'calculator',
            'ICON_CALCULATION_STATE' => 'flag far',
            'ICON_CATEGORY' => 'folder far',
            'ICON_CUSTOMER' => 'address-card far',
            'ICON_GLOBAL_MARGIN' => 'percent',
            'ICON_GROUP' => 'code-branch',
            'ICON_LOG' => 'book',
            'ICON_PRODUCT' => 'file-alt far',
            'ICON_TASK' => 'tasks',
            'ICON_USER' => 'user far',
            // action
            'ICON_SHOW' => 'tv',
            'ICON_ADD' => 'file far',
            'ICON_EDIT' => 'pencil',
            'ICON_DELETE' => 'times',
            'ICON_COPY' => 'copy far',
            // export
            'ICON_PDF' => 'file-pdf far',
            'ICON_EXCEL' => 'file-excel far',
        ];
    }
}

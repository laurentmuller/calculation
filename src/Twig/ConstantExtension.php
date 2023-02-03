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
use App\Traits\CacheAwareTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension to access global class and icon constants.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConstantExtension extends AbstractExtension implements GlobalsInterface, ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use ServiceSubscriberTrait;

    /**
     * The key name to cache constants.
     */
    private const CACHE_KEY = 'twig_constant_extension';

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getGlobals(): array
    {
        /** @var array<string, mixed> $globals */
        $globals = (array) $this->getCacheValue(self::CACHE_KEY, fn () => $this->getValues());

        return $globals;
    }

    /**
     * Gets the public constants for the given class name.
     *
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    private function getConstants(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        $constants = \array_filter($reflection->getReflectionConstants(), static fn (\ReflectionClassConstant $c) => $c->isPublic());
        $names = \array_map(static fn (\ReflectionClassConstant $c) => $c->getName(), $constants);
        $values = \array_map(static fn (\ReflectionClassConstant $c) => $c->getValue(), $constants);

        return \array_combine($names, $values);
    }

    /**
     * @return array<string, string>
     */
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
            'ICON_SHOW' => 'bookmark far',
            'ICON_ADD' => 'file far',
            'ICON_EDIT' => 'pencil',
            'ICON_DELETE' => 'times',
            'ICON_COPY' => 'copy far',
            // export
            'ICON_PDF' => 'file-pdf far',
            'ICON_EXCEL' => 'file-excel far',
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    private function getValues(): array
    {
        return \array_merge(
            $this->getIcons(),
            EntityName::constants(),
            EntityPermission::constants(),
            $this->getConstants(CalculationService::class)
        );
    }
}

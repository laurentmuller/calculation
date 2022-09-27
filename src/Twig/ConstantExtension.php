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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
     * Constructor.
     */
    public function __construct(
        #[Autowire('%kernel.debug%')]
        bool $isDebug
    ) {
        $this->debugCache = $isDebug;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function getGlobals(): array
    {
        return (array) $this->getCacheValue(self::CACHE_KEY, $this->getValues());
    }

    /**
     * @psalm-template T
     *
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
            'ICON_SHOW' => 'whmcs fab',
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

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
use App\Interfaces\ConstantsInterface;
use App\Service\CalculationGroupService;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension to access global constants.
 *
 * @implements ConstantsInterface<string>
 */
final class ConstantExtension extends AbstractExtension implements ConstantsInterface, GlobalsInterface
{
    public function __construct(
        #[Target('calculation.constant')]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @psalm-return array<string, string>
     */
    public static function constants(): array
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
            'ICON_SHOW' => 'display',
            'ICON_ADD' => 'file far',
            'ICON_EDIT' => 'pencil',
            'ICON_DELETE' => 'times',
            'ICON_COPY' => 'copy far',
            // export
            'ICON_PDF' => 'file-pdf far',
            'ICON_EXCEL' => 'file-excel far',
            'ICON_WORD' => 'file-word far',
        ];
    }

    public function getGlobals(): array
    {
        return $this->cache->get('twig_constant_extension', fn (): array => $this->loadValues());
    }

    /**
     * @psalm-return array<string, string|int>
     */
    private function loadValues(): array
    {
        return \array_merge(
            self::constants(),
            EntityName::constants(),
            EntityPermission::constants(),
            CalculationGroupService::constants()
        );
    }
}

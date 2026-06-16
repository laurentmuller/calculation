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

use App\Constants\CacheAttributes;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\ConstantsInterface;
use App\Interfaces\RoleInterface;
use App\Service\CalculationService;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension to access global constants.
 */
final class ConstantExtension extends AbstractExtension implements ConstantsInterface, GlobalsInterface
{
    public function __construct(
        #[Target(CacheAttributes::CACHE_CONSTANT)]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * This implementation returns icon constants.
     *
     * @return array<string, string>
     */
    #[\Override]
    public static function constants(): array
    {
        return [
            // entity
            'ICON_CALCULATION' => 'fa-solid fa-calculator',
            'ICON_CALCULATION_STATE' => 'fa-regular fa-flag',
            'ICON_CATEGORY' => 'fa-regular fa-folder',
            'ICON_CUSTOMER' => 'fa-regular fa-address-card',
            'ICON_GLOBAL_MARGIN' => 'fa-solid fa-percent',
            'ICON_GROUP' => 'fa-regular fa-folder-closed',
            'ICON_LOG' => 'fa-solid fa-book',
            'ICON_PRODUCT' => 'fa-regular fa-file-alt',
            'ICON_TASK' => 'fa-solid fa-tasks',
            'ICON_USER' => 'fa-regular fa-user',
            // action
            'ICON_SHOW' => 'fa-solid fa-wrench',
            'ICON_ADD' => 'fa-regular fa-file',
            'ICON_EDIT' => 'fa-solid fa-pencil',
            'ICON_DELETE' => 'fa-solid fa-eraser',
            'ICON_COPY' => 'fa-regular fa-copy',
            // export
            'ICON_PDF' => 'fa-regular fa-file-pdf',
            'ICON_EXCEL' => 'fa-regular fa-file-excel',
            'ICON_WORD' => 'fa-regular fa-file-word',
            // view
            'ICON_VIEW_TABLE' => 'fa-solid fa-table-list',
            'ICON_VIEW_CUSTOM' => 'fa-solid fa-grip-horizontal',
        ];
    }

    /**
     * @return array<string, string|int>
     */
    #[\Override]
    public function getGlobals(): array
    {
        return $this->cache->get('twig_constant_extension', $this->loadConstants(...));
    }

    /**
     * @param class-string $class
     *
     * @return array<string, string>
     *
     * @throws \ReflectionException
     */
    private function getClassConstants(string $class): array
    {
        $class = new \ReflectionClass($class);

        /** @var array<string, string> */
        return $class->getConstants(\ReflectionClassConstant::IS_PUBLIC);
    }

    /**
     * @return array<string, string|int>
     *
     * @throws \ReflectionException
     */
    private function loadConstants(): array
    {
        return \array_merge(
            self::constants(),
            EntityName::constants(),
            EntityPermission::constants(),
            CalculationService::constants(),
            $this->getClassConstants(RoleInterface::class),
            $this->getClassConstants(AuthenticatedVoter::class),
        );
    }
}

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

namespace App\Service;

use App\Enums\EntityName;
use App\Interfaces\RoleInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service to get entity names.
 */
readonly class EntityNameService
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private bool $debug,
        private Security $security,
    ) {
    }

    /**
     * @return EntityName[]
     */
    public function getEntities(): array
    {
        return \array_filter(EntityName::sorted(), $this->isGranted(...));
    }

    private function isGranted(EntityName $entityName): bool
    {
        return match ($entityName) {
            EntityName::CALCULATION,
            EntityName::CALCULATION_STATE,
            EntityName::CATEGORY,
            EntityName::GLOBAL_MARGIN,
            EntityName::GROUP,
            EntityName::PRODUCT,
            EntityName::TASK => true,
            EntityName::CUSTOMER => $this->debug,
            EntityName::LOG,
            EntityName::USER => $this->security->isGranted(RoleInterface::ROLE_ADMIN),
        };
    }
}

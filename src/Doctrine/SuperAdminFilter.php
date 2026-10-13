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

namespace App\Doctrine;

use App\Entity\User;
use App\Interfaces\RoleInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * SQL filter to get users where the role name is not 'ROLE_SUPER_ADMIN'.
 */
class SuperAdminFilter extends SQLFilter
{
    /** This filter name. */
    public const string NAME = 'super_admin_filter';

    #[\Override]
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (User::class !== $targetEntity->name) {
            return '';
        }

        return \sprintf(
            "IFNULL(%s.role, '%s') <> '%s'",
            $targetTableAlias,
            RoleInterface::ROLE_USER,
            RoleInterface::ROLE_SUPER_ADMIN
        );
    }
}

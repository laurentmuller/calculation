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

namespace App\Tests\Controller;

use App\Controller\CalculationController;
use App\Tests\EntityTrait\CalculationStateTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\GroupTrait;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationController::class)]
class CalculationControllerTest extends AbstractControllerTestCase
{
    use CalculationStateTrait;
    use CalculationTrait;
    use CategoryTrait;
    use GroupTrait;

    public static function getRoutes(): array
    {
        return [
            ['/calculation', self::ROLE_USER],
            ['/calculation', self::ROLE_ADMIN],
            ['/calculation', self::ROLE_SUPER_ADMIN],

            ['/calculation/add', self::ROLE_USER],
            ['/calculation/add', self::ROLE_ADMIN],
            ['/calculation/add', self::ROLE_SUPER_ADMIN],

            ['/calculation/edit/1', self::ROLE_USER],
            ['/calculation/edit/1', self::ROLE_ADMIN],
            ['/calculation/edit/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/state/1', self::ROLE_USER],
            ['/calculation/state/1', self::ROLE_ADMIN],
            ['/calculation/state/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/delete/1', self::ROLE_USER],
            ['/calculation/delete/1', self::ROLE_ADMIN],
            ['/calculation/delete/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/clone/1', self::ROLE_USER],
            ['/calculation/clone/1', self::ROLE_ADMIN],
            ['/calculation/clone/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/show/1', self::ROLE_USER],
            ['/calculation/show/1', self::ROLE_ADMIN],
            ['/calculation/show/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/pdf/1', self::ROLE_USER],
            ['/calculation/pdf/1', self::ROLE_ADMIN],
            ['/calculation/pdf/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/pdf', self::ROLE_USER],
            ['/calculation/pdf', self::ROLE_ADMIN],
            ['/calculation/pdf', self::ROLE_SUPER_ADMIN],

            ['/calculation/excel', self::ROLE_USER],
            ['/calculation/excel', self::ROLE_ADMIN],
            ['/calculation/excel', self::ROLE_SUPER_ADMIN],

            ['/calculation/excel/1', self::ROLE_USER],
            ['/calculation/excel/1', self::ROLE_ADMIN],
            ['/calculation/excel/1', self::ROLE_SUPER_ADMIN],

            ['/calculation?search=22', self::ROLE_USER],
            ['/calculation?search=22', self::ROLE_ADMIN],
            ['/calculation?search=22', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $this->getCategory($this->getGroup());
        $this->getCalculation($this->getCalculationState());
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteCategory();
        $this->deleteGroup();
        $this->deleteCalculationState();
    }
}

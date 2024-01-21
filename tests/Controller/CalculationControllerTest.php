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
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\CategoryTrait;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationController::class)]
class CalculationControllerTest extends AbstractControllerTestCase
{
    use CalculationTrait;
    use CategoryTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/calculation', self::ROLE_USER];
        yield ['/calculation', self::ROLE_ADMIN];
        yield ['/calculation', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/add', self::ROLE_USER];
        yield ['/calculation/add', self::ROLE_ADMIN];
        yield ['/calculation/add', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/edit/1', self::ROLE_USER];
        yield ['/calculation/edit/1', self::ROLE_ADMIN];
        yield ['/calculation/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/state/1', self::ROLE_USER];
        yield ['/calculation/state/1', self::ROLE_ADMIN];
        yield ['/calculation/state/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/delete/1', self::ROLE_USER];
        yield ['/calculation/delete/1', self::ROLE_ADMIN];
        yield ['/calculation/delete/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/clone/1', self::ROLE_USER];
        yield ['/calculation/clone/1', self::ROLE_ADMIN];
        yield ['/calculation/clone/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/show/1', self::ROLE_USER];
        yield ['/calculation/show/1', self::ROLE_ADMIN];
        yield ['/calculation/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/pdf/1', self::ROLE_USER];
        yield ['/calculation/pdf/1', self::ROLE_ADMIN];
        yield ['/calculation/pdf/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/pdf', self::ROLE_USER];
        yield ['/calculation/pdf', self::ROLE_ADMIN];
        yield ['/calculation/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/excel', self::ROLE_USER];
        yield ['/calculation/excel', self::ROLE_ADMIN];
        yield ['/calculation/excel', self::ROLE_SUPER_ADMIN];
        yield ['/calculation/excel/1', self::ROLE_USER];
        yield ['/calculation/excel/1', self::ROLE_ADMIN];
        yield ['/calculation/excel/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculation?search=22', self::ROLE_USER];
        yield ['/calculation?search=22', self::ROLE_ADMIN];
        yield ['/calculation?search=22', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $group = $this->getGroup();
        $this->getCategory($group);
        $state = $this->getCalculationState();
        $this->getCalculation($state);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCalculation();
        $this->deleteCategory();
    }
}

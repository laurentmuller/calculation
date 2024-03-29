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

use App\Controller\CalculationStateController;
use App\Tests\EntityTrait\CalculationStateTrait;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationStateController::class)]
class CalculationStateControllerTest extends AbstractControllerTestCase
{
    use CalculationStateTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/calculationstate', self::ROLE_USER];
        yield ['/calculationstate', self::ROLE_ADMIN];
        yield ['/calculationstate', self::ROLE_SUPER_ADMIN];
        yield ['/calculationstate/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculationstate/add', self::ROLE_ADMIN];
        yield ['/calculationstate/add', self::ROLE_SUPER_ADMIN];
        yield ['/calculationstate/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculationstate/edit/1', self::ROLE_ADMIN];
        yield ['/calculationstate/edit/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculationstate/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/calculationstate/delete/1', self::ROLE_ADMIN];
        yield ['/calculationstate/delete/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculationstate/show/1', self::ROLE_USER];
        yield ['/calculationstate/show/1', self::ROLE_ADMIN];
        yield ['/calculationstate/show/1', self::ROLE_SUPER_ADMIN];
        yield ['/calculationstate/pdf', self::ROLE_USER];
        yield ['/calculationstate/pdf', self::ROLE_ADMIN];
        yield ['/calculationstate/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/calculationstate/excel', self::ROLE_USER];
        yield ['/calculationstate/excel', self::ROLE_ADMIN];
        yield ['/calculationstate/excel', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        $this->getCalculationState();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        $this->deleteCalculationState();
    }
}

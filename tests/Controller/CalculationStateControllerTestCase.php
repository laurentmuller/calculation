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
use App\Entity\CalculationState;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationStateController::class)]
class CalculationStateControllerTestCase extends AbstractControllerTestCase
{
    private static ?CalculationState $entity = null;

    public static function getRoutes(): array
    {
        return [
            ['/calculationstate', self::ROLE_USER],
            ['/calculationstate', self::ROLE_ADMIN],
            ['/calculationstate', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculationstate/add', self::ROLE_ADMIN],
            ['/calculationstate/add', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculationstate/edit/1', self::ROLE_ADMIN],
            ['/calculationstate/edit/1', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculationstate/delete/1', self::ROLE_ADMIN],
            ['/calculationstate/delete/1', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/show/1', self::ROLE_USER],
            ['/calculationstate/show/1', self::ROLE_ADMIN],
            ['/calculationstate/show/1', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/pdf', self::ROLE_USER],
            ['/calculationstate/pdf', self::ROLE_ADMIN],
            ['/calculationstate/pdf', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/excel', self::ROLE_USER],
            ['/calculationstate/excel', self::ROLE_ADMIN],
            ['/calculationstate/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        if (null === self::$entity) {
            self::$entity = new CalculationState();
            self::$entity->setCode('Test Code');
            $this->addEntity(self::$entity);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntities(): void
    {
        self::$entity = $this->deleteEntity(self::$entity);
    }
}
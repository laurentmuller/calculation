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

use App\Controller\GlobalMarginController;
use App\Entity\GlobalMargin;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(GlobalMarginController::class)]
class GlobalMarginControllerTestCase extends AbstractControllerTestCase
{
    private static ?GlobalMargin $entity = null;

    public static function getRoutes(): array
    {
        return [
            ['/globalmargin', self::ROLE_USER],
            ['/globalmargin', self::ROLE_ADMIN],
            ['/globalmargin', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/edit', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/globalmargin/edit', self::ROLE_ADMIN],
            ['/globalmargin/edit', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/show/1', self::ROLE_USER],
            ['/globalmargin/show/1', self::ROLE_ADMIN],
            ['/globalmargin/show/1', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/pdf', self::ROLE_USER],
            ['/globalmargin/pdf', self::ROLE_ADMIN],
            ['/globalmargin/pdf', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/excel', self::ROLE_USER],
            ['/globalmargin/excel', self::ROLE_ADMIN],
            ['/globalmargin/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function addEntities(): void
    {
        if (null === self::$entity) {
            self::$entity = new GlobalMargin();
            self::$entity->setMinimum(0)
                ->setMaximum(100)
                ->setMargin(0.1);
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

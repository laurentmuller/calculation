<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\DigiPrint;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\DigiPrintController} class.
 *
 * @author Laurent Muller
 */
class DigiPrintControllerTest extends AbstractControllerTest
{
    private static ?DigiPrint $entity = null;

    public function getRoutes(): array
    {
        return [
            ['/digiprint', self::ROLE_USER],
            ['/digiprint', self::ROLE_ADMIN],
            ['/digiprint', self::ROLE_SUPER_ADMIN],

            ['/digiprint/table', self::ROLE_USER],
            ['/digiprint/table', self::ROLE_ADMIN],
            ['/digiprint/table', self::ROLE_SUPER_ADMIN],

            ['/digiprint/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/digiprint/add', self::ROLE_ADMIN],
            ['/digiprint/add', self::ROLE_SUPER_ADMIN],

            ['/digiprint/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/digiprint/edit/1', self::ROLE_ADMIN],
            ['/digiprint/edit/1', self::ROLE_SUPER_ADMIN],

            ['/digiprint/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/digiprint/delete/1', self::ROLE_ADMIN],
            ['/digiprint/delete/1', self::ROLE_SUPER_ADMIN],

            ['/digiprint/show/1', self::ROLE_USER],
            ['/digiprint/show/1', self::ROLE_ADMIN],
            ['/digiprint/show/1', self::ROLE_SUPER_ADMIN],

            ['/digiprint/pdf', self::ROLE_USER],
            ['/digiprint/pdf', self::ROLE_ADMIN],
            ['/digiprint/pdf', self::ROLE_SUPER_ADMIN],

            ['/digiprint/excel', self::ROLE_USER],
            ['/digiprint/excel', self::ROLE_ADMIN],
            ['/digiprint/excel', self::ROLE_SUPER_ADMIN],
        ];
    }

    protected function addEntities(): void
    {
        if (null === self::$entity) {
            self::$entity = new DigiPrint();
            self::$entity->setFormat('A4');
            $this->addEntity(self::$entity);
        }
    }

    protected function deleteEntities(): void
    {
        self::$entity = $this->deleteEntity(self::$entity);
    }
}

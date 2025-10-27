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

use App\Entity\GlobalMargin;
use App\Tests\EntityTrait\GlobalMarginTrait;
use Symfony\Component\HttpFoundation\Response;

final class GlobalMarginControllerTest extends EntityControllerTestCase
{
    use GlobalMarginTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/globalmargin', self::ROLE_USER];
        yield ['/globalmargin', self::ROLE_ADMIN];
        yield ['/globalmargin', self::ROLE_SUPER_ADMIN];

        yield ['/globalmargin/edit', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/globalmargin/edit', self::ROLE_ADMIN];
        yield ['/globalmargin/edit', self::ROLE_SUPER_ADMIN];

        yield ['/globalmargin/show/1', self::ROLE_USER];
        yield ['/globalmargin/show/1', self::ROLE_ADMIN];
        yield ['/globalmargin/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/globalmargin/pdf', self::ROLE_USER];
        yield ['/globalmargin/pdf', self::ROLE_ADMIN];
        yield ['/globalmargin/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/globalmargin/excel', self::ROLE_USER];
        yield ['/globalmargin/excel', self::ROLE_ADMIN];
        yield ['/globalmargin/excel', self::ROLE_SUPER_ADMIN];
    }

    public function testEdit(): void
    {
        $this->deleteEntitiesByClass(GlobalMargin::class);
        $this->addEntities();
        $uri = '/globalmargin/edit';
        $this->checkEditEntity($uri, id: 'common.button_ok');
    }

    public function testExcelEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/globalmargin/excel', GlobalMargin::class);
    }

    public function testPdfEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/globalmargin/pdf', GlobalMargin::class);
    }

    #[\Override]
    protected function addEntities(): void
    {
        $this->getGlobalMargin();
    }

    #[\Override]
    protected function deleteEntities(): void
    {
        $this->deleteGlobalMargin();
    }
}

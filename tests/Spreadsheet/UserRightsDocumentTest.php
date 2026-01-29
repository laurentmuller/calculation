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

namespace App\Tests\Spreadsheet;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Parameter\ApplicationParameters;
use App\Parameter\RightsParameter;
use App\Service\RoleBuilderService;
use App\Service\RoleService;
use App\Spreadsheet\UserRightsDocument;
use PHPUnit\Framework\TestCase;

final class UserRightsDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('getRights')
            ->willReturn(new RightsParameter());

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getApplicationParameters')
            ->willReturn($parameters);

        $roleService = self::createStub(RoleService::class);
        $roleBuilderService = new RoleBuilderService();

        $users = [];
        foreach (\range(1, 5) as $index) {
            $users[] = (new User())
                ->setUsername(\sprintf('User %d', $index))
                ->setRole(RoleInterface::ROLE_SUPER_ADMIN);
        }

        $document = new UserRightsDocument(
            $controller,
            $users,
            $roleService,
            $roleBuilderService
        );
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $controller = self::createStub(AbstractController::class);
        $roleService = self::createStub(RoleService::class);
        $roleBuilderService = self::createStub(RoleBuilderService::class);

        $report = new UserRightsDocument($controller, [], $roleService, $roleBuilderService);
        $actual = $report->render();
        self::assertFalse($actual);
    }
}

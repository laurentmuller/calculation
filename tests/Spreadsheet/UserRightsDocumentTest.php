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
use App\Service\ApplicationService;
use App\Service\RoleBuilderService;
use App\Service\RoleService;
use App\Spreadsheet\UserRightsDocument;
use PHPUnit\Framework\TestCase;

final class UserRightsDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $roleService = $this->createMock(RoleService::class);
        $roleBuilderService = new RoleBuilderService();

        $application = $this->createMock(ApplicationService::class);
        $application->method('getAdminRole')
            ->willReturn($roleBuilderService->getRoleAdmin());
        $application->method('getUserRole')
            ->willReturn($roleBuilderService->getRoleUser());

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getApplicationService')
            ->willReturn($application);

        $user = new User();
        $user->setUsername('UserName')
            ->setRole(RoleInterface::ROLE_SUPER_ADMIN);

        $document = new UserRightsDocument(
            $controller,
            [$user],
            $roleService,
            $roleBuilderService
        );
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $roleService = $this->createMock(RoleService::class);
        $roleBuilderService = $this->createMock(RoleBuilderService::class);

        $report = new UserRightsDocument($controller, [], $roleService, $roleBuilderService);
        $actual = $report->render();
        self::assertFalse($actual);
    }
}

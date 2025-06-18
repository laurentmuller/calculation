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

namespace App\Tests\Report;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Report\UsersRightsReport;
use App\Service\ApplicationService;
use App\Service\FontAwesomeService;
use App\Service\RoleBuilderService;
use App\Service\RoleService;
use PHPUnit\Framework\TestCase;

class UsersRightsReportTest extends TestCase
{
    public function testRender(): void
    {
        $roleBuilderService = new RoleBuilderService();

        $application = $this->createMock(ApplicationService::class);
        $application->method('getAdminRole')
            ->willReturn($roleBuilderService->getRoleAdmin());
        $application->method('getUserRole')
            ->willReturn($roleBuilderService->getRoleUser());

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getApplicationService')
            ->willReturn($application);
        $roleService = $this->createMock(RoleService::class);
        $fontAwesomeService = $this->createMock(FontAwesomeService::class);

        $user = new User();
        $user->setUsername('UserName')
            ->setRole(RoleInterface::ROLE_SUPER_ADMIN);

        $report = new UsersRightsReport(
            $controller,
            [$user],
            $roleService,
            $fontAwesomeService,
            $roleBuilderService
        );
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $roleService = $this->createMock(RoleService::class);
        $fontAwesomeService = $this->createMock(FontAwesomeService::class);
        $roleBuilderService = $this->createMock(RoleBuilderService::class);

        $report = new UsersRightsReport(
            $controller,
            [],
            $roleService,
            $fontAwesomeService,
            $roleBuilderService
        );
        $actual = $report->render();
        self::assertFalse($actual);
    }
}

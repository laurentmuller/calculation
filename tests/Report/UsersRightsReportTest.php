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
use App\Parameter\ApplicationParameters;
use App\Parameter\RightsParameter;
use App\Report\UsersRightsReport;
use App\Service\FontAwesomeService;
use App\Service\RoleBuilderService;
use App\Service\RoleService;
use PHPUnit\Framework\TestCase;

final class UsersRightsReportTest extends TestCase
{
    public function testRender(): void
    {
        $parameters = $this->createMock(ApplicationParameters::class);
        $parameters->method('getRights')
            ->willReturn(new RightsParameter());

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getApplicationParameters')
            ->willReturn($parameters);

        $roleService = $this->createMock(RoleService::class);
        $roleBuilderService = new RoleBuilderService();
        $fontAwesomeService = $this->createMock(FontAwesomeService::class);

        $users = [];
        foreach (\range(1, 5) as $index) {
            $users[] = (new User())
                ->setUsername(\sprintf('User %d', $index))
                ->setRole(RoleInterface::ROLE_SUPER_ADMIN);
        }

        $report = new UsersRightsReport(
            $controller,
            $users,
            $roleService,
            $roleBuilderService,
            $fontAwesomeService
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
            $roleBuilderService,
            $fontAwesomeService
        );
        $actual = $report->render();
        self::assertFalse($actual);
    }
}

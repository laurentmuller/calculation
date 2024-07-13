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
use App\Service\RoleBuilderService;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class UsersRightsReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $service = new RoleBuilderService();

        $application = $this->createMock(ApplicationService::class);
        $application->method('getAdminRole')
            ->willReturn($service->getRoleAdmin());
        $application->method('getUserRole')
            ->willReturn($service->getRoleUser());

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getApplicationService')
            ->willReturn($application);

        $builder = $this->createMock(RoleBuilderService::class);

        $user = new User();
        $user->setUsername('UserName')
            ->setRole(RoleInterface::ROLE_ADMIN);

        $report = new UsersRightsReport($controller, [$user], $builder);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $builder = $this->createMock(RoleBuilderService::class);

        $report = new UsersRightsReport($controller, [], $builder);
        $actual = $report->render();
        self::assertFalse($actual);
    }
}

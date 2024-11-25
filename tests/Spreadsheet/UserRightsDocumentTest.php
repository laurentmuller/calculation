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
use App\Spreadsheet\UserRightsDocument;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class UserRightsDocumentTest extends TestCase
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

        $user = new User();
        $user->setUsername('UserName')
            ->setRole(RoleInterface::ROLE_SUPER_ADMIN);

        $controller = $this->createMock(AbstractController::class);
        $document = new UserRightsDocument($controller, [$user], $service);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(RoleBuilderService::class);

        $report = new UserRightsDocument($controller, [], $service);
        $actual = $report->render();
        self::assertFalse($actual);
    }
}

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
use App\Report\UsersReport;
use App\Service\FontAwesomeService;
use App\Service\RoleService;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Storage\StorageInterface;

class UsersReportTest extends TestCase
{
    public function testWithImageGreater(): void
    {
        $defaultImage = __DIR__ . '/../../public/images/flags/ad.png';
        $report = $this->createReport($defaultImage);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithImageInvalid(): void
    {
        $defaultImage = __DIR__ . '/fake.png';
        $report = $this->createReport($defaultImage);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithImageSame(): void
    {
        $defaultImage = __DIR__ . '/../../public/images/flags/ch.png';
        $report = $this->createReport($defaultImage);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithImageSmaller(): void
    {
        $defaultImage = __DIR__ . '/../../public/images/avatar.png';
        $report = $this->createReport($defaultImage);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testWithoutImage(): void
    {
        $report = $this->createReport();
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createReport(?string $imagePath = null): UsersReport
    {
        $controller = $this->createMock(AbstractController::class);
        $roleService = $this->createMock(RoleService::class);
        $storage = $this->createMock(StorageInterface::class);
        $fontService = $this->createMock(FontAwesomeService::class);

        $user1 = new User();
        $user1->updateLastLogin();

        $user2 = $this->createMock(User::class);
        $user2->method('getRole')
            ->willReturn(RoleInterface::ROLE_USER);
        $user2->method('getImagePath')
            ->willReturn($imagePath);

        return new UsersReport(
            $controller,
            [$user1, $user2],
            $storage,
            $roleService,
            $fontService
        );
    }
}

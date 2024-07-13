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
use App\Report\UsersReport;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Storage\StorageInterface;

class UsersReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $storage = $this->createMock(StorageInterface::class);

        $user1 = new User();
        $user1->updateLastLogin();

        $user2 = $this->createMock(User::class);
        $user2->method('getImagePath')
            ->willReturn(__DIR__ . '/../Data/images/example.png');

        $report = new UsersReport($controller, [$user1, $user2], $storage);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}

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
use App\Service\RoleService;
use App\Spreadsheet\UsersDocument;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Storage\StorageInterface;

final class UsersDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $users = $this->createUsers();
        $controller = self::createStub(AbstractController::class);
        $roleService = self::createStub(RoleService::class);
        $storage = self::createStub(StorageInterface::class);
        $document = new UsersDocument($controller, $users, $roleService, $storage);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    /**
     * @return User[]
     */
    private function createUsers(): array
    {
        $user1 = new User();
        $user1->updateLastLogin();

        $user2 = $this->createMock(User::class);
        $user2->method('getImagePath')
            ->willReturn(__DIR__ . '/../files/images/example.png');

        return [$user1, $user2];
    }
}

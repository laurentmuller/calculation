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

use App\Entity\Task;
use App\Interfaces\PropertyServiceInterface;
use App\Service\ApplicationService;
use App\Tests\EntityTrait\TaskItemTrait;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends EntityControllerTestCase
{
    use TaskItemTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/task', self::ROLE_USER];
        yield ['/task', self::ROLE_ADMIN];
        yield ['/task', self::ROLE_SUPER_ADMIN];

        yield ['/task/add', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/add', self::ROLE_ADMIN];
        yield ['/task/add', self::ROLE_SUPER_ADMIN];

        yield ['/task/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/edit/1', self::ROLE_ADMIN];
        yield ['/task/edit/1', self::ROLE_SUPER_ADMIN];

        yield ['/task/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/clone/1', self::ROLE_ADMIN];
        yield ['/task/clone/1', self::ROLE_SUPER_ADMIN];

        yield ['/task/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/delete/1', self::ROLE_ADMIN];
        yield ['/task/delete/1', self::ROLE_SUPER_ADMIN];

        yield ['/task/clone/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/task/clone/1', self::ROLE_ADMIN];
        yield ['/task/clone/1', self::ROLE_SUPER_ADMIN];

        yield ['/task/show/1', self::ROLE_USER];
        yield ['/task/show/1', self::ROLE_ADMIN];
        yield ['/task/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/task/pdf', self::ROLE_USER];
        yield ['/task/pdf', self::ROLE_ADMIN];
        yield ['/task/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/task/excel', self::ROLE_USER];
        yield ['/task/excel', self::ROLE_ADMIN];
        yield ['/task/excel', self::ROLE_SUPER_ADMIN];

        yield ['/task/compute/1', self::ROLE_USER];
        yield ['/task/compute/1', self::ROLE_ADMIN];
        yield ['/task/compute/1', self::ROLE_SUPER_ADMIN];
    }

    public function testAdd(): void
    {
        $category = $this->getCategory();
        $service = $this->getService(ApplicationService::class);
        $service->setProperties([
            PropertyServiceInterface::P_DEFAULT_CATEGORY => $category,
        ]);
        $data = [
            'task[name]' => 'Name',
            'task[category]' => $category->getId(),
            'task[unit]' => 'm2',
            'task[supplier]' => 'Supplier',
        ];
        $this->checkAddEntity('/task/add', $data);
    }

    public function testComputeEmpty(): void
    {
        $uri = '/task/compute/1';
        $this->checkUriWithEmptyEntity($uri, Task::class, expected: Response::HTTP_FOUND);
    }

    public function testDelete(): void
    {
        $this->addEntities();
        $uri = \sprintf('/task/delete/%d', (int) $this->getTask()->getId());
        $this->checkDeleteEntity($uri);
    }

    public function testEdit(): void
    {
        $this->addEntities();
        $uri = \sprintf('/task/edit/%d', (int) $this->getTask()->getId());
        $data = [
            'task[name]' => 'New Name',
            'task[category]' => $this->getCategory()->getId(),
            'task[unit]' => 'km',
            'task[supplier]' => 'New Supplier',
        ];
        $this->checkEditEntity($uri, $data);
    }

    public function testExcelEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/task/excel', Task::class);
    }

    public function testPdfEmpty(): void
    {
        $this->checkUriWithEmptyEntity('/task/pdf', Task::class);
    }

    protected function addEntities(): void
    {
        $this->getTaskItem();
    }

    protected function deleteEntities(): void
    {
        $this->deleteTaskItem();
    }
}

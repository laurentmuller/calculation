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

namespace App\Tests\Service;

use App\Service\HelpService;
use App\Tests\KernelServiceTestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HelpServiceTest extends KernelServiceTestCase
{
    private HelpService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(HelpService::class);
    }

    public function testFindAction(): void
    {
        $actual = $this->service->findAction('list_search');
        self::assertNotEmpty($actual);

        $actual = $this->service->findAction('fake_value_to_search');
        self::assertNull($actual);
    }

    public function testFindDialog(): void
    {
        $actual = $this->service->findDialog('index.title');
        self::assertNotEmpty($actual);

        $actual = $this->service->findDialog('fake_value_to_search');
        self::assertNull($actual);
    }

    public function testFindEntity(): void
    {
        $actual = $this->service->findEntity('product');
        self::assertNotEmpty($actual);

        $actual = $this->service->findEntity(['entity' => 'product']);
        self::assertNotEmpty($actual);

        $actual = $this->service->findEntity([]);
        self::assertNull($actual);

        $actual = $this->service->findEntity(['entity' => null]);
        self::assertNull($actual);

        $actual = $this->service->findEntity('fake_value_to_search');
        self::assertNull($actual);

        $actual = $this->service->findEntity();
        self::assertNull($actual);
    }

    public function testGetActions(): void
    {
        $actual = $this->service->getActions();
        self::assertNotEmpty($actual);
    }

    public function testGetDialogs(): void
    {
        $actual = $this->service->getDialogs();
        self::assertNotEmpty($actual);
    }

    public function testGetDialogsByGroup(): void
    {
        $actual = $this->service->getDialogsByGroup();
        self::assertNotEmpty($actual);
    }

    public function testGetEntities(): void
    {
        $actual = $this->service->getEntities();
        self::assertNotEmpty($actual);
    }

    public function testGetImagePath(): void
    {
        $actual = $this->service->getImagePath();
        self::assertStringEndsWith('/images', $actual);
    }

    public function testGetMainMenu(): void
    {
        $actual = $this->service->getMainMenu();
        self::assertNotEmpty($actual['menus']);
    }

    public function testGetMainMenus(): void
    {
        $actual = $this->service->getMainMenus();
        self::assertNotEmpty($actual);
    }

    public function testInvalidPath(): void
    {
        $cache = $this->getService(CacheInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $service = new HelpService(__DIR__, __DIR__, $cache, $translator);

        $actual = $service->getActions();
        self::assertEmpty($actual);

        $actual = $service->getDialogs();
        self::assertEmpty($actual);

        $actual = $service->getDialogsByGroup();
        self::assertEmpty($actual);

        $actual = $service->getEntities();
        self::assertEmpty($actual);

        $actual = $service->getMainMenu();
        self::assertArrayNotHasKey('image', $actual);
        self::assertArrayNotHasKey('description', $actual);
        self::assertArrayNotHasKey('menus', $actual);
    }

    public function testMergeAction(): void
    {
        $expected = [];
        $actual = $this->service->mergeAction($expected);
        self::assertSame($actual, $expected);

        $expected = ['action' => 'fake_value_to_search'];
        $actual = $this->service->mergeAction($expected);
        self::assertSame($actual, $expected);

        $expected = ['action' => 'list_search'];
        $actual = $this->service->mergeAction($expected);
        self::assertArrayHasKey('action', $actual);
        self::assertArrayHasKey('id', $actual);
        self::assertArrayHasKey('icon', $actual);
        self::assertArrayHasKey('description', $actual);
    }

    public function testSortByName(): void
    {
        $expected = [
            0 => ['name' => 'A'],
            1 => ['name' => 'B'],
        ];
        $actual = [
            1 => ['name' => 'B'],
            0 => ['name' => 'A'],
        ];
        $this->service->sortByName($actual);
        self::assertSame($expected, $actual);
    }
}

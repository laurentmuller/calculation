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
use App\Tests\ContainerServiceTrait;
use App\Tests\KernelServiceTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(HelpService::class)]
class HelpServiceTest extends KernelServiceTestCase
{
    use ContainerServiceTrait;

    private HelpService $service;

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

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testFindEntity(): void
    {
        $actual = $this->service->findEntity('product');
        self::assertNotEmpty($actual);
        // @phpstan-ignore argument.type
        $actual = $this->service->findEntity(['entity' => 'product']);
        self::assertNotEmpty($actual);
        // @phpstan-ignore argument.type
        $actual = $this->service->findEntity([]);
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

    public function testGetFile(): void
    {
        $actual = $this->service->getFile();
        self::assertStringEndsWith('help.json', $actual);
    }

    public function testGetImagePath(): void
    {
        $actual = $this->service->getImagePath();
        self::assertStringEndsWith('/images', $actual);
    }

    public function testGetMainMenu(): void
    {
        $actual = $this->service->getMainMenu();
        self::assertNotEmpty($actual);
    }

    public function testGetMainMenus(): void
    {
        $actual = $this->service->getMainMenus();
        self::assertNotEmpty($actual);
    }

    public function testHelp(): void
    {
        $help = $this->service->getHelp();
        self::assertNotEmpty($help);
    }

    /**
     * @throws Exception
     */
    public function testInvalidFile(): void
    {
        $file = __FILE__;
        $imagePath = __DIR__;
        $cache = $this->getService(CacheInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $service = new HelpService($file, $imagePath, $cache, $translator);

        $help = $service->getHelp();
        self::assertEmpty($help['actions']);
        self::assertEmpty($help['dialogs']);
        self::assertEmpty($help['entities']);
        self::assertNull($help['mainMenu']['image']);
        self::assertNull($help['mainMenu']['description']);
        self::assertEmpty($help['mainMenu']['menus']);
        self::assertEmpty($service->getDialogsByGroup());
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
}

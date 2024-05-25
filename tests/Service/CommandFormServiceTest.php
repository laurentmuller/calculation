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

use App\Service\CommandFormService;
use App\Service\CommandService;
use App\Tests\KernelServiceTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Form\FormInterface;

/**
 * @psalm-import-type CommandType from CommandService
 */
#[CoversClass(CommandFormService::class)]
class CommandFormServiceTest extends KernelServiceTestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function testCreateForm(): void
    {
        $form = $this->createForm('completion');
        self::assertTrue($form->has('argument-shell'));
        self::assertTrue($form->has('option-help'));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCreateFormMultiple(): void
    {
        $form = $this->createForm('cache:pool:invalidate-tags', [
            'argument-tags' => [],
            'option-pool' => [],
        ]);
        self::assertTrue($form->has('argument-tags'));
        self::assertTrue($form->has('option-pool'));

        $view = $form->createView();
        $commandFormService = $this->getCommandFormService();

        $filtered = $commandFormService->filter($view, CommandFormService::ARGUMENT_TEXT);
        self::assertCount(1, $filtered);
        $filtered = $commandFormService->filter($view, CommandFormService::OPTION_TEXT);
        self::assertCount(2, $filtered);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFilter(): void
    {
        $form = $this->createForm('completion');
        $view = $form->createView();
        $commandFormService = $this->getCommandFormService();

        $filtered = $commandFormService->filter($view, CommandFormService::ARGUMENT_TEXT);
        self::assertCount(1, $filtered);
        $filtered = $commandFormService->filter($view, CommandFormService::ARGUMENT_BOOL);
        self::assertCount(0, $filtered);
    }

    /**
     * @psalm-param array<string, array|scalar|null> $data
     *
     * @throws InvalidArgumentException
     */
    private function createForm(string $name = 'about', array $data = []): FormInterface
    {
        $command = $this->getCommand($name);
        $service = $this->getCommandFormService();

        return $service->createForm($command, $data, ['csrf_protection' => false]);
    }

    /**
     * @psalm-return CommandType
     *
     * @phpstan-return array
     *
     * @throws InvalidArgumentException
     */
    private function getCommand(string $name): array
    {
        $service = $this->getCommandService();
        $command = $service->getCommand($name);
        self::assertIsArray($command);

        return $command;
    }

    private function getCommandFormService(): CommandFormService
    {
        return $this->getService(CommandFormService::class);
    }

    private function getCommandService(): CommandService
    {
        return $this->getService(CommandService::class);
    }
}

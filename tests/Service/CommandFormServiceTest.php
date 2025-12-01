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
use Symfony\Component\Form\FormInterface;

/**
 * @phpstan-import-type CommandType from CommandService
 */
final class CommandFormServiceTest extends KernelServiceTestCase
{
    public function testBoolArgument(): void
    {
        $argument = [
            'name' => 'Argument',
            'shortcut' => '',
            'shortcutName' => '',
            'description' => 'Description',
            'isRequired' => false,
            'isArray' => false,
            'isAcceptValue' => false,
            'default' => 'Default',
            'display' => 'Display',
            'arguments' => 'Arguments',
        ];
        $command = [
            'name' => 'fake',
            'description' => 'fake',
            'usage' => [],
            'help' => 'fake',
            'hidden' => false,
            'arguments' => ['fake' => $argument],
            'options' => [],
        ];
        $service = $this->getCommandFormService();
        $actual = $service->createForm($command, []);
        self::assertCount(1, $actual);
        self::assertTrue($actual->has('argument-fake'));
    }

    public function testCreateForm(): void
    {
        $form = $this->createForm('completion');
        self::assertTrue($form->has('argument-shell'));
        self::assertTrue($form->has('option-help'));
    }

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

    public function testFilter(): void
    {
        $form = $this->createForm('completion');
        $view = $form->createView();
        $commandFormService = $this->getCommandFormService();

        $filtered = $commandFormService->filter($view, CommandFormService::ARGUMENT_TEXT);
        self::assertCount(1, $filtered);
        $filtered = $commandFormService->filter($view, CommandFormService::ARGUMENT_BOOL);
        self::assertEmpty($filtered);
    }

    /**
     * @phpstan-param array<string, array|scalar|null> $data
     *
     * @phpstan-return FormInterface<mixed>
     */
    private function createForm(string $name = 'about', array $data = []): FormInterface
    {
        $command = $this->getCommand($name);
        $service = $this->getCommandFormService();

        return $service->createForm($command, $data, ['csrf_protection' => false]);
    }

    /**
     * @phpstan-return CommandType
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

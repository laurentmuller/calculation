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
use App\Utils\FileUtils;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

/**
 * @phpstan-import-type CommandType from CommandService
 */
final class CommandFormServiceTest extends FormIntegrationTestCase
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
            'extra' => 'extra',
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
        $form = $this->createForm();
        self::assertTrue($form->has('argument-shell'));
        self::assertTrue($form->has('option-help'));
    }

    public function testCreateFormMultiple(): void
    {
        $form = $this->createForm('cache_pool_invalidate_tags', [
            'argument-tags' => [],
            'option-pool' => [],
        ]);
        self::assertTrue($form->has('argument-tags'));
        self::assertTrue($form->has('option-pool'));

        $view = $form->createView();
        $commandFormService = $this->getCommandFormService();

        $filtered = $commandFormService->filter($view, CommandFormService::PRIORITY_ARGUMENT);
        self::assertCount(1, $filtered);
        $filtered = $commandFormService->filter($view, CommandFormService::PRIORITY_TEXT);
        self::assertCount(2, $filtered);
    }

    public function testFilter(): void
    {
        $form = $this->createForm();
        $view = $form->createView();
        $commandFormService = $this->getCommandFormService();

        $filtered = $commandFormService->filter($view, CommandFormService::PRIORITY_ARGUMENT);
        self::assertCount(1, $filtered);
    }

    /**
     * @phpstan-param array<string, array|scalar|null> $data
     *
     * @phpstan-return FormInterface<mixed>
     */
    private function createForm(string $name = 'completion', array $data = []): FormInterface
    {
        $command = $this->getCommand($name);
        $service = $this->getCommandFormService();

        return $service->createForm($command, $data);
    }

    /**
     * @phpstan-return CommandType
     */
    private function getCommand(string $name = 'completion'): array
    {
        $path = \sprintf('%s/../files/json/command_%s.json', __DIR__, $name);

        /** @phpstan-var CommandType */
        return FileUtils::decodeJson($path);
    }

    private function getCommandFormService(): CommandFormService
    {
        return new CommandFormService($this->factory);
    }
}

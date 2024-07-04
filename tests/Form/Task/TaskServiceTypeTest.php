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

namespace App\Tests\Form\Task;

use App\Form\Task\TaskServiceType;
use App\Form\Type\PlainType;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(TaskServiceType::class)]
class TaskServiceTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TaskTrait;
    use TranslatorMockTrait;

    public function testFormView(): void
    {
        $formData = [
            'task' => null,
            'quantity' => 1,
        ];
        $view = $this->factory->create(TaskServiceType::class, $formData)
            ->createView();

        foreach (\array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $view);
            self::assertSame((string) $formData[$key], $view->children[$key]->vars['value']);
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testFormViewSimpleWidget(): void
    {
        $formData = [
            'task' => $this->getTask(),
            'quantity' => 1,
        ];
        $view = $this->factory->create(TaskServiceType::class, $formData, ['simple_widget' => true])
            ->createView();

        self::assertArrayHasKey('task', $view);
        self::assertArrayHasKey('quantity', $view);
        self::assertSame((string) $formData['task']->getId(), $view->children['task']->vars['value']);
        self::assertSame((string) $formData['quantity'], $view->children['quantity']->vars['value']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSubmitValidData(): void
    {
        $task = $this->getTask();
        $formData = [
            'task' => $task->getId(),
            'quantity' => 1.0,
        ];
        $model = [
            'task' => null,
            'quantity' => 0.0,
        ];
        $form = $this->factory->create(TaskServiceType::class, $model);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getTaskEntityType(),
            new PlainType($this->createMockTranslator()),
        ];
    }
}

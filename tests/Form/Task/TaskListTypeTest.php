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

use App\Form\Task\TaskListType;
use App\Tests\Fixture\DataForm;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

class TaskListTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TaskTrait;

    /**
     * @throws \ReflectionException
     */
    public function testFormView(): void
    {
        $task = $this->getTask();
        $formData = DataForm::instance($task);

        $view = $this->factory->createBuilder(FormType::class, $formData)
            ->add('value', TaskListType::class)
            ->getForm()
            ->createView();

        self::assertArrayHasKey('value', $view->vars);
        self::assertEqualsCanonicalizing($formData, $view->vars['value']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSubmitValidData(): void
    {
        $task = $this->getTask();
        $formData = [
            'value' => $task->getId(),
        ];
        $model = DataForm::instance($task);
        $form = $this->factory->createBuilder(FormType::class, $model)
            ->add('value', TaskListType::class)
            ->getForm();
        $expected = DataForm::instance($task);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertEqualsCanonicalizing($expected, $model);
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getTaskEntityType(),
        ];
    }
}

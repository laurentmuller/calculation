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

namespace App\Tests\Form\Dialog;

use App\Form\Dialog\EditTaskDialogType;
use App\Tests\Form\Category\CategoryTrait;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\Form\Task\TaskTrait;
use Symfony\Component\Form\Test\TypeTestCase;

class EditTaskDialogTypeTest extends TypeTestCase
{
    use CategoryTrait;
    use PreloadedExtensionsTrait;
    use TaskTrait;

    /**
     * @throws \ReflectionException
     */
    public function testSubmitValidData(): void
    {
        $task = $this->getTask();
        $category = $this->getCategory();
        $formData = [
            'task' => $task->getId(),
            'category' => $category->getId(),
            'unit' => 'Unit',
            'quantity' => 1.0,
        ];
        $model = [
            'task' => null,
            'category' => null,
            'unit' => null,
            'quantity' => 0.0,
        ];
        $form = $this->factory->create(EditTaskDialogType::class, $model);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getTaskEntityType(),
            $this->getCategoryEntityType(),
        ];
    }
}

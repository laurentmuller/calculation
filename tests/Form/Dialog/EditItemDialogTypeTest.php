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

use App\Form\Dialog\EditItemDialogType;
use App\Tests\Form\Category\CategoryTrait;
use App\Tests\Form\PreloadedExtensionsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(EditItemDialogType::class)]
class EditItemDialogTypeTest extends TypeTestCase
{
    use CategoryTrait;
    use PreloadedExtensionsTrait;

    public function testFormView(): void
    {
        $formData = [
            'description' => 'Description',
            'unit' => 'Unit',
            'category' => null,
            'price' => 1.0,
            'quantity' => 1.0,
        ];
        $view = $this->factory->create(EditItemDialogType::class, $formData)
            ->createView();

        foreach (\array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $view);
            self::assertSame((string) $formData[$key], $view->children[$key]->vars['value']);
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function testSubmitValidData(): void
    {
        $category = $this->getCategory();
        $formData = [
            'description' => 'Description',
            'unit' => 'Unit',
            'category' => $category->getId(),
            'price' => 1.0,
            'quantity' => 1.0,
        ];
        $model = [
            'description' => null,
            'unit' => null,
            'category' => null,
            'price' => 0.0,
            'quantity' => 0.0,
        ];
        $form = $this->factory->create(EditItemDialogType::class, $model);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getCategoryEntityType(),
        ];
    }
}
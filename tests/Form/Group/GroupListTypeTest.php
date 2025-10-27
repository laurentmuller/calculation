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

namespace App\Tests\Form\Group;

use App\Form\Group\GroupListType;
use App\Tests\Fixture\DataForm;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

final class GroupListTypeTest extends TypeTestCase
{
    use GroupTrait;
    use PreloadedExtensionsTrait;

    /**
     * @throws \ReflectionException
     */
    public function testFormView(): void
    {
        $group = $this->getGroup();
        $formData = DataForm::instance($group);

        $view = $this->factory->createBuilder(FormType::class, $formData)
            ->add('value', GroupListType::class)
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
        $group = $this->getGroup();
        $formData = [
            'value' => $group->getId(),
        ];
        $model = DataForm::instance($group);
        $form = $this->factory->createBuilder(FormType::class, $model)
            ->add('value', GroupListType::class)
            ->getForm();
        $expected = DataForm::instance($group);
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
            $this->getGroupEntityType(),
        ];
    }
}

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

namespace App\Tests\Form\User;

use App\Form\User\UserListType;
use App\Tests\Fixture\FixtureDataForm;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

final class UserListTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use UserTrait;

    public function testFormView(): void
    {
        $user = $this->getUser();
        $formData = FixtureDataForm::instance($user);

        $view = $this->factory->createBuilder(FormType::class, $formData)
            ->add('value', UserListType::class)
            ->getForm()
            ->createView();

        self::assertArrayHasKey('value', $view->vars);
        self::assertEqualsCanonicalizing($formData, $view->vars['value']);
    }

    public function testSubmitValidData(): void
    {
        $user = $this->getUser();
        $formData = [
            'value' => $user->getId(),
        ];
        $model = FixtureDataForm::instance($user);
        $form = $this->factory->createBuilder(FormType::class, $model)
            ->add('value', UserListType::class)
            ->getForm();
        $expected = FixtureDataForm::instance($user);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertEqualsCanonicalizing($expected, $model);
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getUserEntityType(),
        ];
    }
}

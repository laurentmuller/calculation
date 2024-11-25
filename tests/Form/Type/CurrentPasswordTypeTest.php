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

namespace App\Tests\Form\Type;

use App\Form\Type\CurrentPasswordType;
use Symfony\Component\Form\Test\TypeTestCase;

class CurrentPasswordTypeTest extends TypeTestCase
{
    public function testFormView(): void
    {
        $view = $this->factory->create(CurrentPasswordType::class)
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(CurrentPasswordType::class);
        $form->submit('Fake Password for testing purpose.');
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }
}

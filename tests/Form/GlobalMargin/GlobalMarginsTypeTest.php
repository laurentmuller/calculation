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

namespace App\Tests\Form\GlobalMargin;

use App\Form\GlobalMargin\GlobalMarginsType;
use App\Model\GlobalMargins;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(GlobalMarginsType::class)]
class GlobalMarginsTypeTest extends TypeTestCase
{
    public function testFormView(): void
    {
        $formData = [
            'margins' => new ArrayCollection(),
        ];
        $view = $this->factory->create(GlobalMarginsType::class, $formData)
            ->createView();

        self::assertArrayHasKey('value', $view->vars);
        self::assertEqualsCanonicalizing($formData, $view->vars['value']);
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'margins' => new ArrayCollection(),
        ];
        $model = new GlobalMargins();
        $form = $this->factory->create(GlobalMarginsType::class, $model);
        $expected = new GlobalMargins();
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertEqualsCanonicalizing($expected, $model);
    }
}
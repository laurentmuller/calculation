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

use App\Form\Type\CaptchaImageType;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CaptchaImageTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;

    public function testFormView(): void
    {
        $view = $this->factory->create(CaptchaImageType::class, [], ['image' => 'fake_content'])
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(CaptchaImageType::class, [], ['image' => 'fake_content']);
        $form->submit('');
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);

        return [
            new CaptchaImageType($generator),
        ];
    }
}

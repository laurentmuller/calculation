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

use App\Form\Type\CountryFlagType;
use App\Service\CountryFlagService;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Test\TypeTestCase;

class CountryFlagTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;

    public function testFormView(): void
    {
        $view = $this->factory->create(CountryFlagType::class)
            ->createView();
        self::assertArrayHasKey('id', $view->vars);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(CountryFlagType::class);
        $form->submit('CH');
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        $service = $this->createMock(CountryFlagService::class);
        $service->method('getChoices')
            ->willReturn(['CH' => 'CH']);

        return [
            new CountryFlagType($service),
        ];
    }
}

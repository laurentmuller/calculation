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

namespace App\Tests\Form\Extension;

use App\Form\Extension\VichImageTypeExtension;
use App\Form\Type\PlainType;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\Form\User\VichImageTypeTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Test\TypeTestCase;
use Vich\UploaderBundle\Form\Type\VichImageType;

class VichImageTypeExtensionTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;
    use VichImageTypeTrait;

    public function testForm(): void
    {
        $form = $this->factory->createBuilder()
            ->add('image', VichImageType::class)
            ->getForm();
        self::assertTrue($form->has('image'));
        $child = $form->get('image');
        $options = $child->getConfig()
            ->getOptions();
        self::assertArrayHasKey('placeholder', $options);
        self::assertNull($options['placeholder']);
    }

    /**
     * @throws Exception
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            new PlainType($this->createMockTranslator()),
            $this->createVichImageType(),
        ];
    }

    protected function getTypeExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getTypeExtensions();
        $extensions[] = new VichImageTypeExtension();

        return $extensions;
    }
}

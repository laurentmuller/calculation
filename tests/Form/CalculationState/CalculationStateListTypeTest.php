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

namespace App\Tests\Form\CalculationState;

use App\Form\CalculationState\CalculationStateListType;
use App\Tests\Form\CalculationStateTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(CalculationStateListType::class)]
class CalculationStateListTypeTest extends TypeTestCase
{
    use CalculationStateTrait;
    use TranslatorMockTrait;

    /**
     * @throws \ReflectionException
     */
    public function testSubmitValidData(): void
    {
        $state = $this->getEditableState();
        $data = [
            'state' => $state,
        ];
        $form = $this->factory->createBuilder(FormType::class, $data)
            ->add('state', CalculationStateListType::class)
            ->getForm();
        $form->submit(['state' => $state->getId()]);
        self::assertTrue($form->isValid());
        self::assertSame($data['state'], $state);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();

        $types = [
            $this->getEntityType(),
            new CalculationStateListType($this->createMockTranslator()),
        ];
        $extensions[] = new PreloadedExtension($types, []);

        return $extensions;
    }
}

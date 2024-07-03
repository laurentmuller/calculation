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
use App\Tests\Data\DataForm;
use App\Tests\Form\CalculationStateTrait;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(CalculationStateListType::class)]
class CalculationStateListTypeTest extends TypeTestCase
{
    use CalculationStateTrait;
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    /**
     * @throws \ReflectionException
     */
    public function testFormView(): void
    {
        $state = $this->getNotEditableState();
        $formData = DataForm::instance($state);

        $view = $this->factory->createBuilder(FormType::class, $formData)
            ->add('value', CalculationStateListType::class)
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
        $state = $this->getNotEditableState();
        $formData = [
            'value' => $state->getId(),
        ];
        $model = DataForm::instance($state);
        $form = $this->factory->createBuilder(FormType::class, $model)
            ->add('value', CalculationStateListType::class)
            ->getForm();
        $expected = DataForm::instance($state);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertEqualsCanonicalizing($expected, $model);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getCalculationStateEntityType(),
            new CalculationStateListType($this->createMockTranslator()),
        ];
    }
}

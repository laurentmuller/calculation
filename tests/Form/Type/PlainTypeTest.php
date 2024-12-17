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

use App\Entity\User;
use App\Form\Type\PlainType;
use App\Tests\Fixture\DataForm;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

class PlainTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    public function testExceptException(): void
    {
        self::expectException(TransformationFailedException::class);
        $data = new DataForm();
        $this->factory->create(PlainType::class, $data)
            ->createView();
    }

    public function testWithArray(): void
    {
        $data = [1, 2, 3];
        $expected = '1, 2, 3';
        $this->validateViewValue($data, $expected);
    }

    public function testWithBoolWithDisplayTransformer(): void
    {
        $data = 'fake';
        $expected = 'fake';
        $callback = fn (): string => 'callback';
        $options = ['display_transformer' => $callback];
        $view = $this->validateViewValue($data, $expected, $options);

        $expected = 'callback';
        self::assertArrayHasKey('display_value', $view->vars);
        /** @psalm-var mixed $actual */
        $actual = $view->vars['display_value'];
        self::assertSame($expected, $actual);
    }

    public function testWithBoolWithValueTransformer(): void
    {
        $expected = 'common.value_true';
        $callback = fn (): bool => true;
        $options = ['value_transformer' => $callback];
        $this->validateViewValue(true, $expected, $options);
    }

    public function testWithDateWithDateAndTime(): void
    {
        $data = new \DateTime('2024-06-10 12:22:03');
        $expected = '10.06.2024 12:22';
        $options = [
            'date_format' => null,
            'time_format' => null,
            'date_pattern' => null,
        ];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testWithDateWithDateOnly(): void
    {
        $data = new \DateTime('2024-06-10 12:22:03');
        $expected = '10.06.2024';
        $options = [
            'date_format' => null,
            'time_format' => PlainType::FORMAT_NONE,
            'date_pattern' => null,
        ];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testWithDateWithTimeOnly(): void
    {
        $data = new \DateTime('2024-06-10 12:22:03');
        $expected = '12:22';
        $options = [
            'date_format' => PlainType::FORMAT_NONE,
            'time_format' => null,
            'date_pattern' => null,
        ];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testWithEmpty(): void
    {
        $data = '';
        $expected = 'common.value_null';
        $this->validateViewValue($data, $expected);
    }

    public function testWithEmptyWithCallback(): void
    {
        $data = '';
        $expected = 'callback';
        $callback = fn (): string => 'callback';
        $options = ['empty_value' => $callback];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testWithEntity(): void
    {
        $data = new User();
        $data->setUsername('username');
        $expected = 'username';
        $this->validateViewValue($data, $expected);
    }

    public function testWithNumberAmount(): void
    {
        $data = 1;
        $expected = '1.00';
        $options = ['number_pattern' => PlainType::NUMBER_AMOUNT];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testWithNumberIdentifier(): void
    {
        $data = 123;
        $expected = '000123';
        $options = ['number_pattern' => PlainType::NUMBER_IDENTIFIER];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testWithNumberInteger(): void
    {
        $data = 123456;
        $expected = '123\'456';
        $options = ['number_pattern' => PlainType::NUMBER_INTEGER];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testWithNumberPercent(): void
    {
        $data = 0.1;
        $expected = '10.00%';
        $options = ['number_pattern' => PlainType::NUMBER_PERCENT];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testWithNumeric(): void
    {
        $data = 123456;
        $expected = '123456';
        $this->validateViewValue($data, $expected);
    }

    protected function getPreloadedExtensions(): array
    {
        return [
            new PlainType($this->createMockTranslator()),
        ];
    }

    private function validateViewValue(mixed $data, mixed $expected, array $options = []): FormView
    {
        $view = $this->factory->create(PlainType::class, $data, $options)
            ->createView();

        self::assertArrayHasKey('value', $view->vars);
        /** @psalm-var mixed $actual */
        $actual = $view->vars['value'];
        self::assertSame($expected, $actual);

        return $view;
    }
}

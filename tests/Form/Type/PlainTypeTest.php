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
use App\Interfaces\DateFormatInterface;
use App\Tests\Form\PreloadedExtensionsTrait;
use App\Tests\TranslatorMockTrait;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

final class PlainTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use TranslatorMockTrait;

    public function testArray(): void
    {
        $data = [1, 2, 3];
        $expected = '1, 2, 3';
        $this->validateViewValue($data, $expected);
    }

    public function testBoolWithDisplayTransformer(): void
    {
        $data = 'fake';
        $expected = 'fake';
        $callback = static fn (): string => 'callback';
        $options = ['display_transformer' => $callback];
        $view = $this->validateViewValue($data, $expected, $options);

        $expected = 'callback';
        self::assertArrayHasKey('display_value', $view->vars);
        $actual = $view->vars['display_value'];
        self::assertSame($expected, $actual);
    }

    public function testBoolWithValueTransformer(): void
    {
        $expected = 'common.value_true';
        $callback = static fn (): bool => true;
        $options = ['value_transformer' => $callback];
        $this->validateViewValue(true, $expected, $options);
    }

    public function testDateWithDateAndTime(): void
    {
        $data = new DatePoint('2024-06-10 12:22:03');
        $expected = '10.06.2024 12:22';
        $options = [
            'date_format' => null,
            'time_format' => null,
            'date_pattern' => null,
        ];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testDateWithDateOnly(): void
    {
        $data = new DatePoint('2024-06-10 12:22:03');
        $expected = '10.06.2024';
        $options = [
            'date_format' => null,
            'time_format' => DateFormatInterface::FORMAT_NONE,
            'date_pattern' => null,
        ];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testDateWithTimeOnly(): void
    {
        $data = new DatePoint('2024-06-10 12:22:03');
        $expected = '12:22';
        $options = [
            'date_format' => DateFormatInterface::FORMAT_NONE,
            'time_format' => null,
            'date_pattern' => null,
        ];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testEmptyDefault(): void
    {
        $data = '';
        $expected = 'common.value_null';
        $this->validateViewValue($data, $expected);
    }

    public function testEmptyWithCallback(): void
    {
        $data = '';
        $expected = 'callback';
        $callback = static fn (): string => 'callback';
        $options = ['empty_value' => $callback];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testEmptyWithNull(): void
    {
        $data = null;
        $expected = 'custom_empty_text';
        $options = ['empty_value' => $expected];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testEntity(): void
    {
        $data = new User();
        $data->setUsername('username');
        $expected = 'username';
        $this->validateViewValue($data, $expected);
    }

    public function testInvalidDataValue(): void
    {
        self::expectException(TransformationFailedException::class);
        self::expectExceptionMessage('Unable to map instance of "stdClass" to string.');
        $this->factory->create(PlainType::class, new \stdClass())
            ->createView();
    }

    public function testNumberAmount(): void
    {
        $data = 1;
        $expected = '1.00';
        $options = ['number_pattern' => PlainType::NUMBER_AMOUNT];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testNumberIdentifier(): void
    {
        $data = 123;
        $expected = '000123';
        $options = ['number_pattern' => PlainType::NUMBER_IDENTIFIER];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testNumberInteger(): void
    {
        $data = 123456;
        $expected = "123'456";
        $options = ['number_pattern' => PlainType::NUMBER_INTEGER];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testNumberPercent(): void
    {
        $data = 0.1;
        $expected = '10.00%';
        $options = ['number_pattern' => PlainType::NUMBER_PERCENT];
        $this->validateViewValue($data, $expected, $options);
    }

    public function testNumeric(): void
    {
        $data = 123456;
        $expected = '123456';
        $this->validateViewValue($data, $expected);
    }

    #[\Override]
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
        $actual = $view->vars['value'];
        self::assertSame($expected, $actual);

        return $view;
    }
}

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

use App\Form\Type\BoolHiddenType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Test\TypeTestCase;

#[AllowMockObjectsWithoutExpectations]
final class BoolHiddenTypeTest extends TypeTestCase
{
    public static function getSubmitData(): \Iterator
    {
        yield [null, false];
        yield ['', false];
        yield [0, false];
        yield ['0', false];
        yield [false, false];
        yield ['false', false];
        yield ['off', false];
        yield ['no', false];

        yield [1, true];
        yield ['1', true];
        yield [true, true];
        yield ['true', true];
        yield ['on', true];
        yield ['yes', true];
    }

    public function testConfig(): void
    {
        $config = $this->factory->create(BoolHiddenType::class)
            ->getConfig();
        self::assertFalse($config->getMapped());
        self::assertFalse($config->getRequired());
    }

    #[DataProvider('getSubmitData')]
    public function testSubmit(mixed $data, bool $expected): void
    {
        $form = $this->factory->create(BoolHiddenType::class)
            ->submit($data);
        /** @phpstan-ignore staticMethod.impossibleType */
        self::assertSame($expected, $form->getData());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
    }

    public function testView(): void
    {
        $view = $this->factory->create(BoolHiddenType::class)
            ->createView();
        self::assertFalse($view->vars['required']);
    }
}

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

namespace App\Tests\Traits;

use App\Tests\Fixture\Translatable;
use App\Tests\TranslatorMockTrait;
use App\Traits\TranslatorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorTraitTest extends TestCase
{
    use TranslatorMockTrait;
    use TranslatorTrait;

    private bool $useInterface = true;

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        if ($this->useInterface) {
            return $this->createMockTranslator();
        }

        $translator = $this->createMock(Translator::class);
        $translator->method('trans')
            ->willReturnArgument(0);

        return $translator;
    }

    public function testIsTransDefined(): void
    {
        $this->useInterface = true;
        $actual = $this->isTransDefined('id');
        self::assertFalse($actual);

        $this->useInterface = false;
        $actual = $this->isTransDefined('id');
        self::assertFalse($actual);
    }

    public function testNoBagTranslator(): void
    {
        $translator = new class() implements TranslatorInterface {
            use TranslatorTrait;

            #[\Override]
            public function trans(
                string $id,
                array $parameters = [],
                ?string $domain = null,
                ?string $locale = null
            ): string {
                return $id;
            }

            #[\Override]
            public function getLocale(): string
            {
                return \Locale::getDefault();
            }

            #[\Override]
            public function getTranslator(): TranslatorInterface
            {
                return $this;
            }
        };
        $actual = $translator->isTransDefined('id');
        self::assertFalse($actual);
    }

    public function testTrans(): void
    {
        $actual = $this->trans('id');
        self::assertSame('id', $actual);

        $translatable = new Translatable();
        $actual = $this->trans($translatable);
        self::assertSame('id', $actual);
    }
}

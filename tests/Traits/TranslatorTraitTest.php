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

use App\Tests\Data\Translatable;
use App\Tests\TranslatorMockTrait;
use App\Traits\TranslatorTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(TranslatorTrait::class)]
class TranslatorTraitTest extends TestCase
{
    use TranslatorMockTrait;
    use TranslatorTrait;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createTranslator();
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function testIsTransDefined(): void
    {
        $actual = $this->isTransDefined('id');
        self::assertFalse($actual);
    }

    public function testNoBagTranslator(): void
    {
        $translator = new class() implements TranslatorInterface {
            use TranslatorTrait;

            public function trans(
                string $id,
                array $parameters = [],
                ?string $domain = null,
                ?string $locale = null
            ): string {
                return $id;
            }

            public function getLocale(): string
            {
                return \Locale::getDefault();
            }

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

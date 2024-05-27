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

namespace App\Tests\Service;

use App\Service\CountryFlagService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Countries;

#[CoversClass(CountryFlagService::class)]
class CountryFlagServiceTest extends TestCase
{
    private CountryFlagService $service;

    protected function setUp(): void
    {
        $this->service = new CountryFlagService();
    }

    public static function getDefaultCodes(): \Iterator
    {
        yield ['en-US', 'US'];
        yield ['fr-CH', 'CH'];
        yield ['sl-Latn-IT', 'IT'];
        yield ['sl-Latn-IT-nedis', 'IT'];
        yield ['zh_Hans_MO', 'MO'];
    }

    public static function getFlags(): \Iterator
    {
        yield ['CH', '🇨🇭'];
        yield ['CH', '🇨🇭', true];
        yield ['CH', '🇨🇭', false];
        yield ['FR', '🇫🇷'];
        yield ['ZZ', '', true, true];
        yield ['ZZ', '', false];
    }

    public function testChoices(): void
    {
        $expected = \count(Countries::getNames());
        $choices = $this->service->getChoices();
        self::assertCount($expected, $choices);

        $choices = $this->service->getChoices(flagOnly: true);
        self::assertCount($expected, $choices);
    }

    #[DataProvider('getDefaultCodes')]
    public function testDefaultCode(string $locale, string $expected): void
    {
        \Locale::setDefault($locale);
        $actual = CountryFlagService::getDefaultCode();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getFlags')]
    public function testGetFlag(string $alpha2Code, string $expected, bool $validate = true, bool $exception = false): void
    {
        if ($exception) {
            self::expectException(\InvalidArgumentException::class);
            self::expectExceptionMessage("Invalid country code: '$alpha2Code'.");
        }
        $flag = $this->service->getFlag($alpha2Code, $validate);
        if ($exception) {
            self::fail('No exception raised');
        }
        self::assertSame($expected, $flag);
    }
}

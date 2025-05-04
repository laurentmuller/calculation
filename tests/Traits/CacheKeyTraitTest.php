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

use App\Traits\CacheKeyTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\ItemInterface;

class CacheKeyTraitTest extends TestCase
{
    use CacheKeyTrait;

    /**
     * @phpstan-return \Generator<int, array{string, string}>
     */
    public static function getCleanKeys(): \Generator
    {
        yield ['', ''];
        yield ['valid', 'valid'];
        yield ['space one', 'space one'];

        yield ['test{', 'test_'];
        yield ['test}', 'test_'];
        yield ['test(', 'test_'];
        yield ['test)', 'test_'];
        yield ['test/', 'test_'];
        yield ['test\\', 'test_'];
        yield ['test@', 'test_'];
        yield ['test:', 'test_'];

        yield ['@before', '_before'];
        yield ['after@', 'after_'];
        yield ['@before@after@', '_before_after_'];

        $chars = \str_split(ItemInterface::RESERVED_CHARACTERS);
        foreach ($chars as $char) {
            yield [$char, '_'];
        }
    }

    #[DataProvider('getCleanKeys')]
    public function testCleanKey(string $key, string $expected): void
    {
        $actual = $this->cleanKey($key);
        self::assertSame($expected, $actual);
    }
}

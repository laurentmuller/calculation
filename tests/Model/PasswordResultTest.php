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

namespace App\Tests\Model;

use App\Enums\StrengthLevel;
use App\Model\PasswordResult;
use PHPUnit\Framework\TestCase;

final class PasswordResultTest extends TestCase
{
    public function testConstruct(): void
    {
        $actual = $this->createResult(0);
        self::assertSame(0, $actual->score);
        self::assertNull($actual->warning);
        self::assertNull($actual->suggestions);

        $actual = $this->createResult(1, 'warning', ['suggestion1', 'suggestion2']);
        self::assertSame(1, $actual->score);
        self::assertSame('warning', $actual->warning);
        self::assertSame(['suggestion1', 'suggestion2'], $actual->suggestions);
    }

    public function testGetStrengthLevel(): void
    {
        $result = $this->createResult(-1);
        $actual = $result->getStrengthLevel();
        self::assertSame(StrengthLevel::NONE, $actual);

        $result = $this->createResult(-2);
        $actual = $result->getStrengthLevel();
        self::assertNull($actual);
    }

    public function testToArray(): void
    {
        $result = $this->createResult(0);
        $actual = $result->toArray();
        self::assertSame(['score' => 0], $actual);

        $result = $this->createResult(0, 'warning', ['suggestion1', 'suggestion2']);
        $actual = $result->toArray();
        self::assertSame([
            'score' => 0,
            'warning' => 'warning',
            'suggestions' => ['suggestion1', 'suggestion2'],
        ], $actual);
    }

    private function createResult(int $score, ?string $warning = null, ?array $suggestions = null): PasswordResult
    {
        return new PasswordResult($score, $warning, $suggestions);
    }
}

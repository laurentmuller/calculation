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

namespace App\Tests\Fixture;

use App\Enums\EntityAction;
use App\Parameter\ParameterInterface;

class FixtureParameter implements ParameterInterface
{
    public EntityAction $action = EntityAction::NONE;
    public float $minMargin = 0.0;
    public string $name = '';
    public bool $value = false;

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'fake';
    }
}

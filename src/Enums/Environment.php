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

namespace App\Enums;

use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;

/**
 * The environment enumeration.
 */
#[ReadableEnum(prefix: 'environment.', useValueAsDefault: true)]
enum Environment: string implements TranslatableEnumInterface
{
    use TranslatableEnumTrait;

    /**
     * The development environment.
     */
    case DEVELOPMENT = 'dev';

    /**
     * The production environment.
     */
    case PRODUCTION = 'prod';

    /**
     * The test environment.
     */
    case TEST = 'test';
}

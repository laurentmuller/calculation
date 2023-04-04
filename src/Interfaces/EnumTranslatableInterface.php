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

namespace App\Interfaces;

use Elao\Enum\ReadableEnumInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Interface to get the translatable enumeration value.
 */
interface EnumTranslatableInterface extends ReadableEnumInterface, TranslatableInterface
{
}

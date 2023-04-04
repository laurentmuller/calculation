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

namespace App\Traits;

use Elao\Enum\ReadableEnumTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Trait for enumeration implementing {@link \App\Interfaces\EnumTranslatableInterface EnumTranslatableInterface} interface.
 *
 * @psalm-require-implements \App\Interfaces\EnumTranslatableInterface
 */
trait EnumTranslatableTrait
{
    use ReadableEnumTrait;

    public function trans(TranslatorInterface $translator, string $locale = null): string
    {
        return $translator->trans(id: $this->getReadable(), locale: $locale);
    }
}

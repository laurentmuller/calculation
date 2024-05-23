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

namespace App\Tests\Data;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translatable implements TranslatableInterface
{
    public string $id = 'id';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->id;
    }
}

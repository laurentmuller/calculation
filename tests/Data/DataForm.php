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

/**
 * Class for form type tests.
 */
class DataForm
{
    private mixed $value = null;

    public function getValue(): mixed
    {
        return $this->value;
    }

    public static function instance(mixed $value): self
    {
        $instance = new self();
        $instance->setValue($value);

        return $instance;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}

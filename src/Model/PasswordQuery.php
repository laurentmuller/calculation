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

namespace App\Model;

use App\Enums\StrengthLevel;

/**
 * Class to hold password data.
 */
class PasswordQuery
{
    public function __construct(
        public string $password = '',
        public StrengthLevel $strength = StrengthLevel::NONE,
        public ?string $email = null,
        public ?string $user = null
    ) {
    }

    public function getInputs(): array
    {
        return \array_filter([$this->email, $this->user]);
    }
}

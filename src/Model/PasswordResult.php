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
 * Class to hold password result.
 */
readonly class PasswordResult
{
    public function __construct(
        public int $score,
        public ?string $warning = null,
        /** @var string[]|null */
        public ?array $suggestions = null,
    ) {
    }

    public function getStrengthLevel(): ?StrengthLevel
    {
        return StrengthLevel::tryFrom($this->score);
    }

    public function toArray(): array
    {
        return ['score' => $this->score] + \array_filter([
            'warning' => $this->warning,
            'suggestions' => $this->suggestions,
        ]);
    }
}

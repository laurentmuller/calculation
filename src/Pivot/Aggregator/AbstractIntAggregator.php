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

namespace App\Pivot\Aggregator;

use App\Utils\FormatUtils;

/**
 * Abstract aggregator for int values.
 */
abstract class AbstractIntAggregator extends AbstractAggregator
{
    protected int $result = 0;

    #[\Override]
    public function getFormattedResult(): string
    {
        return FormatUtils::formatInt($this->getResult());
    }

    #[\Override]
    public function getResult(): int
    {
        return $this->result;
    }

    #[\Override]
    public function initialize(): static
    {
        $this->result = 0;

        return $this;
    }
}

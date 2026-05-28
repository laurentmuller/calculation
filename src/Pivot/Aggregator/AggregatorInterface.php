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

interface AggregatorInterface extends \JsonSerializable, \Stringable
{
    /**
     * Add the given value.
     */
    public function add(self|int|float|null $value): self;

    /*
    * Gets the formatted result.
    */
    public function getFormattedResult(): string;

    /**
     * Gets the result.
     */
    public function getResult(): int|float;

    /**
     * Gets the rounded result.
     */
    public function getRoundResult(): int|float;

    /**
     * Initialize the result.
     */
    public function initialize(): self;
}

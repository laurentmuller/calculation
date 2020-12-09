<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pivot\Aggregator;

use App\Util\Utils;

/**
 * Aggregator function.
 *
 * @author Laurent Muller
 */
abstract class Aggregator implements \JsonSerializable
{
    /**
     *  Constructor.
     *
     *  @param mixed $value the initial value
     */
    public function __construct($value = null)
    {
        $this->init();
        if (null !== $value) {
            $this->add($value);
        }
    }

    public function __toString(): string
    {
        $name = Utils::getShortName($this);
        $value = $this->getFormattedResult();

        return \sprintf('%s(%s)', $name, $value);
    }

    /**
     * Add the given value.
     *
     * @param mixed $value the value to add
     */
    abstract public function add($value): self;

    /**
     * Gets the formatted result.
     *
     * @return mixed the formatted result
     */
    public function getFormattedResult()
    {
        return $this->getResult();
    }

    /**
     * Gets the result.
     *
     * @return mixed the result
     */
    abstract public function getResult();

    /**
     * Initialize.
     */
    abstract public function init(): self;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'name' => Utils::getShortName($this),
            'value' => $this->getFormattedResult(),
        ];
    }
}

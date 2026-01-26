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

/**
 * Entity interface.
 */
interface EntityInterface extends \Stringable
{
    /**
     * The extra lazy fetch mode.
     */
    final public const string EXTRA_LAZY = 'EXTRA_LAZY';

    /**
     * The maximum length for a code property.
     */
    final public const int MAX_CODE_LENGTH = 30;

    /**
     * The maximum length for a string property.
     */
    final public const int MAX_STRING_LENGTH = 255;

    /**
     * Gets a string used to display in the user interface (UI).
     */
    public function getDisplay(): string;

    /**
     * Get the primary key identifier value.
     *
     * @return int|null the key identifier value or null if is a new entity
     */
    public function getId(): ?int;

    /**
     * Returns if this entity is new.
     *
     * @return bool true if this entity has never been saved to the database
     */
    public function isNew(): bool;
}

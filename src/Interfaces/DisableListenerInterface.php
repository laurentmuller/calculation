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

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Class implementing this interface deals with the enablement state.
 */
#[AutoconfigureTag]
interface DisableListenerInterface
{
    /**
     * Gets the enabled state.
     *
     * @return bool true if enabled; false if disabled
     */
    public function isEnabled(): bool;

    /**
     * Sets the enabled state.
     *
     * @param bool $enabled true to enable; false to disable
     */
    public function setEnabled(bool $enabled): static;
}

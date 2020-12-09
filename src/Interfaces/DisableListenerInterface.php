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

namespace App\Interfaces;

/**
 * Class implementing this interface deals with enablement state.
 *
 * @author Laurent Muller
 */
interface DisableListenerInterface
{
    /**
     * Sets the enabled state.
     *
     * @param bool $enabled true to enable; false to disable
     */
    public function setEnabled(bool $enabled): self;
}

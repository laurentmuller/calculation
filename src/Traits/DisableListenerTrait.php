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

namespace App\Traits;

/**
 * Trait for class implementing the <code>DisableListenerInterface</code> interface.
 *
 * @author Laurent Muller
 *
 * @see DisableListenerInterface
 */
trait DisableListenerTrait
{
    /**
     * The enabled state.
     */
    protected bool $enabled = true;

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}

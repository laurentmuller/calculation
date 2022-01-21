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

namespace App\Service;

use App\Interfaces\DisableListenerInterface;

/**
 * Service to enable or disable listeners. Only listeners implementing
 * the <code>DisableListenerInterface</code> interface are taken into account.
 *
 * @author Laurent Muller
 *
 * @see DisableListenerInterface
 */
class SuspendEventListenerService
{
    /**
     * The disabled state.
     */
    private bool $disabled = false;

    /**
     * @var DisableListenerInterface[]
     */
    private array $listeners;

    /**
     * Constructor.
     *
     * @param iterable<DisableListenerInterface> $listeners
     */
    public function __construct(iterable $listeners)
    {
        $this->listeners = $listeners instanceof \Traversable ? \iterator_to_array($listeners) : $listeners;
    }

    /**
     * Destructor. The listeners are automatically enabled.
     */
    public function __destruct()
    {
        $this->enableListeners();
    }

    /**
     * Disable listeners. Do nothing if the listeners are already disabled.
     */
    public function disableListeners(): void
    {
        if (!$this->disabled) {
            $this->updateListeners(false);
            $this->disabled = true;
        }
    }

    /**
     * Enable listeners. Do nothing if the listeners are not disabled.
     */
    public function enableListeners(): void
    {
        if ($this->disabled) {
            $this->updateListeners(true);
            $this->disabled = false;
        }
    }

    /**
     * Returns a value indicating if the listeners are disabled.
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Update listeners enablement.
     */
    private function updateListeners(bool $enabled): void
    {
        foreach ($this->listeners as $listener) {
            if ($listener instanceof DisableListenerInterface) {
                $listener->setEnabled($enabled);
            }
        }
    }
}

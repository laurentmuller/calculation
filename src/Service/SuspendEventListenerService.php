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
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to enable and disable doctrine listeners. Only listeners implementing
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
     * The entity manager.
     */
    private EntityManagerInterface $manager;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $manager the entity manager get listeners
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
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
     *
     * @return bool true if disabled; false if enabled
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Update listeners enablement.
     *
     * @param bool $enabled true to enable, false to disable
     */
    private function updateListeners(bool $enabled): void
    {
        $manager = $this->manager->getEventManager();

        foreach ($manager->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof DisableListenerInterface) {
                    $listener->setEnabled($enabled);
                }
            }
        }
    }
}
